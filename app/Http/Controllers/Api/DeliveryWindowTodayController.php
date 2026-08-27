<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Delivery\DeliveryScheduler;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DeliveryWindowTodayController extends Controller
{
    #[OA\Get(
        path: '/guest/delivery-windows/today',
        summary: "List today's delivery windows",
        description: "Get today's active delivery windows with availability flags.",
        tags: ['Delivery'],
        responses: [
            new OA\Response(response: 200, description: 'Delivery windows for today'),
        ]
    )]
    #[OA\Get(
        path: '/me/delivery-windows/today',
        summary: "List today's customer delivery windows",
        description: "Get today's active delivery windows with availability flags for an authenticated customer.",
        tags: ['Delivery'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Delivery windows for today'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Customer access required'),
        ]
    )]
    public function __invoke(DeliveryScheduler $deliveryScheduler): JsonResponse
    {
        return response()->json([
            'delivery_windows' => $deliveryScheduler->today()->values(),
        ]);
    }
}
