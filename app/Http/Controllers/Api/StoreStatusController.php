<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StoreStatusController extends Controller
{
    #[OA\Get(
        path: '/guest/store-status',
        summary: 'Get whether the store is currently taking orders',
        description: 'Public check of opening hours and till staffing, for gating guest checkout. Exposes no session or sales detail.',
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'branch_id',
                in: 'query',
                required: false,
                description: 'Branch to check. Defaults to the Main branch.',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Store status',
                content: new OA\JsonContent(
                    required: ['branch_id', 'is_open', 'cash_session_open', 'open_time', 'close_time'],
                    properties: [
                        new OA\Property(property: 'branch_id', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'is_open', type: 'boolean', example: true),
                        new OA\Property(property: 'cash_session_open', type: 'boolean', example: true),
                        new OA\Property(property: 'open_time', type: 'string', example: '11:00:00'),
                        new OA\Property(property: 'close_time', type: 'string', example: '21:30:00'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid branch id'),
        ]
    )]
    public function __invoke(Request $request, StoreAvailability $availability): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branchId = $availability->resolveBranchId($validated['branch_id'] ?? null);

        return response()->json([
            'branch_id' => $branchId,
            'is_open' => $availability->isOpen($branchId),
            'cash_session_open' => $availability->hasOpenCashSession($branchId),
            'open_time' => $availability->openTime(),
            'close_time' => $availability->closeTime(),
        ]);
    }
}
