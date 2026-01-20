<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemOptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ItemOptionValueController extends Controller
{
    #[OA\Get(
        path: "/item-option-values",
        summary: "List item option values",
        description: "Get all item option values, optionally filtered by item option",
        tags: ["Item Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "item_option_id", in: "query", required: false, description: "Filter by item option ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of item option values", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/ItemOptionValue"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ItemOptionValue::with(['itemOption', 'optionValue']);

        if ($request->has('item_option_id')) {
            $query->where('item_option_id', $request->item_option_id);
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/item-option-values",
        summary: "Create an item option value",
        description: "Create a new item option value",
        tags: ["Item Option Values"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["item_option_id", "option_value_id"],
                properties: [
                    new OA\Property(property: "item_option_id", type: "integer", example: 1),
                    new OA\Property(property: "option_value_id", type: "integer", example: 1),
                    new OA\Property(property: "price", type: "number", example: 0.50),
                    new OA\Property(property: "in_stock", type: "boolean", example: true),
                    new OA\Property(property: "option_dependency_id", type: "integer", nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Item option value created", content: new OA\JsonContent(ref: "#/components/schemas/ItemOptionValue")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_option_id' => 'required|exists:item_options,id',
            'option_value_id' => 'required|exists:option_values,id',
            'price' => 'numeric|min:0',
            'in_stock' => 'boolean',
            'option_dependency_id' => 'nullable|integer',
        ]);

        $itemOptionValue = ItemOptionValue::create($validated);

        return response()->json($itemOptionValue->load(['itemOption', 'optionValue']), 201);
    }

    #[OA\Get(
        path: "/item-option-values/{id}",
        summary: "Get an item option value",
        description: "Get a single item option value",
        tags: ["Item Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item option value ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Item option value details", content: new OA\JsonContent(ref: "#/components/schemas/ItemOptionValue")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item option value not found")
        ]
    )]
    public function show(ItemOptionValue $itemOptionValue): JsonResponse
    {
        return response()->json($itemOptionValue->load(['itemOption', 'optionValue']));
    }

    #[OA\Put(
        path: "/item-option-values/{id}",
        summary: "Update an item option value",
        description: "Update an existing item option value",
        tags: ["Item Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item option value ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "price", type: "number"),
                    new OA\Property(property: "in_stock", type: "boolean"),
                    new OA\Property(property: "option_dependency_id", type: "integer", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Item option value updated", content: new OA\JsonContent(ref: "#/components/schemas/ItemOptionValue")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item option value not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, ItemOptionValue $itemOptionValue): JsonResponse
    {
        $validated = $request->validate([
            'price' => 'numeric|min:0',
            'in_stock' => 'boolean',
            'option_dependency_id' => 'nullable|integer',
        ]);

        $itemOptionValue->update($validated);

        return response()->json($itemOptionValue);
    }

    #[OA\Delete(
        path: "/item-option-values/{id}",
        summary: "Delete an item option value",
        description: "Delete an item option value",
        tags: ["Item Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item option value ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Item option value deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item option value not found")
        ]
    )]
    public function destroy(ItemOptionValue $itemOptionValue): JsonResponse
    {
        $itemOptionValue->delete();

        return response()->json(null, 204);
    }
}
