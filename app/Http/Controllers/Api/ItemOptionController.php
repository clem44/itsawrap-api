<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ItemOptionController extends Controller
{
    #[OA\Get(
        path: "/item-options",
        summary: "List item options",
        description: "Get all item options, optionally filtered by item",
        tags: ["Item Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "item_id", in: "query", required: false, description: "Filter by item ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of item options", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/ItemOption"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ItemOption::with(['item', 'option', 'itemOptionValues.optionValue']);

        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/item-options",
        summary: "Create an item option",
        description: "Create a new item option",
        tags: ["Item Options"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["item_id", "option_id"],
                properties: [
                    new OA\Property(property: "item_id", type: "integer", example: 1),
                    new OA\Property(property: "option_id", type: "integer", example: 1),
                    new OA\Property(property: "required", type: "boolean", example: false),
                    new OA\Property(property: "type", type: "string", enum: ["single", "multiple"], example: "single"),
                    new OA\Property(property: "range", type: "integer", example: 0),
                    new OA\Property(property: "max", type: "integer", nullable: true, example: 2),
                    new OA\Property(property: "min", type: "integer", nullable: true, example: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Item option created", content: new OA\JsonContent(ref: "#/components/schemas/ItemOption")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'option_id' => 'required|exists:options,id',
            'required' => 'boolean',
            'type' => 'string|in:single,multiple',
            'range' => 'integer|min:0',
            'max' => 'nullable|integer|min:0',
            'min' => 'nullable|integer|min:0',
        ]);

        $itemOption = ItemOption::create($validated);

        return response()->json($itemOption->load(['item', 'option']), 201);
    }

    #[OA\Get(
        path: "/item-options/{id}",
        summary: "Get an item option",
        description: "Get a single item option with its values",
        tags: ["Item Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item option ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Item option details", content: new OA\JsonContent(ref: "#/components/schemas/ItemOption")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item option not found")
        ]
    )]
    public function show(ItemOption $itemOption): JsonResponse
    {
        return response()->json($itemOption->load(['item', 'option', 'itemOptionValues.optionValue']));
    }

    #[OA\Put(
        path: "/item-options/{id}",
        summary: "Update an item option",
        description: "Update an existing item option",
        tags: ["Item Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item option ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "required", type: "boolean"),
                    new OA\Property(property: "type", type: "string", enum: ["single", "multiple"]),
                    new OA\Property(property: "range", type: "integer"),
                    new OA\Property(property: "max", type: "integer", nullable: true),
                    new OA\Property(property: "min", type: "integer", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Item option updated", content: new OA\JsonContent(ref: "#/components/schemas/ItemOption")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item option not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, ItemOption $itemOption): JsonResponse
    {
        $validated = $request->validate([
            'required' => 'boolean',
            'type' => 'string|in:single,multiple',
            'range' => 'integer|min:0',
            'max' => 'nullable|integer|min:0',
            'min' => 'nullable|integer|min:0',
        ]);

        $itemOption->update($validated);

        return response()->json($itemOption->load(['item', 'option']));
    }

    #[OA\Delete(
        path: "/item-options/{id}",
        summary: "Delete an item option",
        description: "Delete an item option",
        tags: ["Item Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Item option ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Item option deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Item option not found")
        ]
    )]
    public function destroy(ItemOption $itemOption): JsonResponse
    {
        $itemOption->delete();

        return response()->json(null, 204);
    }
}
