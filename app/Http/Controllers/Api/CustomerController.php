<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CustomerController extends Controller
{
    #[OA\Get(
        path: "/customers",
        summary: "List all customers",
        description: "Get all customers with optional search",
        tags: ["Customers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, description: "Search by name, phone, or email", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of customers", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Customer"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->get());
    }

    #[OA\Post(
        path: "/customers",
        summary: "Create a customer",
        description: "Create a new customer",
        tags: ["Customers"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "firstname", type: "string", nullable: true, example: "John"),
                    new OA\Property(property: "lastname", type: "string", nullable: true, example: "Doe"),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "555-1234"),
                    new OA\Property(property: "email", type: "string", nullable: true, example: "john@example.com"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Customer created", content: new OA\JsonContent(ref: "#/components/schemas/Customer")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    #[OA\Get(
        path: "/customers/{id}",
        summary: "Get a customer",
        description: "Get a single customer with their orders",
        tags: ["Customers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Customer details", content: new OA\JsonContent(ref: "#/components/schemas/Customer")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Customer not found")
        ]
    )]
    public function show(Customer $customer): JsonResponse
    {
        return response()->json($customer->load('orders'));
    }

    #[OA\Put(
        path: "/customers/{id}",
        summary: "Update a customer",
        description: "Update an existing customer",
        tags: ["Customers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "firstname", type: "string", nullable: true),
                    new OA\Property(property: "lastname", type: "string", nullable: true),
                    new OA\Property(property: "phone", type: "string", nullable: true),
                    new OA\Property(property: "email", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Customer updated", content: new OA\JsonContent(ref: "#/components/schemas/Customer")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Customer not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    #[OA\Delete(
        path: "/customers/{id}",
        summary: "Delete a customer",
        description: "Delete a customer",
        tags: ["Customers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Customer deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Customer not found")
        ]
    )]
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
