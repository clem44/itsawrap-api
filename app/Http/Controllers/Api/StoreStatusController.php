<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Store\StoreAvailability;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class StoreStatusController extends Controller
{
    #[OA\Get(
        path: '/guest/store-status',
        summary: 'Get whether the store is currently taking orders',
        description: 'Public check of opening hours and till staffing, for gating guest checkout. Exposes no session or sales detail.',
        tags: ['Settings'],
        responses: [
            new OA\Response(response: 200, description: 'Store status'),
        ]
    )]
    public function __invoke(StoreAvailability $availability): JsonResponse
    {
        return response()->json([
            'is_open' => $availability->isOpen(),
            'open_time' => $availability->openTime(),
            'close_time' => $availability->closeTime(),
        ]);
    }
}
