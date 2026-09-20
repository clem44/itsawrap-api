<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OptionController extends Controller
{
    #[OA\Get(
        path: '/options',
        summary: 'List all options',
        description: 'Get all options with their values',
        tags: ['Options'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of options', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Option'))),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $options = Option::query()
            ->with(['optionValues' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG)])
            ->withMedia(Option::IMAGE_TAG)
            ->get()
            ->each(fn (Option $option) => $this->includePrimaryMedia($option));

        return response()->json($options);
    }

    #[OA\Post(
        path: '/options',
        summary: 'Create an option',
        description: 'Create a new option with values',
        tags: ['Options'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Size'),
                    new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Choose a size'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Select the portion size for this item.'),
                    new OA\Property(property: 'media_id', type: 'integer', nullable: true, description: 'Media library asset to attach as the primary image'),
                    new OA\Property(
                        property: 'values',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Large'),
                                new OA\Property(property: 'price', type: 'number', example: 2.00),
                                new OA\Property(property: 'media_id', type: 'integer', nullable: true, description: 'Media library asset to attach as the primary image'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Option created', content: new OA\JsonContent(ref: '#/components/schemas/Option')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_id' => 'nullable|integer|exists:media,id',
            'values' => 'array',
            'values.*.name' => 'required|string|max:255',
            'values.*.price' => 'numeric|min:0',
            'values.*.media_id' => 'nullable|integer|exists:media,id',
        ]);

        $option = Option::create([
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);
        $this->syncPrimaryImage($option, $request);

        if (isset($validated['values'])) {
            foreach ($validated['values'] as $index => $value) {
                unset($value['media_id']);

                $optionValue = $option->optionValues()->create($value);
                $this->syncPrimaryImage($optionValue, $request, "values.{$index}.media_id");
            }
        }

        $option->load(['optionValues' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG)]);
        $option->loadMedia(Option::IMAGE_TAG);
        $this->includePrimaryMedia($option);

        return response()->json($option, 201);
    }

    #[OA\Get(
        path: '/options/{id}',
        summary: 'Get an option',
        description: 'Get a single option with its values',
        tags: ['Options'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Option ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Option details', content: new OA\JsonContent(ref: '#/components/schemas/Option')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Option not found'),
        ]
    )]
    public function show(Option $option): JsonResponse
    {
        $option->load(['optionValues' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG)]);
        $option->loadMedia(Option::IMAGE_TAG);
        $this->includePrimaryMedia($option);

        return response()->json($option);
    }

    #[OA\Put(
        path: '/options/{id}',
        summary: 'Update an option',
        description: 'Update an existing option',
        tags: ['Options'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Option ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'title', type: 'string', nullable: true),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'media_id', type: 'integer', nullable: true, description: 'Media library asset to attach as the primary image'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Option updated', content: new OA\JsonContent(ref: '#/components/schemas/Option')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Option not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Option $option): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        unset($validated['media_id']);

        $option->update($validated);
        $this->syncPrimaryImage($option, $request);

        $option->load(['optionValues' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG)]);
        $option->loadMedia(Option::IMAGE_TAG);
        $this->includePrimaryMedia($option);

        return response()->json($option);
    }

    private function syncPrimaryImage(Option|OptionValue $model, Request $request, string $key = 'media_id'): void
    {
        if (! $request->has($key)) {
            return;
        }

        if ($request->filled($key)) {
            $model->syncMedia((int) $request->input($key), $model::IMAGE_TAG);

            return;
        }

        $model->detachMediaTags($model::IMAGE_TAG);
    }

    private function includePrimaryMedia(Option $option): void
    {
        $option->includePrimaryImageMedia();
        $option->optionValues->each->includePrimaryImageMedia();
    }

    #[OA\Delete(
        path: '/options/{id}',
        summary: 'Delete an option',
        description: 'Delete an option',
        tags: ['Options'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Option ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Option deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Option not found'),
        ]
    )]
    public function destroy(Option $option): JsonResponse
    {
        $option->delete();

        return response()->json(null, 204);
    }
}
