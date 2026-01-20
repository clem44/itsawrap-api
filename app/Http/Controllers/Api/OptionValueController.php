<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OptionValueController extends Controller
{
    #[OA\Get(
        path: "/option-values",
        summary: "List option values",
        description: "Get all option values, optionally filtered by option",
        tags: ["Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "option_id", in: "query", required: false, description: "Filter by option ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of option values", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/OptionValue"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OptionValue::with('option');

        if ($request->has('option_id')) {
            $query->where('option_id', $request->option_id);
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/option-values",
        summary: "Create an option value",
        description: "Create a new option value",
        tags: ["Option Values"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["option_id", "name"],
                properties: [
                    new OA\Property(property: "option_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Large"),
                    new OA\Property(property: "price", type: "number", example: 2.00),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Option value created", content: new OA\JsonContent(ref: "#/components/schemas/OptionValue")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'option_id' => 'required|exists:options,id',
            'name' => 'required|string|max:255',
            'price' => 'numeric|min:0',
        ]);

        $optionValue = OptionValue::create($validated);

        return response()->json($optionValue, 201);
    }

    #[OA\Get(
        path: "/option-values/{id}",
        summary: "Get an option value",
        description: "Get a single option value",
        tags: ["Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option value ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Option value details", content: new OA\JsonContent(ref: "#/components/schemas/OptionValue")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option value not found")
        ]
    )]
    public function show(OptionValue $optionValue): JsonResponse
    {
        return response()->json($optionValue->load('option'));
    }

    #[OA\Put(
        path: "/option-values/{id}",
        summary: "Update an option value",
        description: "Update an existing option value",
        tags: ["Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option value ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Option value updated", content: new OA\JsonContent(ref: "#/components/schemas/OptionValue")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option value not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, OptionValue $optionValue): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
        ]);

        $optionValue->update($validated);

        return response()->json($optionValue);
    }

    #[OA\Delete(
        path: "/option-values/{id}",
        summary: "Delete an option value",
        description: "Delete an option value",
        tags: ["Option Values"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Option value ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Option value deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Option value not found")
        ]
    )]
    public function destroy(OptionValue $optionValue): JsonResponse
    {
        $optionValue->delete();

        return response()->json(null, 204);
    }
}
