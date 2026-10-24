<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\StoreCustomerOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CustomerOrderController extends Controller
{
    #[OA\Post(
        path: '/me/orders',
        summary: 'Create an order for the authenticated customer',
        description: 'Create a customer web order for the authenticated customer. Optional participants allow group-order item assignment while rewards remain attached to the primary customer record.',
        tags: ['Customer', 'Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                in: 'header',
                required: false,
                description: 'Optional key used to safely replay the same customer order creation request.',
                schema: new OA\Schema(type: 'string', maxLength: 100)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subtotal', 'total', 'is_delivery', 'branch_id'],
                properties: [
                    new OA\Property(property: 'number', type: 'string', nullable: true, example: 'IAW-000123'),
                    new OA\Property(property: 'branch_id', type: 'integer', description: 'The branch to order from; must have an open cash session or the order is rejected.', example: 1),
                    new OA\Property(property: 'subtotal', type: 'number', example: 21.50),
                    new OA\Property(property: 'service_charge', type: 'number', nullable: true, example: 0),
                    new OA\Property(property: 'total', type: 'number', example: 21.50),
                    new OA\Property(property: 'comments', type: 'string', nullable: true, example: 'No onions'),
                    new OA\Property(property: 'is_delivery', type: 'boolean', example: false),
                    new OA\Property(property: 'delivery_window_id', type: 'integer', nullable: true, example: 5),
                    new OA\Property(property: 'delivery_address', type: 'string', nullable: true, example: '123 Main Road, The Valley'),
                    new OA\Property(property: 'delivery_latitude', type: 'number', nullable: true, example: 18.2208),
                    new OA\Property(property: 'delivery_longitude', type: 'number', nullable: true, example: -63.0686),
                    new OA\Property(property: 'delivery_instructions', type: 'string', nullable: true, example: 'Call on arrival'),
                    new OA\Property(
                        property: 'participants',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            required: ['client_id', 'name'],
                            properties: [
                                new OA\Property(property: 'client_id', type: 'string', example: 'person-0'),
                                new OA\Property(property: 'name', type: 'string', example: 'Alex Carter'),
                                new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'bundles',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            required: ['bundle_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'bundle_id', type: 'integer', example: 3),
                                new OA\Property(property: 'quantity', type: 'integer', example: 1),
                                new OA\Property(property: 'participant_client_id', type: 'string', nullable: true, example: 'person-0'),
                                new OA\Property(property: 'comment', type: 'string', nullable: true),
                            ]
                        )
                    ),
                    new OA\Property(property: 'meal_deals', type: 'array', nullable: true, items: new OA\Items(ref: '#/components/schemas/MealDealOrderSelection')),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            required: ['item_id', 'price', 'quantity'],
                            properties: [
                                new OA\Property(property: 'item_id', type: 'integer', example: 1),
                                new OA\Property(property: 'price', type: 'number', example: 12.50),
                                new OA\Property(property: 'quantity', type: 'integer', example: 1),
                                new OA\Property(property: 'participant_client_id', type: 'string', nullable: true, example: 'person-0'),
                                new OA\Property(property: 'comment', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    nullable: true,
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'option_value_id', type: 'integer', example: 2),
                                            new OA\Property(property: 'price', type: 'number', nullable: true, example: 1.00),
                                            new OA\Property(property: 'qty', type: 'integer', nullable: true, example: 1),
                                            new OA\Property(property: 'parent_option_value_id', type: 'integer', nullable: true),
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
            new OA\Response(response: 201, description: 'Customer order created', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 200, description: 'Existing customer order replayed by idempotency key', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not a customer token'),
            new OA\Response(response: 409, description: 'Duplicate order is already being processed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCustomerOrderRequest $request, OrderCreationService $orders): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $customer = Customer::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => trim($user->firstname.' '.$user->lastname),
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'source' => 'web-customer',
            ]
        );

        $order = $orders->create($validated, $customer->id, 'web-customer', $request->ip(), (int) $validated['branch_id']);

        return response()->json($order, $order->wasRecentlyCreated ? 201 : 200);
    }

    #[OA\Get(
        path: '/me/orders',
        summary: "List the authenticated customer's past orders",
        description: "Returns the authenticated customer's own orders, most recent first. Returns an empty list when the authenticated user has no linked Customer record yet (never placed an order).",
        tags: ['Customer', 'Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated order history', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomerOrder')),
                    new OA\Property(property: 'meta', properties: [
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'last_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                    ], type: 'object'),
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not a customer token'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->first();

        if ($customer === null) {
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
        }

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with($this->eagerLoadRelations())
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order): array => $this->orderPayload($order))->values()->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/me/orders/{number}',
        summary: "Get one of the authenticated customer's own orders by number",
        description: 'Fetch order details and live status for an order belonging to the authenticated customer. Unlike /guest/orders/{number}, no lookup token is needed — ownership is established by the Sanctum token.',
        tags: ['Customer', 'Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'number',
                in: 'path',
                required: true,
                description: 'Order number returned when the order was created.',
                schema: new OA\Schema(type: 'string'),
                example: 'IAW-000123'
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order details', content: new OA\JsonContent(ref: '#/components/schemas/CustomerOrder')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not a customer token'),
            new OA\Response(response: 404, description: 'Order not found, or does not belong to the authenticated customer'),
        ]
    )]
    public function show(Request $request, string $number): JsonResponse
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->first();

        $order = $customer !== null
            ? Order::query()
                ->where('customer_id', $customer->id)
                ->where('number', $number)
                ->with($this->eagerLoadRelations())
                ->first()
            : null;

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($this->orderPayload($order));
    }

    /**
     * @return array<int, string>
     */
    private function eagerLoadRelations(): array
    {
        return ['status', 'delivery.deliveryWindow', 'orderItems.item', 'orderItems.orderItemOptions.optionValue', 'mealDeals'];
    }

    /**
     * Unlike GuestOrderController's guest-safe payload, internal row ids are
     * fine to include here: the caller already proved ownership via their
     * own Sanctum token, not a shareable lookup link, so there's no
     * guest-safety reason to strip them — and ItemOrderLine.item_id is what
     * the mobile app's "Reorder" action needs.
     *
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status !== null ? [
                'id' => $order->status->id,
                'name' => $order->status->name,
            ] : null,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'service_charge' => $order->service_charge,
            'meal_deal_service_charge' => $order->meal_deal_service_charge,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'comments' => $order->comments,
            'is_delivery' => $order->is_delivery,
            'placed_at' => $order->placed_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
            'delivery' => $order->delivery !== null ? [
                'delivery_date' => $order->delivery->delivery_date?->toDateString(),
                'window_start_at' => $order->delivery->window_start_at?->toISOString(),
                'window_end_at' => $order->delivery->window_end_at?->toISOString(),
                'address' => $order->delivery->address,
                'delivery_instructions' => $order->delivery->delivery_instructions,
                'status' => $order->delivery->status,
            ] : null,
            'order_items' => $order->orderItems->map(fn ($orderItem): array => [
                'id' => $orderItem->id,
                'item_id' => $orderItem->item_id,
                'item' => $orderItem->item !== null ? [
                    'id' => $orderItem->item->id,
                    'name' => $orderItem->item->name,
                ] : null,
                'price' => $orderItem->price,
                'quantity' => $orderItem->quantity,
                'comment' => $orderItem->comment,
                'line_total' => $orderItem->lineTotal(),
                'options' => $orderItem->orderItemOptions->map(fn ($option): array => [
                    'option_value_id' => $option->option_value_id,
                    'name' => $option->optionValue?->name,
                    'price' => $option->price,
                    'qty' => $option->qty,
                ])->values()->all(),
            ])->values()->all(),
            'meal_deals' => $order->mealDeals->map(fn ($deal): array => [
                'id' => $deal->id,
                'meal_deal_id' => $deal->meal_deal_id,
                'name' => $deal->name,
                'quantity' => $deal->quantity,
                'base_price' => $deal->base_price,
                'extras_price' => $deal->extras_price,
            ])->values()->all(),
        ];
    }
}
