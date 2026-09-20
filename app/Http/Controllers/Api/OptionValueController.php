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
        path: '/option-values',
        summary: 'List option values',
        description: 'Get all option values, optionally filtered by option',
        tags: ['Option Values'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'option_id', in: 'query', required: false, description: 'Filter by option ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of option values', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OptionValue'))),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OptionValue::query()
            ->with('option')
            ->withMedia(OptionValue::IMAGE_TAG);

        if ($request->has('option_id')) {
            $query->where('option_id', $request->option_id);
        }

        $optionValues = $query->get()
            ->each->includePrimaryImageMedia();

        return response()->json($optionValues);
    }

    #[OA\Post(
        path: '/option-values',
        summary: 'Create an option value',
        description: 'Create a new option value',
        tags: ['Option Values'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['option_id', 'name'],
                properties: [
                    new OA\Property(property: 'option_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Large'),
                    new OA\Property(property: 'price', type: 'number', example: 2.00),
                    new OA\Property(property: 'media_id', type: 'integer', nullable: true, description: 'Media library asset to attach as the primary image'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Option value created', content: new OA\JsonContent(ref: '#/components/schemas/OptionValue')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'option_id' => 'required|exists:options,id',
            'name' => 'required|string|max:255',
            'price' => 'numeric|min:0',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        unset($validated['media_id']);

        $optionValue = OptionValue::create($validated);
        $this->syncPrimaryImage($optionValue, $request);
        $optionValue->loadMedia(OptionValue::IMAGE_TAG);
        $optionValue->includePrimaryImageMedia();

        return response()->json($optionValue, 201);
    }

    #[OA\Get(
        path: '/option-values/{id}',
        summary: 'Get an option value',
        description: 'Get a single option value',
        tags: ['Option Values'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Option value ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Option value details', content: new OA\JsonContent(ref: '#/components/schemas/OptionValue')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Option value not found'),
        ]
    )]
    public function show(OptionValue $optionValue): JsonResponse
    {
        $optionValue->load('option');
        $optionValue->loadMedia(OptionValue::IMAGE_TAG);
        $optionValue->includePrimaryImageMedia();

        return response()->json($optionValue);
    }

    #[OA\Put(
        path: '/option-values/{id}',
        summary: 'Update an option value',
        description: 'Update an existing option value',
        tags: ['Option Values'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Option value ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'media_id', type: 'integer', nullable: true, description: 'Media library asset to attach as the primary image'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Option value updated', content: new OA\JsonContent(ref: '#/components/schemas/OptionValue')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Option value not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, OptionValue $optionValue): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        unset($validated['media_id']);

        $optionValue->update($validated);
        $this->syncPrimaryImage($optionValue, $request);
        $optionValue->loadMedia(OptionValue::IMAGE_TAG);
        $optionValue->includePrimaryImageMedia();

        return response()->json($optionValue);
    }

    private function syncPrimaryImage(OptionValue $optionValue, Request $request): void
    {
        if (! $request->has('media_id')) {
            return;
        }

        if ($request->filled('media_id')) {
            $optionValue->syncMedia((int) $request->input('media_id'), OptionValue::IMAGE_TAG);

            return;
        }

        $optionValue->detachMediaTags(OptionValue::IMAGE_TAG);
    }

    #[OA\Delete(
        path: '/option-values/{id}',
        summary: 'Delete an option value',
        description: 'Delete an option value',
        tags: ['Option Values'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Option value ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Option value deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Option value not found'),
        ]
    )]
    public function destroy(OptionValue $optionValue): JsonResponse
    {
        $optionValue->delete();

        return response()->json(null, 204);
    }
}
