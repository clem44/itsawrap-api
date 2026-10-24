<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\UpdateCustomerPasswordRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerProfileRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class CustomerProfileController extends Controller
{
    #[OA\Patch(
        path: '/me/profile',
        summary: 'Update the authenticated customer profile',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'firstname', type: 'string', example: 'Jordan'),
                    new OA\Property(property: 'lastname', type: 'string', example: 'Baptiste'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '264-555-0199'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'firstname', type: 'string', example: 'Jordan'),
                    new OA\Property(property: 'lastname', type: 'string', example: 'Baptiste'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '264-555-0199'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Customer access required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCustomerProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $profile = DB::transaction(function () use ($user, $validated): array {
            $user->update(array_intersect_key($validated, array_flip(['firstname', 'lastname'])));

            $customer = Customer::query()->firstOrNew(['user_id' => $user->id]);
            $customer->fill([
                'name' => trim($user->firstname.' '.$user->lastname),
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $customer->phone,
                'email' => $user->email,
                'source' => $customer->source ?? 'web-customer',
            ])->save();

            return [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $customer->phone,
            ];
        });

        return response()->json($profile);
    }

    #[OA\Put(
        path: '/me/password',
        summary: 'Update the authenticated customer password',
        tags: ['Customers'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully.'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Customer access required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updatePassword(UpdateCustomerPasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
