<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Category",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Wraps"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Delicious wraps"),
        new OA\Property(property: "icon", type: "string", nullable: true, example: "wrap-icon"),
        new OA\Property(property: "color", type: "string", nullable: true, example: "#FF5733"),
        new OA\Property(property: "sort_order", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Item",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Chicken Wrap"),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "cost", type: "number", format: "float", example: 9.99),
        new OA\Property(property: "category_id", type: "integer", nullable: true),
        new OA\Property(property: "active", type: "boolean", example: true),
        new OA\Property(property: "image_path", type: "string", nullable: true),
        new OA\Property(property: "short_code", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Option",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Size"),
        new OA\Property(property: "type", type: "string", example: "single"),
        new OA\Property(property: "required", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "OptionValue",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "option_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Large"),
        new OA\Property(property: "price", type: "number", format: "float", example: 2.00),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "ItemOption",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "item_id", type: "integer", example: 1),
        new OA\Property(property: "option_id", type: "integer", example: 1),
        new OA\Property(property: "required", type: "boolean", example: false),
        new OA\Property(property: "type", type: "string", example: "single"),
        new OA\Property(property: "range", type: "integer", example: 0),
        new OA\Property(property: "max", type: "integer", nullable: true, example: 2),
        new OA\Property(property: "min", type: "integer", nullable: true, example: 0),
        new OA\Property(property: "item", ref: "#/components/schemas/Item"),
        new OA\Property(property: "option", ref: "#/components/schemas/Option"),
        new OA\Property(property: "item_option_values", type: "array", items: new OA\Items(ref: "#/components/schemas/ItemOptionValue")),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "ItemOptionValue",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "item_option_id", type: "integer", example: 1),
        new OA\Property(property: "option_value_id", type: "integer", example: 1),
        new OA\Property(property: "price", type: "number", format: "float", example: 0.50),
        new OA\Property(property: "in_stock", type: "boolean", example: true),
        new OA\Property(property: "option_dependency_id", type: "integer", nullable: true, example: 2),
        new OA\Property(property: "option_dependency", ref: "#/components/schemas/OptionDependency"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "OptionDependency",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "parent_option_value_id", type: "integer", example: 10),
        new OA\Property(property: "child_option_id", type: "integer", example: 5),
        new OA\Property(property: "parent_option_value", ref: "#/components/schemas/ItemOptionValue"),
        new OA\Property(property: "child_option", ref: "#/components/schemas/ItemOption"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Tax",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Sales Tax"),
        new OA\Property(property: "rate", type: "number", format: "float", example: 8.25),
        new OA\Property(property: "active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Customer",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "555-1234"),
        new OA\Property(property: "email", type: "string", nullable: true, example: "john@example.com"),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "CashSession",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "opening_amount", type: "number", format: "float", example: 100.00),
        new OA\Property(property: "closing_amount", type: "number", format: "float", nullable: true),
        new OA\Property(property: "expected_amount", type: "number", format: "float", nullable: true),
        new OA\Property(property: "total_sales", type: "number", format: "float", example: 0),
        new OA\Property(property: "total_tips", type: "number", format: "float", example: 0),
        new OA\Property(property: "total_service_charge", type: "number", format: "float", example: 0),
        new OA\Property(property: "total_withdrawals", type: "number", format: "float", example: 0),
        new OA\Property(property: "is_open", type: "boolean", example: true),
        new OA\Property(property: "opened_at", type: "string", format: "date-time"),
        new OA\Property(property: "closed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Status",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Pending"),
        new OA\Property(property: "color", type: "string", nullable: true, example: "#FFA500"),
        new OA\Property(property: "sort_order", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Order",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "customer_id", type: "integer", nullable: true),
        new OA\Property(property: "cash_session_id", type: "integer", nullable: true),
        new OA\Property(property: "status_id", type: "integer", example: 1),
        new OA\Property(property: "order_number", type: "string", example: "ORD-001"),
        new OA\Property(property: "subtotal", type: "number", format: "float", example: 25.99),
        new OA\Property(property: "tax_amount", type: "number", format: "float", example: 2.14),
        new OA\Property(property: "discount_amount", type: "number", format: "float", example: 0),
        new OA\Property(property: "total", type: "number", format: "float", example: 28.13),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "OrderItem",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "order_id", type: "integer", example: 1),
        new OA\Property(property: "item_id", type: "integer", example: 1),
        new OA\Property(property: "quantity", type: "integer", example: 2),
        new OA\Property(property: "unit_price", type: "number", format: "float", example: 9.99),
        new OA\Property(property: "total_price", type: "number", format: "float", example: 19.98),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Payment",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "order_id", type: "integer", example: 1),
        new OA\Property(property: "payment_method", type: "string", example: "cash"),
        new OA\Property(property: "amount", type: "number", format: "float", example: 28.13),
        new OA\Property(property: "reference", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Tip",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "order_id", type: "integer", example: 1),
        new OA\Property(property: "cash_session_id", type: "integer", nullable: true),
        new OA\Property(property: "amount", type: "number", format: "float", example: 5.00),
        new OA\Property(property: "payment_method", type: "string", example: "cash"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Withdrawal",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "cash_session_id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "amount", type: "number", format: "float", example: 50.00),
        new OA\Property(property: "reason", type: "string", nullable: true, example: "Bank deposit"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Setting",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "key", type: "string", example: "store_name"),
        new OA\Property(property: "value", type: "string", example: "It's A Wrap"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Branch",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Downtown"),
        new OA\Property(property: "address", type: "string", nullable: true),
        new OA\Property(property: "phone", type: "string", nullable: true),
        new OA\Property(property: "active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "User",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "username", type: "string", example: "johndoe"),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "pin", type: "string", example: "1234"),
        new OA\Property(property: "role_id", type: "integer", example: 2),
        new OA\Property(property: "branch_id", type: "integer", nullable: true),
        new OA\Property(property: "active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
class Schemas
{
    // This class exists only to hold OpenAPI schema definitions
}
