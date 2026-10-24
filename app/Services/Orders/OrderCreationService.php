<?php

namespace App\Services\Orders;

use App\Models\CashSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RewardProgram;
use App\Models\Status;
use App\Services\Delivery\DeliveryScheduler;
use App\Services\Push\PosPushNotifier;
use App\Services\Rewards\RewardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    private const GUEST_LOOKUP_TOKEN_TTL_DAYS = 7;

    public function __construct(
        private readonly PosPushNotifier $posPushNotifier,
        private readonly DeliveryScheduler $deliveryScheduler,
        private readonly BundleOrderExpander $bundleOrderExpander,
        private readonly MealDealSelectionService $mealDealSelections,
        private readonly RewardService $rewards,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, int $customerId, string $source, ?string $ip = null, ?int $branchId = null): Order
    {
        $idempotencyKey = $validated['idempotency_key'] ?? null;

        if ($idempotencyKey !== null) {
            $existingOrder = Order::query()
                ->where('source', $source)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['customer', 'status', 'delivery.deliveryWindow', 'participants', 'mealDeals', 'orderItems.participant', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option'])
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

        // An open cash session is what "the shop is open" means. Orders
        // placed against a branch with no open session are rejected rather
        // than silently queued with a null session_id.
        $sessionId = null;
        if ($branchId !== null) {
            $session = CashSession::query()
                ->where('branch_id', $branchId)
                ->where('is_open', true)
                ->latest('opened_at')
                ->first();

            if ($session === null) {
                throw ValidationException::withMessages([
                    'branch_id' => 'This location is not currently accepting orders.',
                ]);
            }

            $sessionId = $session->id;
        }

        $validated = $this->bundleOrderExpander->expand($validated);
        $preparedDeals = [];
        foreach ($validated['meal_deals'] ?? [] as $index => $selection) {
            $preparedDeals[] = $this->mealDealSelections->prepare($selection, "meal_deals.{$index}");
        }
        if ($preparedDeals !== []) {
            $ordinarySubtotal = collect($validated['items'] ?? [])->sum(function (array $item): float {
                $options = collect($item['options'] ?? [])->sum(fn (array $option): float => (float) ($option['price'] ?? 0) * max(1, (int) ($option['qty'] ?? 1)));

                return ((float) $item['price'] + $options) * (int) $item['quantity'];
            });
            $dealSubtotal = collect($preparedDeals)->sum(fn (array $deal): float => ($deal['base_price'] + $deal['extras_price']) * $deal['quantity']);
            $dealCharge = collect($preparedDeals)->sum(fn (array $deal): float => $deal['service_charge'] * $deal['quantity']);
            $validated['delivery_fee'] = (float) ($validated['service_charge'] ?? 0);
            $validated['meal_deal_service_charge'] = round($dealCharge, 2);
            $validated['subtotal'] = round($ordinarySubtotal + $dealSubtotal, 2);
            $validated['service_charge'] = round($validated['delivery_fee'] + $dealCharge, 2);
            $validated['total'] = round($validated['subtotal'] + $validated['service_charge'], 2);
            $validated['items'] = $validated['items'] ?? [];
            foreach ($preparedDeals as $dealIndex => $deal) {
                foreach ($deal['items'] as $item) {
                    $validated['items'][] = [...$item, '_meal_deal_index' => $dealIndex, 'participant_client_id' => $deal['participant_client_id']];
                }
            }
        }
        unset($validated['meal_deals']);

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

            $createOrder = function () use ($validated, $preparedDeals, $customerId, $source, $pendingStatusId, $idempotencyKey, $sessionId): Order {
                $guestLookupToken = $source === 'guest-web' ? Str::random(64) : null;

                $order = Order::query()->create([
                    'number' => filled($validated['number'] ?? null) ? $validated['number'] : $this->generateOrderNumber(),
                    'customer_id' => $customerId,
                    'status_id' => $pendingStatusId,
                    'subtotal' => $validated['subtotal'],
                    'discount' => 0,
                    'discount_percent' => 0,
                    'service_charge' => $validated['service_charge'] ?? 0,
                    'meal_deal_service_charge' => $validated['meal_deal_service_charge'] ?? 0,
                    'delivery_fee' => $validated['delivery_fee'] ?? 0,
                    'total' => $validated['total'],
                    'comments' => $validated['comments'] ?? null,
                    'placed_at' => now(),
                    'is_delivery' => $validated['is_delivery'],
                    'is_reward' => false,
                    'session_id' => $sessionId,
                    'source' => $source,
                    'idempotency_key' => $idempotencyKey,
                    'guest_access_token' => $guestLookupToken,
                    'guest_access_token_hash' => $guestLookupToken !== null ? hash('sha256', $guestLookupToken) : null,
                    'guest_access_token_expires_at' => $guestLookupToken !== null ? now()->addDays(self::GUEST_LOOKUP_TOKEN_TTL_DAYS) : null,
                ]);

                $participantIdsByClientId = $this->createParticipants($order, $validated['participants'] ?? []);

                $purchases = [];
                foreach ($preparedDeals as $index => $deal) {
                    $purchases[$index] = $order->mealDeals()->create([
                        'meal_deal_id' => $deal['meal_deal_id'],
                        'order_participant_id' => $this->participantIdForItem($participantIdsByClientId, $deal),
                        'name' => $deal['name'],
                        'base_price' => $deal['base_price'],
                        'extras_price' => $deal['extras_price'],
                        'service_charge' => $deal['service_charge'],
                        'quantity' => $deal['quantity'],
                        'selections' => $deal['selections'],
                    ]);
                }

                foreach ($validated['items'] as $itemData) {
                    $orderItem = $order->orderItems()->create([
                        'order_meal_deal_id' => isset($itemData['_meal_deal_index']) ? $purchases[$itemData['_meal_deal_index']]->id : null,
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

                    // Redeemed after the options exist, so the discount covers
                    // the whole line rather than the item's bare base price.
                    $this->redeemRewardOnItem($order, $orderItem, $itemData);
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

        return $order->load(['customer', 'status', 'delivery.deliveryWindow', 'participants', 'mealDeals', 'orderItems.participant', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']);
    }

    /**
     * Spend a customer reward on the line the client marked, if it marked one.
     *
     * RewardService does the checking that matters — that the item is in the
     * programme's reward category and that the customer actually has a reward
     * to spend — and throws a validation error if not, which rolls the whole
     * order back rather than letting a free item through unpaid for.
     *
     * @param  array<string, mixed>  $itemData
     */
    private function redeemRewardOnItem(Order $order, OrderItem $orderItem, array $itemData): void
    {
        if (! filter_var($itemData['is_reward_item'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $program = RewardProgram::query()->find($itemData['reward_program_id'] ?? null);

        if ($program === null) {
            throw ValidationException::withMessages([
                'items' => 'A reward programme is required to redeem a reward.',
            ]);
        }

        $this->rewards->redeemReward($order, $orderItem->refresh(), $program);
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
