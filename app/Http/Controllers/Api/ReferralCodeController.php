<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Referrals\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReferralCodeController extends Controller
{
    #[OA\Get(
        path: '/me/referral-code',
        summary: "Get the authenticated customer's referral code",
        description: 'Return the customer referral code and share URL for the logged-in customer.',
        tags: ['Rewards'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer referral code',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'SUNRA482'),
                        new OA\Property(property: 'share_url', type: 'string', example: 'https://itsawrap.ai/ref/SUNRA482'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Customer access required'),
        ]
    )]
    public function show(Request $request, ReferralService $referrals): JsonResponse
    {
        $code = $referrals->ensureCodeForUser($request->user());

        return response()->json([
            'code' => $code->code,
            'share_url' => rtrim((string) config('app.url'), '/').'/ref/'.$code->code,
        ]);
    }
}
