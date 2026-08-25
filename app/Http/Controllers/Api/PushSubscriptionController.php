<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Push\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class PushSubscriptionController extends Controller
{
    #[OA\Post(
        path: '/push-subscriptions',
        summary: 'Register or refresh a push subscription',
        description: 'Stores the authenticated device Firebase registration token for later push delivery.',
        tags: ['Push Notifications'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'platform', 'app_context'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'firebase-fcm-registration-token'),
                    new OA\Property(property: 'platform', type: 'string', enum: ['ios', 'android', 'web'], example: 'ios'),
                    new OA\Property(property: 'app_context', type: 'string', enum: ['pos', 'admin', 'customer'], example: 'pos'),
                    new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'Kitchen iPad'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Push subscription created', content: new OA\JsonContent(ref: '#/components/schemas/PushSubscription')),
            new OA\Response(response: 200, description: 'Push subscription refreshed', content: new OA\JsonContent(ref: '#/components/schemas/PushSubscription')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $token = (string) $validated['token'];

        $subscription = PushSubscription::query()->updateOrCreate(
            ['token_hash' => hash('sha256', $token)],
            [
                'user_id' => $request->user()->id,
                'provider' => PushSubscription::PROVIDER_FIREBASE,
                'token' => $token,
                'platform' => $validated['platform'],
                'app_context' => $validated['app_context'],
                'device_name' => $validated['device_name'] ?? null,
                'personal_access_token_id' => $this->currentAccessTokenId($request),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]
        );

        return response()->json($subscription->refresh(), $subscription->wasRecentlyCreated ? 201 : 200);
    }

    #[OA\Delete(
        path: '/push-subscriptions/{pushSubscription}',
        summary: 'Revoke a push subscription',
        description: 'Soft-revokes a push subscription. Users can revoke their own subscriptions; admins can revoke any subscription.',
        tags: ['Push Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'pushSubscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Push subscription revoked'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Push subscription not found'),
        ]
    )]
    public function destroy(Request $request, PushSubscription $pushSubscription): JsonResponse
    {
        if ($pushSubscription->user_id !== $request->user()->id && (int) $request->user()->role_id !== 1) {
            abort(403);
        }

        $pushSubscription->markRevoked();

        return response()->json(null, 204);
    }

    private function currentAccessTokenId(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return null;
        }

        return $token->id > 0 ? $token->id : null;
    }
}
