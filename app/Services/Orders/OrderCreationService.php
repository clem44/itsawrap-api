<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Status;
use App\Services\Push\PosPushNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCreationService
{
    public function __construct(private readonly PosPushNotifier $posPushNotifier) {}

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
                ->with(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option'])
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

        $lockKey = "{$source}-order:".sha1((string) ($idempotencyKey ?? json_encode([
            'customer_id' => $customerId,
            'total' => $validated['total'],
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

            $order = DB::transaction(function () use ($validated, $customerId, $source, $pendingStatusId, $idempotencyKey) {
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
                ]);

                foreach ($validated['items'] as $itemData) {
                    $orderItem = $order->orderItems()->create([
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

                return $order;
            });
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

        return $order->load(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']);
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
}
