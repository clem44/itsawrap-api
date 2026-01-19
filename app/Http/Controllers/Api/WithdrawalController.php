<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WithdrawalController extends Controller
{
    #[OA\Get(
        path: "/withdrawals",
        summary: "List all withdrawals",
        description: "Get all withdrawals with optional filtering",
        tags: ["Withdrawals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "session_id", in: "query", required: false, description: "Filter by cash session", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "user_id", in: "query", required: false, description: "Filter by user", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of withdrawals", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Withdrawal"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Withdrawal::with(['session', 'user']);

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    #[OA\Post(
        path: "/withdrawals",
        summary: "Create a withdrawal",
        description: "Record a new withdrawal from the current cash session",
        tags: ["Withdrawals"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Bank deposit"),
                    new OA\Property(property: "amount", type: "number", example: 50.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Withdrawal created", content: new OA\JsonContent(ref: "#/components/schemas/Withdrawal")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "No open session or validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);

        // Get current open session for the user
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('is_open', true)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'No open session found'], 422);
        }

        $withdrawal = Withdrawal::create([
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'session_id' => $session->id,
            'user_id' => $request->user()->id,
        ]);

        $session->increment('total_withdrawals', $withdrawal->amount);

        return response()->json($withdrawal->load(['session', 'user']), 201);
    }

    #[OA\Get(
        path: "/withdrawals/{id}",
        summary: "Get a withdrawal",
        description: "Get a single withdrawal with details",
        tags: ["Withdrawals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Withdrawal ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Withdrawal details", content: new OA\JsonContent(ref: "#/components/schemas/Withdrawal")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Withdrawal not found")
        ]
    )]
    public function show(Withdrawal $withdrawal): JsonResponse
    {
        return response()->json($withdrawal->load(['session', 'user']));
    }

    #[OA\Delete(
        path: "/withdrawals/{id}",
        summary: "Delete a withdrawal",
        description: "Delete a withdrawal and adjust session totals",
        tags: ["Withdrawals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Withdrawal ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Withdrawal deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Withdrawal not found")
        ]
    )]
    public function destroy(Withdrawal $withdrawal): JsonResponse
    {
        $session = $withdrawal->session;
        $session->decrement('total_withdrawals', $withdrawal->amount);

        $withdrawal->delete();

        return response()->json(null, 204);
    }
}
