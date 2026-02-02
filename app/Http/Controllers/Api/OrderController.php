<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItemOption;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[OA\Get(
        path: "/orders/history",
        summary: "List order history (summary)",
        description: "Get lightweight order history summaries",
        tags: ["Orders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "status_ids", in: "query", required: false, description: "Filter by multiple statuses (comma-separated or array)", schema: new OA\Schema(type: "array", items: new OA\Items(type: "integer"))),
            new OA\Parameter(name: "session_id", in: "query", required: false, description: "Filter by cash session", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "search", in: "query", required: false, description: "Search by customer or order number", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "date_start", in: "query", required: false, description: "Filter start date (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "date_end", in: "query", required: false, description: "Filter end date (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "limit", in: "query", required: false, description: "Max rows", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, description: "Page number", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of order summaries", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Order"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function history(Request $request): JsonResponse
    {
        $paymentSummarySub = DB::table('payments')
            ->select(
                'order_id',
                DB::raw("MAX(CASE WHEN status IN ('paid','completed') THEN 1 ELSE 0 END) as is_paid"),
                DB::raw("SUM(CASE WHEN status IN ('paid','completed') THEN amount ELSE 0 END) as total_paid"),
                DB::raw('MAX(id) as latest_payment_id')
            )
            ->groupBy('order_id');

        $tipSummarySub = DB::table('tips')
            ->select('order_id', DB::raw('SUM(amount) as tip_amount'))
            ->groupBy('order_id');

        $query = Order::query()
            ->with(['customer' => function ($q) {
                $q->select('id', 'name', 'firstname', 'lastname');
            }])
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->leftJoinSub($paymentSummarySub, 'payment_summary', function ($join) {
                $join->on('orders.id', '=', 'payment_summary.order_id');
            })
            ->leftJoin('payments as payment_latest', 'payment_latest.id', '=', 'payment_summary.latest_payment_id')
            ->leftJoinSub($tipSummarySub, 'tip_summary', function ($join) {
                $join->on('orders.id', '=', 'tip_summary.order_id');
            })
            ->select([
                'orders.id',
                'orders.number',
                'orders.customer_id',
                'orders.status_id',
                'orders.subtotal',
                'orders.discount',
                'orders.discount_percent',
                'orders.service_charge',
                'orders.total',
                'orders.is_delivery',
                'orders.is_reward',
                'orders.session_id',
                'orders.created_at',
                'orders.updated_at',
                DB::raw('COALESCE(payment_summary.is_paid, 0) as is_paid'),
                DB::raw('COALESCE(payment_summary.total_paid, 0) as total_paid'),
                'payment_latest.method as payment_method',
                'payment_latest.status as payment_status',
                DB::raw('COALESCE(tip_summary.tip_amount, 0) as tip_amount'),
            ]);

        if ($request->has('status_ids')) {
            $statusIds = $request->input('status_ids', []);
            if (is_string($statusIds)) {
                $statusIds = array_filter(explode(',', $statusIds));
            }
            if (is_array($statusIds) && !empty($statusIds)) {
                $query->whereIn('orders.status_id', $statusIds);
            }
        } elseif ($request->has('status_id')) {
            $query->where('orders.status_id', $request->status_id);
        }

        if ($request->has('session_id')) {
            $query->where('orders.session_id', $request->session_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('orders.number', 'like', "%{$search}%")
                    ->orWhere('customers.name', 'like', "%{$search}%")
                    ->orWhere('customers.firstname', 'like', "%{$search}%")
                    ->orWhere('customers.lastname', 'like', "%{$search}%");
            });
        }

        if ($request->has('date_start')) {
            $query->whereDate('orders.created_at', '>=', $request->date_start);
        }

        if ($request->has('date_end')) {
            $query->whereDate('orders.created_at', '<=', $request->date_end);
        }

        $limit = (int) $request->input('limit', 0);
        if ($limit > 0) {
            $page = (int) $request->input('page', 1);
            $page = max($page, 1);
            $query->limit($limit)->offset(($page - 1) * $limit);
        }

        return response()->json($query->orderByDesc('orders.created_at')->get());
    }

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
        $paymentSummarySub = DB::table('payments')
            ->select(
                'order_id',
                DB::raw("MAX(CASE WHEN status IN ('paid','completed') THEN 1 ELSE 0 END) as is_paid"),
                DB::raw("SUM(CASE WHEN status IN ('paid','completed') THEN amount ELSE 0 END) as total_paid"),
                DB::raw('MAX(id) as latest_payment_id')
            )
            ->groupBy('order_id');

        $tipSummarySub = DB::table('tips')
            ->select('order_id', DB::raw('SUM(amount) as tip_amount'))
            ->groupBy('order_id');

        $query = Order::with(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option'])
            ->leftJoinSub($paymentSummarySub, 'payment_summary', function ($join) {
                $join->on('orders.id', '=', 'payment_summary.order_id');
            })
            ->leftJoin('payments as payment_latest', 'payment_latest.id', '=', 'payment_summary.latest_payment_id')
            ->leftJoinSub($tipSummarySub, 'tip_summary', function ($join) {
                $join->on('orders.id', '=', 'tip_summary.order_id');
            })
            ->select([
                'orders.*',
                DB::raw('COALESCE(payment_summary.is_paid, 0) as is_paid'),
                DB::raw('COALESCE(payment_summary.total_paid, 0) as total_paid'),
                'payment_latest.method as payment_method',
                'payment_latest.status as payment_status',
                DB::raw('COALESCE(tip_summary.tip_amount, 0) as tip_amount'),
            ]);

        if ($request->has('status_ids')) {
            $statusIds = $request->input('status_ids', []);
            if (is_string($statusIds)) {
                $statusIds = array_filter(explode(',', $statusIds));
            }
            if (is_array($statusIds) && !empty($statusIds)) {
                $query->whereIn('orders.status_id', $statusIds);
            }
        } elseif ($request->has('status_id')) {
            $query->where('orders.status_id', $request->status_id);
        }

        if ($request->has('session_id')) {
            $query->where('orders.session_id', $request->session_id);
        }

        if ($request->has('customer_id')) {
            $query->where('orders.customer_id', $request->customer_id);
        }

        if ($request->has('date')) {
            $query->whereDate('orders.created_at', $request->date);
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
                                new OA\Property(property: "comment", type: "string", nullable: true),
                                new OA\Property(
                                    property: "options",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "option_value_id", type: "integer"),
                                            new OA\Property(property: "price", type: "number"),
                                            new OA\Property(property: "qty", type: "integer", nullable: true, example: 1),
                                            new OA\Property(property: "parent_option_value_id", type: "integer", nullable: true),
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
            'items.*.comment' => 'nullable|string',
            'items.*.options' => 'array',
            'items.*.options.*.option_value_id' => 'required|exists:option_values,id',
            'items.*.options.*.price' => 'numeric|min:0',
            'items.*.options.*.qty' => 'nullable|integer|min:1',
            'items.*.options.*.parent_option_value_id' => 'nullable|exists:option_values,id',
        ]);



        DB::listen(function ($query) {
            if (str_contains($query->sql, 'order_item_options')) {
                /*Log::info('OrderController.sql order_item_options', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                ]);*/
            }
        });

        $optionModel = new \App\Models\OrderItemOption();
        /*Log::info('OrderController.fillable', [
            'fillable' => $optionModel->getFillable(),
            'is_fillable_parent' => $optionModel->isFillable('parent_option_value_id'),
        ]);*/
        if (isset($validated['items'])) {
            /*Log::info('OrderController.store received options', [
                'database' => DB::connection()->getDatabaseName(),
                'items' => collect($validated['items'])->map(function ($item) {
                    return [
                        'item_id' => $item['item_id'] ?? null,
                        'options' => collect($item['options'] ?? [])->map(function ($option) {
                            return [
                                'option_value_id' => $option['option_value_id'] ?? null,
                                'parent_option_value_id' => $option['parent_option_value_id'] ?? null,
                                'price' => $option['price'] ?? null,
                                'qty' => $option['qty'] ?? null,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ]);*/
        }

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
                    'comment' => $itemData['comment'] ?? null,
                ]);

                if (isset($itemData['options'])) {
                    foreach ($itemData['options'] as $optionData) {
                        $optionPayload = [
                            'option_value_id' => $optionData['option_value_id'],
                            'price' => $optionData['price'] ?? 0,
                            'qty' => $optionData['qty'] ?? null,
                            'parent_option_value_id' => $optionData['parent_option_value_id'] ?? null,
                        ];
                        /*Log::info('OrderController.store option payload', [
                            'order_item_id' => $orderItem->id,
                            'payload' => $optionPayload,
                        ]);*/
                        $createdOption = $orderItem->orderItemOptions()->create($optionPayload);
                        $dbParentOptionValueId = DB::table('order_item_options')
                            ->where('id', $createdOption->id)
                            ->value('parent_option_value_id');
                        /*Log::info('OrderController.store saved option', [
                            'order_item_id' => $orderItem->id,
                            'option_value_id' => $createdOption->option_value_id,
                            'parent_option_value_id' => $createdOption->parent_option_value_id,
                            'db_parent_option_value_id' => $dbParentOptionValueId,
                        ]);*/
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
            $order->load(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']),
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
            $order->load(['customer', 'status', 'session', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option', 'payments', 'tips'])
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
            'subtotal' => 'nullable|numeric|min:0',
            'service_charge' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'is_delivery' => 'nullable|boolean',
            'is_reward' => 'nullable|boolean',
        ]);

        $order->update($validated);

        return response()->json($order->load(['customer', 'status', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']));
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
