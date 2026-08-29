<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\StoreGuestRegistrationRequest;
use App\Models\Customer;
use App\Models\User;
use App\Services\Referrals\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class GuestRegistrationController extends Controller
{
    #[OA\Post(
        path: '/guest/register',
        summary: 'Register a customer account',
        description: 'Create a customer user account and optionally apply a strict referral code during registration.',
        tags: ['Guest'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstname', 'lastname', 'email', 'password', 'password_confirmation', 'device_name'],
                properties: [
                    new OA\Property(property: 'firstname', type: 'string', example: 'Maya'),
                    new OA\Property(property: 'lastname', type: 'string', example: 'Joseph'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maya@example.com'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '2645550000'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'itsawrapweb'),
                    new OA\Property(property: 'referral_code', type: 'string', nullable: true, example: 'SUNRA482'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Customer account created'),
            new OA\Response(response: 422, description: 'Validation error or invalid referral code'),
        ]
    )]
    public function store(StoreGuestRegistrationRequest $request, ReferralService $referrals): JsonResponse
    {
        $validated = $request->validated();
        $referralCode = $referrals->normalizeCode($validated['referral_code'] ?? null);

        [$user, $customer, $referral] = DB::transaction(function () use ($validated, $referrals, $referralCode) {
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

            $referrals->ensureCodeForUser($user);
            $referral = $referrals->recordRegistrationReferral($user, $referralCode);

            return [$user, $customer, $referral];
        });

        return response()->json([
            'user' => $user,
            'customer' => $customer,
            'token' => $user->createToken($validated['device_name'])->plainTextToken,
            'referral' => [
                'accepted' => $referral !== null,
                'code' => $referral !== null ? $referralCode : null,
            ],
        ], 201);
    }
}
