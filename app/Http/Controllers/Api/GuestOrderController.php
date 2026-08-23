<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\StoreGuestOrderRequest;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;

class GuestOrderController extends Controller
{
    public function store(StoreGuestOrderRequest $request, OrderCreationService $orders): JsonResponse
    {
        $validated = $request->validated();

        $order = $orders->create($validated, (int) $validated['customer_id'], 'guest-web', $request->ip());

        return response()->json($order, $order->wasRecentlyCreated ? 201 : 200);
    }
}
