<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class GuestSettingController extends Controller
{
    private const PUBLIC_KEYS = [
        'service_charge',
        'delivery_fee',
        'open_time',
        'close_time',
        'business_phone',
        'business_address',
        'currency',
        'selected_branch_id',
    ];

    #[OA\Get(
        path: '/guest/settings/{key}',
        summary: 'Get a public setting',
        description: 'Returns the value of an allowlisted setting that is safe to expose to customers.',
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'key',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', enum: self::PUBLIC_KEYS),
                example: 'service_charge'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Public setting value',
                content: new OA\JsonContent(
                    required: ['value'],
                    properties: [new OA\Property(property: 'value', type: 'string', example: '0')]
                )
            ),
            new OA\Response(response: 404, description: 'Setting not found'),
        ]
    )]
    public function __invoke(string $key): JsonResponse
    {
        $value = Setting::query()
            ->whereIn('key', self::PUBLIC_KEYS)
            ->where('key', $key)
            ->value('value');

        abort_if($value === null, 404);

        return response()->json(['value' => $value]);
    }
}
