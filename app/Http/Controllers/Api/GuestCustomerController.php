<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\StoreGuestCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class GuestCustomerController extends Controller
{
    public function store(StoreGuestCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::query()->create([
            'name' => $validated['name'],
            'firstname' => $validated['firstname'] ?? null,
            'lastname' => $validated['lastname'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'source' => 'guest-web',
        ]);

        return response()->json($customer, 201);
    }
}
