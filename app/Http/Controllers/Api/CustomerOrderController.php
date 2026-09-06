<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\StoreCustomerOrderRequest;
use App\Models\Customer;
use App\Services\Orders\OrderCreationService;
use Illuminate\Http\JsonResponse;
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
                required: ['subtotal', 'total', 'is_delivery'],
                properties: [
                    new OA\Property(property: 'number', type: 'string', nullable: true, example: 'IAW-000123'),
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

        $order = $orders->create($validated, $customer->id, 'web-customer', $request->ip());

        return response()->json($order, $order->wasRecentlyCreated ? 201 : 200);
    }
}
