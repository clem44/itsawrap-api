<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\StoreCustomerOrderRequest;
use App\Models\Customer;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;

class CustomerOrderController extends Controller
{
    public function store(StoreCustomerOrderRequest $request, OrderCreationService $orders): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $customer = Customer::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => trim($user->firstname.' '.$user->lastname),
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'source' => 'web-customer',
            ]
        );

        $order = $orders->create($validated, $customer->id, 'web-customer', $request->ip());

        return response()->json($order, $order->wasRecentlyCreated ? 201 : 200);
    }
}
