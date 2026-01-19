<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OptionController extends Controller
{
    #[OA\Get(
        path: "/options",
        summary: "List all options",
        description: "Get all options with their values",
        tags: ["Options"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of options", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Option"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Option::with('optionValues')->get());
    }

    #[OA\Post(
        path: "/options",
        summary: "Create an option",
        description: "Create a new option with values",
        tags: ["Options"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Size"),
                    new OA\Property(
                        property: "values",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "name", type: "string", example: "Large"),
                                new OA\Property(property: "price", type: "number", example: 2.00),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Option created", content: new OA\JsonContent(ref: "#/components/schemas/Option")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'values' => 'array',
            'values.*.name' => 'required|string|max:255',
            'values.*.price' => 'numeric|min:0',
        ]);

        $option = Option::create(['name' => $validated['name']]);

        if (isset($validated['values'])) {
            foreach ($validated['values'] as $value) {
                $option->optionValues()->create($value);
            }
        }

        return response()->json($option->load('optionValues'), 201);
    }

    #[OA\Get(
        path: "/options/{id}",
        summary: "Get an option",
        description: "Get a single option with its values",
        tags: ["Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Option details", content: new OA\JsonContent(ref: "#/components/schemas/Option")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option not found")
        ]
    )]
    public function show(Option $option): JsonResponse
    {
        return response()->json($option->load('optionValues'));
    }

    #[OA\Put(
        path: "/options/{id}",
        summary: "Update an option",
        description: "Update an existing option",
        tags: ["Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Option updated", content: new OA\JsonContent(ref: "#/components/schemas/Option")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Option $option): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
        ]);

        $option->update($validated);

        return response()->json($option->load('optionValues'));
    }

    #[OA\Delete(
        path: "/options/{id}",
        summary: "Delete an option",
        description: "Delete an option",
        tags: ["Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Option deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option not found")
        ]
    )]
    public function destroy(Option $option): JsonResponse
    {
        $option->delete();

        return response()->json(null, 204);
    }
}
