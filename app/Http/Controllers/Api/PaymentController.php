<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    #[OA\Get(
        path: "/payments",
        summary: "List all payments",
        description: "Get all payments with optional filtering",
        tags: ["Payments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "order_id", in: "query", required: false, description: "Filter by order", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "status", in: "query", required: false, description: "Filter by status", schema: new OA\Schema(type: "string", enum: ["pending", "completed", "failed", "refunded"])),
            new OA\Parameter(name: "method", in: "query", required: false, description: "Filter by payment method", schema: new OA\Schema(type: "string", enum: ["cash", "card", "mobile", "other"])),
            new OA\Parameter(name: "session_id", in: "query", required: false, description: "Filter by session via order", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of payments", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Payment"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with('order');

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        if ($request->has('session_id')) {
            $sessionId = $request->input('session_id');
            $query->whereHas('order', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    #[OA\Post(
        path: "/payments",
        summary: "Create a payment",
        description: "Record a new payment for an order",
        tags: ["Payments"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["order_id", "amount"],
                properties: [
                    new OA\Property(property: "order_id", type: "integer", example: 1),
                    new OA\Property(property: "amount", type: "number", example: 28.13),
                    new OA\Property(property: "method", type: "string", enum: ["cash", "card", "mobile", "other"], example: "cash"),
                    new OA\Property(property: "status", type: "string", enum: ["pending", "completed", "failed", "refunded"], example: "completed"),
                    new OA\Property(property: "type", type: "string", nullable: true),
                    new OA\Property(property: "charges", type: "object", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Payment created", content: new OA\JsonContent(ref: "#/components/schemas/Payment")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'string|in:cash,card,mobile,other',
            'status' => 'string|in:pending,completed,failed,refunded',
            'type' => 'nullable|string',
            'charges' => 'nullable|array',
        ]);

        $payment = Payment::create($validated);

        return response()->json($payment->load('order'), 201);
    }

    #[OA\Get(
        path: "/payments/{id}",
        summary: "Get a payment",
        description: "Get a single payment with its order",
        tags: ["Payments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Payment ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Payment details", content: new OA\JsonContent(ref: "#/components/schemas/Payment")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Payment not found")
        ]
    )]
    public function show(Payment $payment): JsonResponse
    {
        return response()->json($payment->load('order'));
    }

    #[OA\Put(
        path: "/payments/{id}",
        summary: "Update a payment",
        description: "Update a payment status",
        tags: ["Payments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Payment ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["pending", "completed", "failed", "refunded"]),
                    new OA\Property(property: "charges", type: "object", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Payment updated", content: new OA\JsonContent(ref: "#/components/schemas/Payment")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Payment not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'string|in:pending,completed,failed,refunded',
            'charges' => 'nullable|array',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    #[OA\Delete(
        path: "/payments/{id}",
        summary: "Delete a payment",
        description: "Delete a payment",
        tags: ["Payments"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Payment ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Payment deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Payment not found")
        ]
    )]
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json(null, 204);
    }
}
