<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\ReferralProgram;
use App\Services\Referrals\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class OrderReferralRewardController extends Controller
{
    #[OA\Post(
        path: '/orders/{order}/referral-rewards/redeem',
        summary: 'Redeem a customer referral reward on an order',
        description: 'Add a free referral reward item to an order and decrement the customer referral reward balance.',
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
                required: ['referral_program_id', 'item_id'],
                properties: [
                    new OA\Property(property: 'referral_program_id', type: 'integer', example: 1),
                    new OA\Property(property: 'item_id', type: 'integer', example: 10),
                    new OA\Property(property: 'quantity', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Referral reward item added to order',
                content: new OA\JsonContent(ref: '#/components/schemas/OrderItem')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order, referral program, or item not found'),
            new OA\Response(response: 422, description: 'Validation error or unavailable referral reward'),
        ]
    )]
    public function store(Request $request, Order $order, ReferralService $referrals): JsonResponse
    {
        $validated = $request->validate([
            'referral_program_id' => 'required|exists:referral_programs,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $program = ReferralProgram::query()->findOrFail($validated['referral_program_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);

        if ($quantity > $program->reward_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "The quantity may not be greater than {$program->reward_quantity} for this referral reward.",
            ]);
        }

        $orderItem = DB::transaction(function () use ($order, $program, $referrals, $request, $validated, $quantity) {
            $item = Item::query()->findOrFail($validated['item_id']);

            $orderItem = $order->orderItems()->create([
                'item_id' => $item->id,
                'price' => $item->cost,
                'quantity' => $quantity,
                'is_reward_item' => true,
                'referral_program_id' => $program->id,
                'reward_discount_amount' => (float) $item->cost * $quantity,
            ]);

            $referrals->redeemReferralReward($order, $orderItem, $program, $request->user());

            return $orderItem->refresh();
        });

        return response()->json(
            $orderItem->load(['item', 'referralProgram', 'referralLedgerEntry']),
            201
        );
    }
}
