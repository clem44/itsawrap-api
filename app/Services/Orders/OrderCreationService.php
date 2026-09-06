<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Status;
use App\Services\Delivery\DeliveryScheduler;
use App\Services\Push\PosPushNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderCreationService
{
    private const GUEST_LOOKUP_TOKEN_TTL_DAYS = 7;

    public function __construct(
        private readonly PosPushNotifier $posPushNotifier,
        private readonly DeliveryScheduler $deliveryScheduler,
        private readonly BundleOrderExpander $bundleOrderExpander,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, int $customerId, string $source, ?string $ip = null): Order
    {
        $idempotencyKey = $validated['idempotency_key'] ?? null;

        if ($idempotencyKey !== null) {
            $existingOrder = Order::query()
                ->where('source', $source)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['customer', 'status', 'delivery.deliveryWindow', 'participants', 'orderItems.participant', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option'])
                ->first();

            if ($existingOrder !== null) {
                Log::info("{$source}-order-idempotent-replay", [
                    'order_id' => $existingOrder->id,
                    'idempotency_key' => $idempotencyKey,
                    'ip' => $ip,
                ]);

                return $existingOrder;
            }
        }

        $validated = $this->bundleOrderExpander->expand($validated);

        $lockKey = "{$source}-order:".sha1((string) ($idempotencyKey ?? json_encode([
            'customer_id' => $customerId,
            'total' => $validated['total'],
            'participants' => $validated['participants'] ?? [],
            'items' => $validated['items'],
        ], JSON_THROW_ON_ERROR)));

        $lock = Cache::lock($lockKey, 15);

        if (! $lock->get()) {
            Log::warning("{$source}-order-duplicate-blocked", [
                'ip' => $ip,
                'idempotency_key' => $idempotencyKey,
            ]);

            abort(response()->json(['message' => 'This order is already being processed.'], 409));
        }

        try {
            $pendingStatusId = Status::query()
                ->where('name', 'pending')
                ->value('id') ?? 1;

            $createOrder = function () use ($validated, $customerId, $source, $pendingStatusId, $idempotencyKey): Order {
                $guestLookupToken = $source === 'guest-web' ? Str::random(64) : null;

                $order = Order::query()->create([
                    'number' => filled($validated['number'] ?? null) ? $validated['number'] : $this->generateOrderNumber(),
                    'customer_id' => $customerId,
                    'status_id' => $pendingStatusId,
                    'subtotal' => $validated['subtotal'],
                    'discount' => 0,
                    'discount_percent' => 0,
                    'service_charge' => $validated['service_charge'] ?? 0,
                    'total' => $validated['total'],
                    'comments' => $validated['comments'] ?? null,
                    'placed_at' => now(),
                    'is_delivery' => $validated['is_delivery'],
                    'is_reward' => false,
                    'session_id' => null,
                    'source' => $source,
                    'idempotency_key' => $idempotencyKey,
                    'guest_access_token' => $guestLookupToken,
                    'guest_access_token_hash' => $guestLookupToken !== null ? hash('sha256', $guestLookupToken) : null,
                    'guest_access_token_expires_at' => $guestLookupToken !== null ? now()->addDays(self::GUEST_LOOKUP_TOKEN_TTL_DAYS) : null,
                ]);

                $participantIdsByClientId = $this->createParticipants($order, $validated['participants'] ?? []);

                foreach ($validated['items'] as $itemData) {
                    $orderItem = $order->orderItems()->create([
                        'order_participant_id' => $this->participantIdForItem($participantIdsByClientId, $itemData),
                        'item_id' => $itemData['item_id'],
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'],
                        'comment' => $itemData['comment'] ?? null,
                    ]);

                    foreach ($itemData['options'] ?? [] as $optionData) {
                        $orderItem->orderItemOptions()->create([
                            'option_value_id' => $optionData['option_value_id'],
                            'parent_option_value_id' => $optionData['parent_option_value_id'] ?? null,
                            'price' => $optionData['price'] ?? 0,
                            'qty' => $optionData['qty'] ?? null,
                        ]);
                    }
                }

                if ($order->is_delivery) {
                    $this->deliveryScheduler->reserveForOrder($order, $validated);
                }

                return $order;
            };

            $order = $this->deliveryScheduler->withReservationLock(
                $validated,
                fn (): Order => DB::transaction($createOrder),
            );
        } finally {
            $lock->release();
        }

        Log::info("{$source}-order-created", [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'ip' => $ip,
            'item_count' => count($validated['items']),
        ]);

        $this->posPushNotifier->webOrderCreated($order);

        return $order->load(['customer', 'status', 'delivery.deliveryWindow', 'participants', 'orderItems.participant', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']);
    }

    /**
     * Fallback for clients that don't generate their own order number.
     */
    protected function generateOrderNumber(): string
    {
        do {
            $number = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<int, array<string, mixed>>  $participants
     * @return array<string, int>
     */
    private function createParticipants(Order $order, array $participants): array
    {
        if ($participants === []) {
            return [];
        }

        $primaryClientId = $this->primaryParticipantClientId($participants);
        $participantIdsByClientId = [];

        foreach (array_values($participants) as $index => $participantData) {
            $clientId = (string) $participantData['client_id'];
            $participant = $order->participants()->create([
                'client_id' => $clientId,
                'name' => trim((string) $participantData['name']),
                'is_primary' => $clientId === $primaryClientId,
                'sort_order' => $index,
            ]);

            $participantIdsByClientId[$clientId] = $participant->id;
        }

        return $participantIdsByClientId;
    }

    /**
     * @param  array<int, array<string, mixed>>  $participants
     */
    private function primaryParticipantClientId(array $participants): ?string
    {
        $participants = array_values($participants);

        foreach ($participants as $participantData) {
            if (filter_var($participantData['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return (string) $participantData['client_id'];
            }
        }

        return isset($participants[0]['client_id']) ? (string) $participants[0]['client_id'] : null;
    }

    /**
     * @param  array<string, int>  $participantIdsByClientId
     * @param  array<string, mixed>  $itemData
     */
    private function participantIdForItem(array $participantIdsByClientId, array $itemData): ?int
    {
        $clientId = $itemData['participant_client_id'] ?? null;

        if ($clientId === null) {
            return null;
        }

        return $participantIdsByClientId[(string) $clientId] ?? null;
    }
}
