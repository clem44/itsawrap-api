<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BranchController extends Controller
{
    #[OA\Get(
        path: "/branches",
        summary: "List all branches",
        description: "Get all branches with optional filtering",
        tags: ["Branches"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "active", in: "query", required: false, description: "Filter by active status", schema: new OA\Schema(type: "boolean"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of branches", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Branch"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Branch::query();

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/branches",
        summary: "Create a branch",
        description: "Create a new branch",
        tags: ["Branches"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Downtown"),
                    new OA\Property(property: "address", type: "string", nullable: true, example: "123 Main St"),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "555-1234"),
                    new OA\Property(property: "active", type: "boolean", example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Branch created", content: new OA\JsonContent(ref: "#/components/schemas/Branch")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $branch = Branch::create($validated);

        return response()->json($branch, 201);
    }

    #[OA\Get(
        path: "/branches/{id}",
        summary: "Get a branch",
        description: "Get a single branch with its items",
        tags: ["Branches"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Branch ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Branch details", content: new OA\JsonContent(ref: "#/components/schemas/Branch")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Branch not found")
        ]
    )]
    public function show(Branch $branch): JsonResponse
    {
        return response()->json($branch->load('items'));
    }

    #[OA\Put(
        path: "/branches/{id}",
        summary: "Update a branch",
        description: "Update an existing branch",
        tags: ["Branches"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Branch ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "address", type: "string", nullable: true),
                    new OA\Property(property: "phone", type: "string", nullable: true),
                    new OA\Property(property: "active", type: "boolean"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Branch updated", content: new OA\JsonContent(ref: "#/components/schemas/Branch")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Branch not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $branch->update($validated);

        return response()->json($branch);
    }

    #[OA\Delete(
        path: "/branches/{id}",
        summary: "Delete a branch",
        description: "Delete a branch",
        tags: ["Branches"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Branch ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Branch deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Branch not found")
        ]
    )]
    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: "/branches/{id}/items",
        summary: "Sync branch items",
        description: "Sync items available at this branch",
        tags: ["Branches"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Branch ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["item_ids"],
                properties: [
                    new OA\Property(property: "item_ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Items synced", content: new OA\JsonContent(ref: "#/components/schemas/Branch")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Branch not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function syncItems(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:items,id',
        ]);

        $branch->items()->sync($validated['item_ids']);

        return response()->json($branch->load('items'));
    }
}
