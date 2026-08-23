<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
    public function me(Request $request, RewardService $rewards): JsonResponse
    {
        $customer = $request->user()->customer;

        if ($customer === null) {
            return response()->json(['message' => 'No customer profile found for this account.'], 404);
        }

        return response()->json($rewards->summaryForCustomer($customer));
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
    public function show(Customer $customer, RewardService $rewards): JsonResponse
    {
        return response()->json($rewards->summaryForCustomer($customer));
    }
}
