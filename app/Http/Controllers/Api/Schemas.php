<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Wraps'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Delicious wraps'),
        new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'wrap-icon'),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: '#FF5733'),
        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
        new OA\Property(property: 'primary_image_url', type: 'string', nullable: true, example: 'https://itsawrap.ai/storage/media-library/wraps.jpg'),
        new OA\Property(property: 'primary_media', ref: '#/components/schemas/PrimaryMedia', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Item',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Chicken Wrap'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'cost', type: 'number', format: 'float', example: 9.99),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'image_path', type: 'string', nullable: true),
        new OA\Property(property: 'short_code', type: 'string', nullable: true),
        new OA\Property(property: 'primary_image_url', type: 'string', nullable: true, example: 'https://itsawrap.ai/storage/media-library/chicken-wrap.jpg'),
        new OA\Property(property: 'primary_media', ref: '#/components/schemas/PrimaryMedia', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PrimaryMedia',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'basename', type: 'string', example: 'chicken-wrap.jpg'),
        new OA\Property(property: 'filename', type: 'string', example: 'chicken-wrap'),
        new OA\Property(property: 'extension', type: 'string', example: 'jpg'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'image/jpeg'),
        new OA\Property(property: 'aggregate_type', type: 'string', example: 'image'),
        new OA\Property(property: 'size', type: 'integer', nullable: true, example: 35248),
        new OA\Property(property: 'url', type: 'string', nullable: true, example: 'https://itsawrap.ai/storage/media-library/chicken-wrap.jpg'),
        new OA\Property(property: 'preview_url', type: 'string', nullable: true, example: 'https://itsawrap.ai/storage/media-library/chicken-wrap.jpg'),
        new OA\Property(property: 'alt', type: 'string', nullable: true, example: 'Chicken wrap on a plate'),
    ]
)]
#[OA\Schema(
    schema: 'Option',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Size'),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Choose a size'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Select the portion size for this item.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'OptionValue',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'option_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Large'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 2.00),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ItemOption',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'item_id', type: 'integer', example: 1),
        new OA\Property(property: 'option_id', type: 'integer', example: 1),
        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
        new OA\Property(property: 'required', type: 'boolean', example: false),
        new OA\Property(property: 'type', type: 'string', example: 'single'),
        new OA\Property(property: 'range', type: 'integer', example: 0),
        new OA\Property(property: 'max', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'min', type: 'integer', nullable: true, example: 0),
        new OA\Property(property: 'qty', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'enable_qty', type: 'boolean', example: false),
        new OA\Property(property: 'item', ref: '#/components/schemas/Item'),
        new OA\Property(property: 'option', ref: '#/components/schemas/Option'),
        new OA\Property(property: 'item_option_values', type: 'array', items: new OA\Items(ref: '#/components/schemas/ItemOptionValue')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ItemOptionValue',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'item_option_id', type: 'integer', example: 1),
        new OA\Property(property: 'option_value_id', type: 'integer', example: 1),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 0.50),
        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
        new OA\Property(property: 'qty', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'option_dependency_id', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'option_dependency', ref: '#/components/schemas/OptionDependency'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'OptionDependency',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parent_option_value_id', type: 'integer', example: 10),
        new OA\Property(property: 'child_option_id', type: 'integer', example: 5),
        new OA\Property(property: 'parent_option_value', ref: '#/components/schemas/ItemOptionValue'),
        new OA\Property(property: 'child_option', ref: '#/components/schemas/ItemOption'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Tax',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Sales Tax'),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 8.25),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Customer',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '555-1234'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'john@example.com'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'CashSession',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'opening_amount', type: 'number', format: 'float', example: 100.00),
        new OA\Property(property: 'closing_amount', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'expected_amount', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'total_sales', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'total_tips', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'total_service_charge', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'total_withdrawals', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'is_open', type: 'boolean', example: true),
        new OA\Property(property: 'opened_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Status',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Pending'),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: '#FFA500'),
        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Order',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'customer_id', type: 'integer', nullable: true),
        new OA\Property(property: 'cash_session_id', type: 'integer', nullable: true),
        new OA\Property(property: 'status_id', type: 'integer', example: 1),
        new OA\Property(property: 'order_number', type: 'string', example: 'ORD-001'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 25.99),
        new OA\Property(property: 'tax_amount', type: 'number', format: 'float', example: 2.14),
        new OA\Property(property: 'discount_amount', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 28.13),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'OrderParticipant',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'client_id', type: 'string', nullable: true, example: 'person-0'),
        new OA\Property(property: 'name', type: 'string', example: 'Alex Carter'),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
        new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', nullable: true, example: 12.50),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'GuestOrderParticipant',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Alex Carter'),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 12.50),
    ]
)]
#[OA\Schema(
    schema: 'OrderItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'order_participant_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'participant', ref: '#/components/schemas/OrderParticipant', nullable: true),
        new OA\Property(property: 'item_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'is_reward_item', type: 'boolean', example: false),
        new OA\Property(property: 'reward_program_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'reward_ledger_entry_id', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'reward_discount_amount', type: 'number', format: 'float', nullable: true, example: 12.50),
        new OA\Property(property: 'referral_program_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'referral_ledger_entry_id', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 9.99),
        new OA\Property(property: 'total_price', type: 'number', format: 'float', example: 19.98),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'GuestOrderLookup',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: 'z0Zpgxd5WHC0x0QxLzSfHmW8n5k4xJ0l6DOLxP9pWcMKRz4OzVzMxE2oOfqg1PtA'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'status_url', type: 'string', example: 'https://itsawrap.ai/api/guest/orders/IAW-000123?token=z0Zpgxd5WHC0x0QxLzSfHmW8n5k4xJ0l6DOLxP9pWcMKRz4OzVzMxE2oOfqg1PtA'),
    ]
)]
#[OA\Schema(
    schema: 'GuestOrderDetails',
    properties: [
        new OA\Property(property: 'number', type: 'string', example: 'IAW-000123'),
        new OA\Property(property: 'status', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'pending'),
        ]),
        new OA\Property(property: 'subtotal', type: 'string', example: '13.50'),
        new OA\Property(property: 'discount', type: 'string', nullable: true, example: '0.00'),
        new OA\Property(property: 'discount_percent', type: 'string', nullable: true, example: '0.00'),
        new OA\Property(property: 'service_charge', type: 'string', example: '0.00'),
        new OA\Property(property: 'total', type: 'string', example: '13.50'),
        new OA\Property(property: 'comments', type: 'string', nullable: true),
        new OA\Property(property: 'placed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_delivery', type: 'boolean', example: false),
        new OA\Property(
            property: 'participants',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/GuestOrderParticipant')
        ),
        new OA\Property(property: 'delivery', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'delivery_date', type: 'string', format: 'date', example: '2026-08-31'),
            new OA\Property(property: 'window_start_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'window_end_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'address', type: 'string', example: '123 Main Road, The Valley'),
            new OA\Property(property: 'latitude', type: 'string', example: '18.2208000'),
            new OA\Property(property: 'longitude', type: 'string', example: '-63.0686000'),
            new OA\Property(property: 'delivery_instructions', type: 'string', nullable: true, example: 'Call on arrival'),
            new OA\Property(property: 'status', type: 'string', example: 'pending'),
        ]),
        new OA\Property(
            property: 'order_items',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'item', type: 'object', nullable: true, properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Chicken Wrap'),
                    ]),
                    new OA\Property(property: 'participant', type: 'object', nullable: true, properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Alex Carter'),
                        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                    ]),
                    new OA\Property(property: 'price', type: 'string', example: '12.50'),
                    new OA\Property(property: 'quantity', type: 'integer', example: 1),
                    new OA\Property(property: 'comment', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'options',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'name', type: 'string', nullable: true, example: 'BBQ'),
                                new OA\Property(property: 'option_name', type: 'string', nullable: true, example: 'Sauce'),
                                new OA\Property(property: 'price', type: 'string', example: '1.00'),
                                new OA\Property(property: 'qty', type: 'integer', nullable: true, example: 1),
                            ]
                        )
                    ),
                ]
            )
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'GuestOrderResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GuestOrderDetails'),
        new OA\Schema(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 123),
            new OA\Property(property: 'customer_id', type: 'integer', nullable: true, example: 1),
            new OA\Property(property: 'status_id', type: 'integer', example: 1),
            new OA\Property(property: 'is_reward', type: 'boolean', example: false),
            new OA\Property(property: 'session_id', type: 'integer', nullable: true),
            new OA\Property(property: 'source', type: 'string', example: 'guest-web'),
            new OA\Property(property: 'lookup', ref: '#/components/schemas/GuestOrderLookup'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'RewardProgram',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Buy 6 Wraps, Get 1 Free Wrap'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'earn_category_id', type: 'integer', example: 1),
        new OA\Property(property: 'qualifying_item_quantity_required', type: 'integer', example: 6),
        new OA\Property(property: 'reward_category_id', type: 'integer', example: 1),
        new OA\Property(property: 'reward_quantity', type: 'integer', example: 1),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_by_user_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'earn_category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'reward_category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'CustomerRewardProgramSummary',
    properties: [
        new OA\Property(property: 'program_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Buy 6 Wraps, Get 1 Free Wrap'),
        new OA\Property(property: 'earn_category', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Wraps'),
        ]),
        new OA\Property(property: 'reward_category', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Wraps'),
        ]),
        new OA\Property(property: 'qualifying_item_quantity_required', type: 'integer', example: 6),
        new OA\Property(property: 'reward_quantity', type: 'integer', example: 1),
        new OA\Property(property: 'progress_quantity', type: 'integer', example: 4),
        new OA\Property(property: 'rewards_available', type: 'integer', example: 0),
        new OA\Property(property: 'lifetime_qualifying_quantity', type: 'integer', example: 16),
        new OA\Property(property: 'lifetime_rewards_earned', type: 'integer', example: 2),
        new OA\Property(property: 'lifetime_rewards_redeemed', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'CustomerRewardSummary',
    properties: [
        new OA\Property(property: 'rewards_enabled', type: 'boolean', example: true),
        new OA\Property(
            property: 'programs',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CustomerRewardProgramSummary')
        ),
        new OA\Property(property: 'purchase_loyalty', type: 'object', properties: [
            new OA\Property(
                property: 'programs',
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/CustomerRewardProgramSummary')
            ),
        ]),
        new OA\Property(property: 'referral_loyalty', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'program_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Referral Loyalty'),
            new OA\Property(property: 'code', type: 'string', example: 'SUNRA482'),
            new OA\Property(property: 'share_url', type: 'string', example: 'https://itsawrap.ai/ref/SUNRA482'),
            new OA\Property(property: 'required_referrals', type: 'integer', example: 5),
            new OA\Property(property: 'qualified_referrals', type: 'integer', example: 3),
            new OA\Property(property: 'progress_quantity', type: 'integer', example: 3),
            new OA\Property(property: 'rewards_available', type: 'integer', example: 0),
            new OA\Property(property: 'lifetime_qualified_referrals', type: 'integer', example: 3),
            new OA\Property(property: 'lifetime_rewards_earned', type: 'integer', example: 0),
            new OA\Property(property: 'lifetime_rewards_redeemed', type: 'integer', example: 0),
        ]),
        new OA\Property(property: 'total_rewards_available', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'RewardLedgerEntry',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'customer_id', type: 'integer', example: 1),
        new OA\Property(property: 'reward_program_id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'order_item_id', type: 'integer', nullable: true, example: 20),
        new OA\Property(property: 'reverses_ledger_entry_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'type', type: 'string', enum: ['earned_progress', 'redeemed', 'reversed', 'adjusted', 'expired'], example: 'redeemed'),
        new OA\Property(property: 'progress_delta', type: 'integer', example: 0),
        new OA\Property(property: 'rewards_delta', type: 'integer', example: -1),
        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'reward_redemption'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true),
        new OA\Property(property: 'created_by_user_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Offer',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Get 10% off Rice Bowls'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Limited time rice bowl discount.'),
        new OA\Property(property: 'offer_type', type: 'string', enum: ['percentage_discount', 'fixed_discount', 'buy_x_get_y', 'spend_x_get_y'], example: 'percentage_discount'),
        new OA\Property(property: 'discount_type', type: 'string', enum: ['percent', 'fixed_amount', 'free_item'], example: 'percent'),
        new OA\Property(property: 'discount_value', type: 'number', format: 'float', nullable: true, example: 10),
        new OA\Property(property: 'minimum_subtotal', type: 'number', format: 'float', nullable: true, example: 20),
        new OA\Property(property: 'required_quantity', type: 'integer', nullable: true, example: 6),
        new OA\Property(property: 'reward_quantity', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_stackable', type: 'boolean', example: false),
        new OA\Property(property: 'priority', type: 'integer', example: 10),
        new OA\Property(property: 'featured_image_url', type: 'string', nullable: true, example: 'https://itsawrap.ai/storage/media-library/rice-bowls.jpg'),
        new OA\Property(property: 'qualifying_category', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Rice Bowls'),
        ]),
        new OA\Property(property: 'qualifying_item', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 10),
            new OA\Property(property: 'name', type: 'string', example: 'Chicken Rice Bowl'),
        ]),
        new OA\Property(property: 'reward_category', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 2),
            new OA\Property(property: 'name', type: 'string', example: 'Sides'),
        ]),
        new OA\Property(property: 'reward_item', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 20),
            new OA\Property(property: 'name', type: 'string', example: 'Plantain Side'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'PushSubscription',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'provider', type: 'string', example: 'firebase'),
        new OA\Property(property: 'platform', type: 'string', enum: ['ios', 'android', 'web'], example: 'ios'),
        new OA\Property(property: 'app_context', type: 'string', enum: ['pos', 'admin', 'customer'], example: 'pos'),
        new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'Kitchen iPad'),
        new OA\Property(property: 'personal_access_token_id', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'last_seen_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'revoked_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Payment',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'payment_method', type: 'string', example: 'cash'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 28.13),
        new OA\Property(property: 'reference', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Tip',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', example: 1),
        new OA\Property(property: 'cash_session_id', type: 'integer', nullable: true),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 5.00),
        new OA\Property(property: 'payment_method', type: 'string', example: 'cash'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Withdrawal',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'cash_session_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50.00),
        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Bank deposit'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Setting',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'key', type: 'string', example: 'store_name'),
        new OA\Property(property: 'value', type: 'string', example: "It's A Wrap"),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Branch',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Downtown'),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'pin', type: 'string', example: '1234'),
        new OA\Property(property: 'role_id', type: 'integer', example: 2),
        new OA\Property(property: 'branch_id', type: 'integer', nullable: true),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Schemas
{
    // This class exists only to hold OpenAPI schema definitions
}
