<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Referrals\ReferralService;
use App\Services\Rewards\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CustomerRewardController extends Controller
{
    #[OA\Get(
        path: '/me/rewards',
        summary: "Get the authenticated customer's reward summary",
        description: 'Get current reward progress and available rewards for the logged-in customer.',
        tags: ['Rewards'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer reward summary',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerRewardSummary')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Customer access required'),
        ]
    )]
    public function me(Request $request, RewardService $rewards, ReferralService $referrals): JsonResponse
    {
        $customer = $request->user()->customer;

        if ($customer === null) {
            return response()->json(['message' => 'No customer profile found for this account.'], 404);
        }

        return response()->json($this->combinedSummary($customer, $request->user(), $rewards, $referrals));
    }

    #[OA\Get(
        path: '/customers/{customer}/rewards',
        summary: 'Get customer reward summary',
        description: 'Get current reward progress and available rewards for a customer.',
        tags: ['Rewards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                required: true,
                description: 'Customer ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer reward summary',
                content: new OA\JsonContent(ref: '#/components/schemas/CustomerRewardSummary')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Customer not found'),
        ]
    )]
    public function show(Customer $customer, RewardService $rewards, ReferralService $referrals): JsonResponse
    {
        return response()->json($this->combinedSummary($customer, $customer->user, $rewards, $referrals));
    }

    /**
     * @return array<string, mixed>
     */
    private function combinedSummary(Customer $customer, ?\App\Models\User $user, RewardService $rewards, ReferralService $referrals): array
    {
        $purchaseSummary = $rewards->summaryForCustomer($customer);
        $referralSummary = $user !== null ? $referrals->summaryForUser($user) : null;
        $purchaseRewardsAvailable = collect($purchaseSummary['programs'] ?? [])->sum('rewards_available');

        return $purchaseSummary + [
            'purchase_loyalty' => [
                'programs' => $purchaseSummary['programs'] ?? [],
            ],
            'referral_loyalty' => $referralSummary,
            'total_rewards_available' => $purchaseRewardsAvailable + (int) ($referralSummary['rewards_available'] ?? 0),
        ];
    }
}
