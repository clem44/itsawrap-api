<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\StoreGuestOrderRequest;
use App\Models\Order;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestOrderController extends Controller
{
    public function store(StoreGuestOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $idempotencyKey = $validated['idempotency_key'] ?? null;

        if ($idempotencyKey !== null) {
            $existingOrder = Order::query()
                ->where('source', 'guest-web')
                ->where('idempotency_key', $idempotencyKey)
                ->with(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option'])
                ->first();

            if ($existingOrder !== null) {
                Log::info('guest-order-idempotent-replay', [
                    'order_id' => $existingOrder->id,
                    'idempotency_key' => $idempotencyKey,
                    'ip' => $request->ip(),
                ]);

                return response()->json($existingOrder, 200);
            }
        }

        $lockKey = 'guest-order:'.sha1((string) ($idempotencyKey ?? json_encode([
            'customer_id' => $validated['customer_id'],
            'total' => $validated['total'],
            'items' => $validated['items'],
        ], JSON_THROW_ON_ERROR)));

        $lock = Cache::lock($lockKey, 15);

        if (! $lock->get()) {
            Log::warning('guest-order-duplicate-blocked', [
                'ip' => $request->ip(),
                'idempotency_key' => $idempotencyKey,
            ]);

            return response()->json([
                'message' => 'This order is already being processed.',
            ], 409);
        }

        try {
            $pendingStatusId = Status::query()
                ->where('name', 'pending')
                ->value('id') ?? 1;

            $order = DB::transaction(function () use ($validated, $pendingStatusId, $idempotencyKey) {
                $order = Order::query()->create([
                    'number' => $validated['number'] ?? null,
                    'customer_id' => $validated['customer_id'],
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
                    'source' => 'guest-web',
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

        Log::info('guest-order-created', [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'ip' => $request->ip(),
            'item_count' => count($validated['items']),
        ]);

        return response()->json(
            $order->load(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']),
            201
        );
    }
}
