<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\StoreGuestRegistrationRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GuestRegistrationController extends Controller
{
    public function store(StoreGuestRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        [$user, $customer] = DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'username' => $validated['email'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role_id' => 3, // customer
            ]);

            $customer = Customer::query()->create([
                'user_id' => $user->id,
                'name' => trim($validated['firstname'].' '.$validated['lastname']),
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'source' => 'web-customer',
            ]);

            return [$user, $customer];
        });

        return response()->json([
            'user' => $user,
            'customer' => $customer,
            'token' => $user->createToken($validated['device_name'])->plainTextToken,
        ], 201);
    }
}
