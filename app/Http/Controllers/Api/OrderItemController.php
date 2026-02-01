<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItemOption;
use OpenApi\Attributes as OA;

class OrderItemController extends Controller
{
    #[OA\Get(
        path: "/order-items",
        summary: "List order items",
        description: "Get all order items with optional filtering by order",
        tags: ["Order Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "order_id", in: "query", required: false, description: "Filter by order", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of order items", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/OrderItem"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OrderItem::with(['item', 'orderItemOptions.optionValue']);

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/order-items",
        summary: "Create an order item",
        description: "Add a new item to an existing order",
        tags: ["Order Items"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["order_id", "item_id", "price"],
                properties: [
                    new OA\Property(property: "order_id", type: "integer", example: 1),
                    new OA\Property(property: "item_id", type: "integer", example: 1),
                    new OA\Property(property: "price", type: "number", example: 12.99),
                    new OA\Property(property: "quantity", type: "integer", example: 1),
                    new OA\Property(property: "comment", type: "string", nullable: true),
                    new OA\Property(
                        property: "options",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "option_value_id", type: "integer"),
                                new OA\Property(property: "price", type: "number"),
                                new OA\Property(property: "qty", type: "integer", nullable: true),
                                new OA\Property(property: "parent_option_value_id", type: "integer", nullable: true),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Order item created", content: new OA\JsonContent(ref: "#/components/schemas/OrderItem")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'item_id' => 'required|exists:items,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'integer|min:1',
            'comment' => 'nullable|string',
            'options' => 'array',
            'options.*.option_value_id' => 'required|exists:option_values,id',
            'options.*.price' => 'numeric|min:0',
            'options.*.qty' => 'nullable|integer|min:1',
            'options.*.parent_option_value_id' => 'nullable|exists:option_values,id',
        ]);



        DB::listen(function ($query) {
            if (str_contains($query->sql, 'order_item_options')) {
                /*Log::info('OrderItemController.sql order_item_options', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                ]);*/
            }
        });

        $optionModel = new \App\Models\OrderItemOption();
        /*Log::info('OrderItemController.fillable', [
            'fillable' => $optionModel->getFillable(),
            'is_fillable_parent' => $optionModel->isFillable('parent_option_value_id'),
        ]);*/
        if (isset($validated['options'])) {
            /*Log::info('OrderItemController.store received options', [
                'database' => DB::connection()->getDatabaseName(),
                'order_id' => $validated['order_id'] ?? null,
                'item_id' => $validated['item_id'] ?? null,
                'options' => collect($validated['options'])->map(function ($option) {
                    return [
                        'option_value_id' => $option['option_value_id'] ?? null,
                        'parent_option_value_id' => $option['parent_option_value_id'] ?? null,
                        'price' => $option['price'] ?? null,
                        'qty' => $option['qty'] ?? null,
                    ];
                })->values()->all(),
            ]);*/
        }

        $orderItem = OrderItem::create([
            'order_id' => $validated['order_id'],
            'item_id' => $validated['item_id'],
            'price' => $validated['price'],
            'quantity' => $validated['quantity'] ?? 1,
            'comment' => $validated['comment'] ?? null,
        ]);

        if (isset($validated['options'])) {
            foreach ($validated['options'] as $optionData) {
                $optionPayload = [
                    'option_value_id' => $optionData['option_value_id'],
                    'price' => $optionData['price'] ?? 0,
                    'qty' => $optionData['qty'] ?? null,
                    'parent_option_value_id' => $optionData['parent_option_value_id'] ?? null,
                ];
                /*Log::info('OrderItemController.store option payload', [
                    'order_item_id' => $orderItem->id,
                    'payload' => $optionPayload,
                ]);*/
                $createdOption = $orderItem->orderItemOptions()->create($optionPayload);
                $dbParentOptionValueId = DB::table('order_item_options')
                    ->where('id', $createdOption->id)
                    ->value('parent_option_value_id');
                /*Log::info('OrderItemController.store saved option', [
                    'order_item_id' => $orderItem->id,
                    'option_value_id' => $createdOption->option_value_id,
                    'parent_option_value_id' => $createdOption->parent_option_value_id,
                    'db_parent_option_value_id' => $dbParentOptionValueId,
                ]);*/
            }
        }

        return response()->json(
            $orderItem->load(['item', 'orderItemOptions.optionValue']),
            201
        );
    }

    #[OA\Get(
        path: "/order-items/{id}",
        summary: "Get an order item",
        description: "Get a single order item with its options",
        tags: ["Order Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Order Item ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Order item details", content: new OA\JsonContent(ref: "#/components/schemas/OrderItem")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order item not found")
        ]
    )]
    public function show(OrderItem $orderItem): JsonResponse
    {
        return response()->json($orderItem->load(['item', 'orderItemOptions.optionValue']));
    }

    #[OA\Put(
        path: "/order-items/{id}",
        summary: "Update an order item",
        description: "Update an order item's quantity or price",
        tags: ["Order Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Order Item ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "price", type: "number"),
                    new OA\Property(property: "quantity", type: "integer"),
                    new OA\Property(property: "comment", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Order item updated", content: new OA\JsonContent(ref: "#/components/schemas/OrderItem")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order item not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, OrderItem $orderItem): JsonResponse
    {
        $validated = $request->validate([
            'price' => 'numeric|min:0',
            'quantity' => 'integer|min:1',
            'comment' => 'nullable|string',
        ]);

        $orderItem->update($validated);

        return response()->json($orderItem->load(['item', 'orderItemOptions.optionValue']));
    }

    #[OA\Delete(
        path: "/order-items/{id}",
        summary: "Delete an order item",
        description: "Remove an item from an order",
        tags: ["Order Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Order Item ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Order item deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order item not found")
        ]
    )]
    public function destroy(OrderItem $orderItem): JsonResponse
    {
        // Delete associated options first
        $orderItem->orderItemOptions()->delete();

        $orderItem->delete();

        return response()->json(null, 204);
    }
}
