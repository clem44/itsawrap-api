<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\RewardProgram;
use App\Services\Rewards\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class OrderRewardController extends Controller
{
    #[OA\Post(
        path: '/orders/{order}/rewards/redeem',
        summary: 'Redeem a customer reward on an order',
        description: 'Add a free reward item to an order and decrement the customer reward balance.',
        tags: ['Rewards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'order',
                in: 'path',
                required: true,
                description: 'Order ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reward_program_id', 'item_id'],
                properties: [
                    new OA\Property(property: 'reward_program_id', type: 'integer', example: 1),
                    new OA\Property(property: 'item_id', type: 'integer', example: 10),
                    new OA\Property(property: 'quantity', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reward item added to order',
                content: new OA\JsonContent(ref: '#/components/schemas/OrderItem')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order, reward program, or item not found'),
            new OA\Response(response: 422, description: 'Validation error or unavailable reward'),
        ]
    )]
    public function store(Request $request, Order $order, RewardService $rewards): JsonResponse
    {
        $validated = $request->validate([
            'reward_program_id' => 'required|exists:reward_programs,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $program = RewardProgram::query()->findOrFail($validated['reward_program_id']);

        $orderItem = DB::transaction(function () use ($order, $program, $rewards, $request, $validated) {
            $item = Item::query()->findOrFail($validated['item_id']);

            $orderItem = $order->orderItems()->create([
                'item_id' => $item->id,
                'price' => $item->cost,
                'quantity' => $validated['quantity'] ?? 1,
                'is_reward_item' => true,
                'reward_program_id' => $program->id,
                'reward_discount_amount' => $item->cost,
            ]);

            $rewards->redeemReward($order, $orderItem, $program, $request->user());

            return $orderItem->refresh();
        });

        return response()->json(
            $orderItem->load(['item', 'rewardProgram', 'rewardLedgerEntry']),
            201
        );
    }
}
