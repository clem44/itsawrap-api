<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StatusController extends Controller
{
    #[OA\Get(
        path: "/statuses",
        summary: "List all statuses",
        description: "Get all order statuses",
        tags: ["Statuses"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of statuses", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Status"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Status::all());
    }

    #[OA\Post(
        path: "/statuses",
        summary: "Create a status",
        description: "Create a new order status",
        tags: ["Statuses"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Pending"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Order is pending"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Status created", content: new OA\JsonContent(ref: "#/components/schemas/Status")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $status = Status::create($validated);

        return response()->json($status, 201);
    }

    #[OA\Get(
        path: "/statuses/{id}",
        summary: "Get a status",
        description: "Get a single status",
        tags: ["Statuses"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Status ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Status details", content: new OA\JsonContent(ref: "#/components/schemas/Status")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Status not found")
        ]
    )]
    public function show(Status $status): JsonResponse
    {
        return response()->json($status);
    }

    #[OA\Put(
        path: "/statuses/{id}",
        summary: "Update a status",
        description: "Update an existing status",
        tags: ["Statuses"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Status ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status updated", content: new OA\JsonContent(ref: "#/components/schemas/Status")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Status not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Status $status): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
        ]);

        $status->update($validated);

        return response()->json($status);
    }

    #[OA\Delete(
        path: "/statuses/{id}",
        summary: "Delete a status",
        description: "Delete a status",
        tags: ["Statuses"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Status ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Status deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Status not found")
        ]
    )]
    public function destroy(Status $status): JsonResponse
    {
        $status->delete();

        return response()->json(null, 204);
    }
}
