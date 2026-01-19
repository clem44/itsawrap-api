<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: "/categories",
        summary: "List all categories",
        description: "Get all categories ordered by sort_order",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of categories",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Category")
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Category::orderBy('sort_order')->get());
    }

    #[OA\Post(
        path: "/categories",
        summary: "Create a category",
        description: "Create a new category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Wraps"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Delicious wraps"),
                    new OA\Property(property: "icon", type: "string", nullable: true, example: "wrap-icon"),
                    new OA\Property(property: "color", type: "string", nullable: true, example: "#FF5733"),
                    new OA\Property(property: "sort_order", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Category created",
                content: new OA\JsonContent(ref: "#/components/schemas/Category")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'sort_order' => 'integer',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    #[OA\Get(
        path: "/categories/{id}",
        summary: "Get a category",
        description: "Get a single category with its items",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Category ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Category details",
                content: new OA\JsonContent(ref: "#/components/schemas/Category")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load('items'));
    }

    #[OA\Put(
        path: "/categories/{id}",
        summary: "Update a category",
        description: "Update an existing category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Category ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Wraps"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "icon", type: "string", nullable: true),
                    new OA\Property(property: "color", type: "string", nullable: true),
                    new OA\Property(property: "sort_order", type: "integer"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Category updated",
                content: new OA\JsonContent(ref: "#/components/schemas/Category")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'sort_order' => 'integer',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    #[OA\Delete(
        path: "/categories/{id}",
        summary: "Delete a category",
        description: "Delete a category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Category ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Category deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }
}
