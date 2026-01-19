<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ItemController extends Controller
{
    #[OA\Get(
        path: "/items",
        summary: "List all items",
        description: "Get all items with optional filtering by category and active status",
        tags: ["Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "category_id", in: "query", required: false, description: "Filter by category", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "active", in: "query", required: false, description: "Filter by active status", schema: new OA\Schema(type: "boolean"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of items",
                content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Item"))
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Item::with(['category', 'taxes', 'itemOptions.option', 'itemOptions.itemOptionValues.optionValue']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/items",
        summary: "Create an item",
        description: "Create a new menu item",
        tags: ["Items"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Chicken Wrap"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "cost", type: "number", example: 9.99),
                    new OA\Property(property: "category_id", type: "integer", nullable: true),
                    new OA\Property(property: "active", type: "boolean", example: true),
                    new OA\Property(property: "image_path", type: "string", nullable: true),
                    new OA\Property(property: "short_code", type: "string", nullable: true),
                    new OA\Property(property: "tax_ids", type: "array", items: new OA\Items(type: "integer")),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Item created", content: new OA\JsonContent(ref: "#/components/schemas/Item")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'active' => 'boolean',
            'image_path' => 'nullable|string',
            'short_code' => 'nullable|string|max:255',
        ]);

        $item = Item::create($validated);

        if ($request->has('tax_ids')) {
            $item->taxes()->sync($request->tax_ids);
        }

        return response()->json($item->load(['category', 'taxes']), 201);
    }

    #[OA\Get(
        path: "/items/{id}",
        summary: "Get an item",
        description: "Get a single item with its relationships",
        tags: ["Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Item details", content: new OA\JsonContent(ref: "#/components/schemas/Item")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item not found")
        ]
    )]
    public function show(Item $item): JsonResponse
    {
        return response()->json(
            $item->load(['category', 'taxes', 'itemOptions.option', 'itemOptions.itemOptionValues.optionValue'])
        );
    }

    #[OA\Put(
        path: "/items/{id}",
        summary: "Update an item",
        description: "Update an existing item",
        tags: ["Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "cost", type: "number"),
                    new OA\Property(property: "category_id", type: "integer", nullable: true),
                    new OA\Property(property: "active", type: "boolean"),
                    new OA\Property(property: "image_path", type: "string", nullable: true),
                    new OA\Property(property: "short_code", type: "string", nullable: true),
                    new OA\Property(property: "tax_ids", type: "array", items: new OA\Items(type: "integer")),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Item updated", content: new OA\JsonContent(ref: "#/components/schemas/Item")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'cost' => 'numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'active' => 'boolean',
            'image_path' => 'nullable|string',
            'short_code' => 'nullable|string|max:255',
        ]);

        $item->update($validated);

        if ($request->has('tax_ids')) {
            $item->taxes()->sync($request->tax_ids);
        }

        return response()->json($item->load(['category', 'taxes']));
    }

    #[OA\Delete(
        path: "/items/{id}",
        summary: "Delete an item",
        description: "Delete an item",
        tags: ["Items"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Item deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item not found")
        ]
    )]
    public function destroy(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
