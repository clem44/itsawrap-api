<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TaxController extends Controller
{
    #[OA\Get(
        path: "/taxes",
        summary: "List all taxes",
        description: "Get all tax configurations",
        tags: ["Taxes"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of taxes", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Tax"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Tax::all());
    }

    #[OA\Post(
        path: "/taxes",
        summary: "Create a tax",
        description: "Create a new tax configuration",
        tags: ["Taxes"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Sales Tax"),
                    new OA\Property(property: "value", type: "number", example: 8.25),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "type", type: "string", enum: ["percentage", "fixed"], example: "percentage"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tax created", content: new OA\JsonContent(ref: "#/components/schemas/Tax")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'numeric|min:0',
            'description' => 'nullable|string',
            'type' => 'string|in:percentage,fixed',
        ]);

        $tax = Tax::create($validated);

        return response()->json($tax, 201);
    }

    #[OA\Get(
        path: "/taxes/{id}",
        summary: "Get a tax",
        description: "Get a single tax with its items",
        tags: ["Taxes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Tax ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Tax details", content: new OA\JsonContent(ref: "#/components/schemas/Tax")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Tax not found")
        ]
    )]
    public function show(Tax $tax): JsonResponse
    {
        return response()->json($tax->load('items'));
    }

    #[OA\Put(
        path: "/taxes/{id}",
        summary: "Update a tax",
        description: "Update an existing tax",
        tags: ["Taxes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Tax ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "value", type: "number"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "type", type: "string", enum: ["percentage", "fixed"]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Tax updated", content: new OA\JsonContent(ref: "#/components/schemas/Tax")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Tax not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Tax $tax): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'value' => 'numeric|min:0',
            'description' => 'nullable|string',
            'type' => 'string|in:percentage,fixed',
        ]);

        $tax->update($validated);

        return response()->json($tax);
    }

    #[OA\Delete(
        path: "/taxes/{id}",
        summary: "Delete a tax",
        description: "Delete a tax",
        tags: ["Taxes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Tax ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Tax deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Tax not found")
        ]
    )]
    public function destroy(Tax $tax): JsonResponse
    {
        $tax->delete();

        return response()->json(null, 204);
    }
}
