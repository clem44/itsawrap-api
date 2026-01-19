<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CashSessionController extends Controller
{
    #[OA\Get(
        path: "/sessions",
        summary: "List all cash sessions",
        description: "Get all cash sessions with optional filtering",
        tags: ["Cash Sessions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "is_open", in: "query", required: false, description: "Filter by open/closed status", schema: new OA\Schema(type: "boolean")),
            new OA\Parameter(name: "user_id", in: "query", required: false, description: "Filter by user", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of cash sessions", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/CashSession"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = CashSession::with('user');

        if ($request->has('is_open')) {
            $query->where('is_open', $request->boolean('is_open'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->orderByDesc('opened_at')->get());
    }

    #[OA\Post(
        path: "/sessions",
        summary: "Open a cash session",
        description: "Open a new cash session for the current user",
        tags: ["Cash Sessions"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "opening_amount", type: "number", example: 100.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Session opened", content: new OA\JsonContent(ref: "#/components/schemas/CashSession")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_amount' => 'numeric|min:0',
        ]);

        $session = CashSession::create([
            'user_id' => $request->user()->id,
            'opening_amount' => $validated['opening_amount'] ?? 0,
            'opened_at' => now(),
        ]);

        return response()->json($session->load('user'), 201);
    }

    #[OA\Get(
        path: "/sessions/{id}",
        summary: "Get a cash session",
        description: "Get a single cash session with details",
        tags: ["Cash Sessions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Cash Session ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Cash session details", content: new OA\JsonContent(ref: "#/components/schemas/CashSession")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Session not found")
        ]
    )]
    public function show(CashSession $cashSession): JsonResponse
    {
        return response()->json($cashSession->load(['user', 'orders', 'withdrawals']));
    }

    #[OA\Post(
        path: "/sessions/{id}/close",
        summary: "Close a cash session",
        description: "Close an open cash session",
        tags: ["Cash Sessions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Cash Session ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["closing_amount"],
                properties: [
                    new OA\Property(property: "closing_amount", type: "number", example: 500.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Session closed", content: new OA\JsonContent(ref: "#/components/schemas/CashSession")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Session not found"),
            new OA\Response(response: 422, description: "Session already closed")
        ]
    )]
    public function close(Request $request, CashSession $cashSession): JsonResponse
    {
        if (!$cashSession->is_open) {
            return response()->json(['message' => 'Session is already closed'], 422);
        }

        $validated = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
        ]);

        $cashSession->update([
            'closing_amount' => $validated['closing_amount'],
            'is_open' => false,
            'closed_at' => now(),
        ]);

        return response()->json($cashSession->load('user'));
    }

    #[OA\Patch(
        path: "/sessions/{id}/totals",
        summary: "Update cash session totals",
        description: "Update running totals for an open cash session",
        tags: ["Cash Sessions"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Cash Session ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "expected_amount", type: "number", example: 150.00),
                    new OA\Property(property: "total_sales", type: "number", example: 350.00),
                    new OA\Property(property: "total_tips", type: "number", example: 25.00),
                    new OA\Property(property: "total_service_charge", type: "number", example: 10.00),
                    new OA\Property(property: "total_withdrawals", type: "number", example: 40.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Totals updated", content: new OA\JsonContent(ref: "#/components/schemas/CashSession")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Session not found"),
            new OA\Response(response: 422, description: "Session already closed")
        ]
    )]
    public function updateTotals(Request $request, CashSession $cashSession): JsonResponse
    {
        if (!$cashSession->is_open) {
            return response()->json(['message' => 'Session is already closed'], 422);
        }

        $validated = $request->validate([
            'expected_amount' => 'numeric|min:0',
            'total_sales' => 'numeric|min:0',
            'total_tips' => 'numeric|min:0',
            'total_service_charge' => 'numeric|min:0',
            'total_withdrawals' => 'numeric|min:0',
        ]);

        $cashSession->update($validated);

        return response()->json($cashSession->load('user'));
    }

    #[OA\Get(
        path: "/sessions/current",
        summary: "Get current user's open session",
        description: "Get the currently open cash session for the authenticated user",
        tags: ["Cash Sessions"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Current session (or null if none open)", content: new OA\JsonContent(ref: "#/components/schemas/CashSession")),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function current(Request $request): JsonResponse
    {
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('is_open', true)
            ->first();

        return response()->json($session?->load(['user', 'orders', 'withdrawals']));
    }
}
