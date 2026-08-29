<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\StoreGuestOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GuestOrderController extends Controller
{
    #[OA\Post(
        path: '/guest/orders',
        summary: 'Create a guest order',
        description: 'Create a guest checkout order and return a secure lookup token for later guest order status checks.',
        tags: ['Guest', 'Orders'],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                in: 'header',
                required: false,
                description: 'Optional key used to safely replay the same guest order creation request.',
                schema: new OA\Schema(type: 'string', maxLength: 100)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id', 'subtotal', 'total', 'is_delivery', 'items'],
                properties: [
                    new OA\Property(property: 'number', type: 'string', nullable: true, example: 'IAW-000123'),
                    new OA\Property(property: 'customer_id', type: 'integer', example: 1),
                    new OA\Property(property: 'subtotal', type: 'number', example: 13.50),
                    new OA\Property(property: 'service_charge', type: 'number', nullable: true, example: 0),
                    new OA\Property(property: 'total', type: 'number', example: 13.50),
                    new OA\Property(property: 'comments', type: 'string', nullable: true, example: 'No onions'),
                    new OA\Property(property: 'is_delivery', type: 'boolean', example: false),
                    new OA\Property(property: 'delivery_window_id', type: 'integer', nullable: true, example: 5),
                    new OA\Property(property: 'delivery_address', type: 'string', nullable: true, example: '123 Main Road, The Valley'),
                    new OA\Property(property: 'delivery_latitude', type: 'number', nullable: true, example: 18.2208),
                    new OA\Property(property: 'delivery_longitude', type: 'number', nullable: true, example: -63.0686),
                    new OA\Property(property: 'delivery_instructions', type: 'string', nullable: true, example: 'Call on arrival'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['item_id', 'price', 'quantity'],
                            properties: [
                                new OA\Property(property: 'item_id', type: 'integer', example: 1),
                                new OA\Property(property: 'price', type: 'number', example: 12.50),
                                new OA\Property(property: 'quantity', type: 'integer', example: 1),
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
            new OA\Response(response: 201, description: 'Guest order created', content: new OA\JsonContent(ref: '#/components/schemas/GuestOrderResponse')),
            new OA\Response(response: 200, description: 'Existing guest order replayed by idempotency key', content: new OA\JsonContent(ref: '#/components/schemas/GuestOrderResponse')),
            new OA\Response(response: 409, description: 'Duplicate order is already being processed'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many guest order requests'),
        ]
    )]
    public function store(StoreGuestOrderRequest $request, OrderCreationService $orders): JsonResponse
    {
        $validated = $request->validated();

        $order = $orders->create($validated, (int) $validated['customer_id'], 'guest-web', $request->ip());

        return response()->json($this->guestOrderPayload($order, true, true), $order->wasRecentlyCreated ? 201 : 200);
    }

    #[OA\Get(
        path: '/guest/orders/{number}',
        summary: 'Get guest order status',
        description: 'Fetch guest-safe order details and status using the order number and secure lookup token returned when the guest order was created. One of the token query parameter or X-Guest-Order-Token header is required.',
        tags: ['Guest', 'Orders'],
        parameters: [
            new OA\Parameter(
                name: 'number',
                in: 'path',
                required: true,
                description: 'Public order number returned by guest order creation.',
                schema: new OA\Schema(type: 'string'),
                example: 'IAW-000123'
            ),
            new OA\Parameter(
                name: 'token',
                in: 'query',
                required: false,
                description: 'Guest lookup token returned by guest order creation. May also be sent as X-Guest-Order-Token.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'X-Guest-Order-Token',
                in: 'header',
                required: false,
                description: 'Alternative header for the guest lookup token.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Guest-safe order details', content: new OA\JsonContent(ref: '#/components/schemas/GuestOrderDetails')),
            new OA\Response(response: 404, description: 'Order not found or token invalid'),
            new OA\Response(response: 429, description: 'Too many guest order lookup requests'),
        ]
    )]
    public function show(Request $request, string $number): JsonResponse
    {
        $token = (string) ($request->query('token') ?? $request->header('X-Guest-Order-Token', ''));

        if ($token === '') {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order = Order::query()
            ->where('source', 'guest-web')
            ->where('number', $number)
            ->where('guest_access_token_hash', hash('sha256', $token))
            ->where(function ($query): void {
                $query->whereNull('guest_access_token_expires_at')
                    ->orWhere('guest_access_token_expires_at', '>', now());
            })
            ->with(['customer', 'status', 'delivery.deliveryWindow', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option'])
            ->first();

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($this->guestOrderPayload($order));
    }

    /**
     * @return array<string, mixed>
     */
    private function guestOrderPayload(Order $order, bool $includeLookup = false, bool $includeCreationFields = false): array
    {
        $order->loadMissing(['customer', 'status', 'delivery.deliveryWindow', 'orderItems.item', 'orderItems.orderItemOptions.optionValue.option']);

        $payload = [
            'number' => $order->number,
            'status' => $order->status !== null ? [
                'id' => $order->status->id,
                'name' => $order->status->name,
            ] : null,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'discount_percent' => $order->discount_percent,
            'service_charge' => $order->service_charge,
            'total' => $order->total,
            'comments' => $order->comments,
            'placed_at' => $order->placed_at?->toISOString(),
            'is_delivery' => $order->is_delivery,
            'delivery' => $this->deliveryPayload($order, $includeCreationFields),
            'order_items' => $this->orderItemsPayload($order, $includeCreationFields),
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
        ];

        if ($includeCreationFields) {
            $payload = [
                'id' => $order->id,
                'customer_id' => $order->customer_id,
                'status_id' => $order->status_id,
                'is_reward' => $order->is_reward,
                'session_id' => $order->session_id,
                'source' => $order->source,
            ] + $payload;
        }

        if ($includeLookup && $order->source === 'guest-web' && $order->guest_access_token !== null) {
            $payload['lookup'] = [
                'token' => $order->guest_access_token,
                'expires_at' => $order->guest_access_token_expires_at?->toISOString(),
                'status_url' => url('/api/guest/orders/'.rawurlencode((string) $order->number).'?token='.rawurlencode($order->guest_access_token)),
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function deliveryPayload(Order $order, bool $includeCreationFields = false): ?array
    {
        if ($order->delivery === null) {
            return null;
        }

        $payload = [
            'delivery_date' => $order->delivery->delivery_date?->toDateString(),
            'window_start_at' => $order->delivery->window_start_at?->toISOString(),
            'window_end_at' => $order->delivery->window_end_at?->toISOString(),
            'address' => $order->delivery->address,
            'latitude' => $order->delivery->latitude,
            'longitude' => $order->delivery->longitude,
            'delivery_instructions' => $order->delivery->delivery_instructions,
            'status' => $order->delivery->status,
        ];

        if ($includeCreationFields) {
            $payload = [
                'id' => $order->delivery->id,
                'delivery_window_id' => $order->delivery->delivery_window_id,
            ] + $payload;

            $payload['delivery_window'] = $order->delivery->deliveryWindow !== null ? [
                'id' => $order->delivery->deliveryWindow->id,
                'schedule_type' => $order->delivery->deliveryWindow->schedule_type,
                'day_of_week' => $order->delivery->deliveryWindow->day_of_week,
                'delivery_date' => $order->delivery->deliveryWindow->delivery_date?->toDateString(),
                'start_time' => $order->delivery->deliveryWindow->start_time,
                'end_time' => $order->delivery->deliveryWindow->end_time,
            ] : null;
        }

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orderItemsPayload(Order $order, bool $includeCreationFields = false): array
    {
        return $order->orderItems->map(function ($orderItem) use ($includeCreationFields): array {
            $payload = [
                'item' => $orderItem->item !== null ? [
                    'name' => $orderItem->item->name,
                ] : null,
                'price' => $orderItem->price,
                'quantity' => $orderItem->quantity,
                'comment' => $orderItem->comment,
                'options' => $orderItem->orderItemOptions->map(function ($option) use ($includeCreationFields): array {
                    $optionPayload = [
                        'name' => $option->optionValue?->name,
                        'option_name' => $option->optionValue?->option?->name,
                        'price' => $option->price,
                        'qty' => $option->qty,
                    ];

                    if ($includeCreationFields) {
                        $optionPayload = [
                            'id' => $option->id,
                            'option_value_id' => $option->option_value_id,
                            'parent_option_value_id' => $option->parent_option_value_id,
                        ] + $optionPayload;
                    }

                    return $optionPayload;
                })->values()->all(),
            ];

            if ($includeCreationFields) {
                $payload = [
                    'id' => $orderItem->id,
                    'item_id' => $orderItem->item_id,
                ] + $payload;

                if ($payload['item'] !== null) {
                    $payload['item'] = [
                        'id' => $orderItem->item->id,
                    ] + $payload['item'];
                }
            }

            return $payload;
        })->values()->all();
    }
}
