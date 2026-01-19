<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SettingController extends Controller
{
    #[OA\Get(
        path: "/settings",
        summary: "List all settings",
        description: "Get all application settings",
        tags: ["Settings"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of settings", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Setting"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Setting::all());
    }

    #[OA\Get(
        path: "/settings/{key}",
        summary: "Get a setting",
        description: "Get a single setting by key",
        tags: ["Settings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "key", in: "path", required: true, description: "Setting key", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Setting details", content: new OA\JsonContent(ref: "#/components/schemas/Setting")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Setting not found")
        ]
    )]
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json($setting);
    }

    #[OA\Put(
        path: "/settings/{key}",
        summary: "Update a setting",
        description: "Update a setting value by key",
        tags: ["Settings"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "key", in: "path", required: true, description: "Setting key", schema: new OA\Schema(type: "string"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "value", type: "string", nullable: true, example: "My Store Name"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Setting updated", content: new OA\JsonContent(ref: "#/components/schemas/Setting")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'nullable|string',
        ]);

        Setting::setValue($key, $validated['value']);

        return response()->json(Setting::where('key', $key)->first());
    }

    #[OA\Post(
        path: "/settings/bulk",
        summary: "Bulk update settings",
        description: "Update multiple settings at once",
        tags: ["Settings"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["settings"],
                properties: [
                    new OA\Property(
                        property: "settings",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "key", type: "string", example: "store_name"),
                                new OA\Property(property: "value", type: "string", nullable: true, example: "My Store"),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Settings updated", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Setting"))),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $setting) {
            Setting::setValue($setting['key'], $setting['value']);
        }

        return response()->json(Setting::all());
    }
}
