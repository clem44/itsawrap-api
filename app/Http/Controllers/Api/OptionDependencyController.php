<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionDependency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OptionDependencyController extends Controller
{
    #[OA\Get(
        path: "/option-dependencies",
        summary: "List option dependencies",
        description: "Get all option dependencies, optionally filtered by parent or child",
        tags: ["Option Dependencies"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "parent_option_value_id", in: "query", required: false, description: "Filter by parent ItemOptionValue ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "child_option_id", in: "query", required: false, description: "Filter by child ItemOption ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of option dependencies", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/OptionDependency"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OptionDependency::with(['parentOptionValue', 'childOption']);

        if ($request->has('parent_option_value_id')) {
            $query->where('parent_option_value_id', $request->parent_option_value_id);
        }

        if ($request->has('child_option_id')) {
            $query->where('child_option_id', $request->child_option_id);
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/option-dependencies",
        summary: "Create an option dependency",
        description: "Create a dependency between a parent ItemOptionValue and a child ItemOption",
        tags: ["Option Dependencies"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["parent_option_value_id", "child_option_id"],
                properties: [
                    new OA\Property(property: "parent_option_value_id", type: "integer", example: 10),
                    new OA\Property(property: "child_option_id", type: "integer", example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Option dependency created", content: new OA\JsonContent(ref: "#/components/schemas/OptionDependency")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_option_value_id' => 'required|exists:item_option_values,id',
            'child_option_id' => 'required|exists:item_options,id',
        ]);

        $optionDependency = OptionDependency::create($validated);

        return response()->json($optionDependency->load(['parentOptionValue', 'childOption']), 201);
    }

    #[OA\Get(
        path: "/option-dependencies/{id}",
        summary: "Get an option dependency",
        description: "Get a single option dependency with relationships",
        tags: ["Option Dependencies"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option dependency ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Option dependency details", content: new OA\JsonContent(ref: "#/components/schemas/OptionDependency")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option dependency not found")
        ]
    )]
    public function show(OptionDependency $optionDependency): JsonResponse
    {
        return response()->json($optionDependency->load(['parentOptionValue', 'childOption']));
    }

    #[OA\Put(
        path: "/option-dependencies/{id}",
        summary: "Update an option dependency",
        description: "Update an existing option dependency",
        tags: ["Option Dependencies"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option dependency ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "parent_option_value_id", type: "integer"),
                    new OA\Property(property: "child_option_id", type: "integer"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Option dependency updated", content: new OA\JsonContent(ref: "#/components/schemas/OptionDependency")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option dependency not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, OptionDependency $optionDependency): JsonResponse
    {
        $validated = $request->validate([
            'parent_option_value_id' => 'exists:item_option_values,id',
            'child_option_id' => 'exists:item_options,id',
        ]);

        $optionDependency->update($validated);

        return response()->json($optionDependency->load(['parentOptionValue', 'childOption']));
    }

    #[OA\Delete(
        path: "/option-dependencies/{id}",
        summary: "Delete an option dependency",
        description: "Delete an option dependency",
        tags: ["Option Dependencies"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option dependency ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Option dependency deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option dependency not found")
        ]
    )]
    public function destroy(OptionDependency $optionDependency): JsonResponse
    {
        $optionDependency->delete();

        return response()->json(null, 204);
    }
}
