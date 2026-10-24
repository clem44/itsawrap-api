<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CustomerBranchController extends Controller
{
    #[OA\Get(
        path: '/me/branches',
        summary: 'List active branches',
        description: 'Minimal branch list the customer apps use to resolve which branch to attach to an order (e.g. selecting the branch named "Main").',
        tags: ['Customer'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Active branches', content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                ])
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $branches = Branch::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($branches);
    }
}
