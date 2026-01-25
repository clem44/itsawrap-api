<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[OA\Get(
        path: "/orders",
        summary: "List all orders",
        description: "Get all orders with optional filtering",
        tags: ["Orders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "status_id", in: "query", required: false, description: "Filter by status", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "status_ids", in: "query", required: false, description: "Filter by multiple statuses (comma-separated or array)", schema: new OA\Schema(type: "array", items: new OA\Items(type: "integer"))),
            new OA\Parameter(name: "session_id", in: "query", required: false, description: "Filter by cash session", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "customer_id", in: "query", required: false, description: "Filter by customer", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "date", in: "query", required: false, description: "Filter by date (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of orders", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Order"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue']);

        if ($request->has('status_ids')) {
            $statusIds = $request->input('status_ids', []);
            if (is_string($statusIds)) {
                $statusIds = array_filter(explode(',', $statusIds));
            }
            if (is_array($statusIds) && !empty($statusIds)) {
                $query->whereIn('status_id', $statusIds);
            }
        } elseif ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    #[OA\Post(
        path: "/orders",
        summary: "Create an order",
        description: "Create a new order with items",
        tags: ["Orders"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status_id", "subtotal", "total"],
                properties: [
                    new OA\Property(property: "number", type: "string", nullable: true, example: "ORD-001"),
                    new OA\Property(property: "customer_id", type: "integer", nullable: true),
                    new OA\Property(property: "status_id", type: "integer", example: 1),
                    new OA\Property(property: "subtotal", type: "number", example: 25.99),
                    new OA\Property(property: "discount", type: "number", nullable: true, example: 0),
                    new OA\Property(property: "discount_percent", type: "number", nullable: true, example: 0),
                    new OA\Property(property: "service_charge", type: "number", example: 0),
                    new OA\Property(property: "total", type: "number", example: 25.99),
                    new OA\Property(property: "comments", type: "string", nullable: true),
                    new OA\Property(property: "is_delivery", type: "boolean", example: false),
                    new OA\Property(property: "is_reward", type: "boolean", example: false),
                    new OA\Property(
                        property: "items",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "item_id", type: "integer"),
                                new OA\Property(property: "price", type: "number"),
                                new OA\Property(property: "quantity", type: "integer"),
                                new OA\Property(
                                    property: "options",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "option_value_id", type: "integer"),
                                            new OA\Property(property: "price", type: "number"),
                                            new OA\Property(property: "qty", type: "integer", nullable: true, example: 1),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Order created", content: new OA\JsonContent(ref: "#/components/schemas/Order")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'status_id' => 'integer|exists:statuses,id',
            'subtotal' => 'numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'service_charge' => 'numeric|min:0',
            'total' => 'numeric|min:0',
            'comments' => 'nullable|string',
            'is_delivery' => 'boolean',
            'is_reward' => 'boolean',
            'items' => 'array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'integer|min:1',
            'items.*.options' => 'array',
            'items.*.options.*.option_value_id' => 'required|exists:option_values,id',
            'items.*.options.*.price' => 'numeric|min:0',
            'items.*.options.*.qty' => 'nullable|integer|min:1',
        ]);

        // Get current open session for the user
        $session = CashSession::where('user_id', $request->user()->id)
            ->where('is_open', true)
            ->first();

        $validated['session_id'] = $session?->id;

        $order = Order::create($validated);

        if (isset($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $orderItem = $order->orderItems()->create([
                    'item_id' => $itemData['item_id'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'] ?? 1,
                ]);

                if (isset($itemData['options'])) {
                    foreach ($itemData['options'] as $optionData) {
                        $orderItem->orderItemOptions()->create([
                            'option_value_id' => $optionData['option_value_id'],
                            'price' => $optionData['price'] ?? 0,
                            'qty' => $optionData['qty'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Update session totals
        if ($session) {
            $session->increment('total_sales', $order->total);
            $session->increment('total_service_charge', $order->service_charge);
        }

        return response()->json(
            $order->load(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue']),
            201
        );
    }

    #[OA\Get(
        path: "/orders/{id}",
        summary: "Get an order",
        description: "Get a single order with all details",
        tags: ["Orders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Order ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Order details", content: new OA\JsonContent(ref: "#/components/schemas/Order")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order not found")
        ]
    )]
    public function show(Order $order): JsonResponse
    {
        return response()->json(
            $order->load(['customer', 'status', 'session', 'orderItems.item', 'orderItems.orderItemOptions.optionValue', 'payments', 'tips'])
        );
    }

    #[OA\Put(
        path: "/orders/{id}",
        summary: "Update an order",
        description: "Update an existing order",
        tags: ["Orders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Order ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status_id", type: "integer"),
                    new OA\Property(property: "comments", type: "string", nullable: true),
                    new OA\Property(property: "discount", type: "number", nullable: true),
                    new OA\Property(property: "discount_percent", type: "number", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Order updated", content: new OA\JsonContent(ref: "#/components/schemas/Order")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status_id' => 'integer|exists:statuses,id',
            'comments' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $order->update($validated);

        return response()->json($order->load(['customer', 'status']));
    }

    #[OA\Delete(
        path: "/orders/{id}",
        summary: "Delete an order",
        description: "Delete an order",
        tags: ["Orders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Order ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Order deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order not found")
        ]
    )]
    public function destroy(Order $order): JsonResponse
    {
        $order->delete();

        return response()->json(null, 204);
    }
}
