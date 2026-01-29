<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TipController extends Controller
{
    #[OA\Get(
        path: "/tips",
        summary: "List all tips",
        description: "Get all tips with optional filtering",
        tags: ["Tips"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "order_id", in: "query", required: false, description: "Filter by order", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of tips", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Tip"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Tip::with('order');

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    #[OA\Post(
        path: "/tips",
        summary: "Create a tip",
        description: "Record a new tip for an order",
        tags: ["Tips"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["order_id", "amount"],
                properties: [
                    new OA\Property(property: "order_id", type: "integer", example: 1),
                    new OA\Property(property: "amount", type: "number", example: 5.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tip created", content: new OA\JsonContent(ref: "#/components/schemas/Tip")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $tip = Tip::create($validated);

        // Update session totals
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('is_open', true)
            ->first();

        if ($session) {
            $session->increment('total_tips', $tip->amount);
        }

        return response()->json($tip->load('order'), 201);
    }

    #[OA\Get(
        path: "/tips/{id}",
        summary: "Get a tip",
        description: "Get a single tip with its order",
        tags: ["Tips"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Tip ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Tip details", content: new OA\JsonContent(ref: "#/components/schemas/Tip")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Tip not found")
        ]
    )]
    public function show(Tip $tip): JsonResponse
    {
        return response()->json($tip->load('order'));
    }

    #[OA\Put(
        path: "/tips/{id}",
        summary: "Update a tip",
        description: "Update a tip amount",
        tags: ["Tips"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Tip ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(property: "amount", type: "number", example: 5.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Tip updated", content: new OA\JsonContent(ref: "#/components/schemas/Tip")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Tip not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Tip $tip): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $oldAmount = $tip->amount;
        $newAmount = $validated['amount'];
        $difference = $newAmount - $oldAmount;

        // Update session totals if there's an active session
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('is_open', true)
            ->first();

        if ($session && $difference != 0) {
            $session->increment('total_tips', $difference);
        }

        $tip->update($validated);

        return response()->json($tip->load('order'));
    }

    #[OA\Delete(
        path: "/tips/{id}",
        summary: "Delete a tip",
        description: "Delete a tip",
        tags: ["Tips"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Tip ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Tip deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Tip not found")
        ]
    )]
    public function destroy(Request $request, Tip $tip): JsonResponse
    {
        // Decrement session totals
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('is_open', true)
            ->first();

        if ($session) {
            $session->decrement('total_tips', $tip->amount);
        }

        $tip->delete();

        return response()->json(null, 204);
    }
}
