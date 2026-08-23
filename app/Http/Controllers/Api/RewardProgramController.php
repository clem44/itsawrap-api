<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RewardProgram;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RewardProgramController extends Controller
{
    #[OA\Get(
        path: '/rewards/programs',
        summary: 'List active reward programs',
        description: 'Get active customer reward programs with their earn and reward categories.',
        tags: ['Rewards'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active reward programs',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RewardProgram')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(
            RewardProgram::query()
                ->currentlyActive()
                ->with(['earnCategory', 'rewardCategory'])
                ->orderBy('name')
                ->get()
        );
    }
}
