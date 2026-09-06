<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Support\Bundles\BundlePresenter;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BundleController extends Controller
{
    #[OA\Get(
        path: '/guest/bundles',
        summary: 'List active bundles',
        description: 'Returns currently active food bundles for customer ordering surfaces.',
        tags: ['Bundles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active bundles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'bundles',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Bundle')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    #[OA\Get(
        path: '/me/bundles',
        summary: 'List active bundles',
        description: 'Returns currently active food bundles for signed-in customer ordering surfaces.',
        tags: ['Bundles'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active bundles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'bundles',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Bundle')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    #[OA\Get(
        path: '/bundles',
        summary: 'List active bundles',
        description: 'Returns currently active food bundles for POS clients.',
        tags: ['Bundles'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active bundles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'bundles',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Bundle')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(BundlePresenter $presenter): JsonResponse
    {
        $bundles = Bundle::query()
            ->availableForOrdering()
            ->withMedia(Bundle::IMAGE_TAG)
            ->with(Bundle::apiRelations())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Bundle $bundle) => $presenter->present($bundle))
            ->values();

        return response()->json(['bundles' => $bundles]);
    }
}
