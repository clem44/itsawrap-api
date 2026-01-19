<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemOptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemOptionValueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ItemOptionValue::with(['itemOption', 'optionValue']);

        if ($request->has('item_option_id')) {
            $query->where('item_option_id', $request->item_option_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_option_id' => 'required|exists:item_options,id',
            'option_value_id' => 'required|exists:option_values,id',
            'price' => 'numeric|min:0',
            'in_stock' => 'boolean',
            'option_dependency_id' => 'nullable|integer',
        ]);

        $itemOptionValue = ItemOptionValue::create($validated);

        return response()->json($itemOptionValue->load(['itemOption', 'optionValue']), 201);
    }

    public function show(ItemOptionValue $itemOptionValue): JsonResponse
    {
        return response()->json($itemOptionValue->load(['itemOption', 'optionValue']));
    }

    public function update(Request $request, ItemOptionValue $itemOptionValue): JsonResponse
    {
        $validated = $request->validate([
            'price' => 'numeric|min:0',
            'in_stock' => 'boolean',
            'option_dependency_id' => 'nullable|integer',
        ]);

        $itemOptionValue->update($validated);

        return response()->json($itemOptionValue);
    }

    public function destroy(ItemOptionValue $itemOptionValue): JsonResponse
    {
        $itemOptionValue->delete();

        return response()->json(null, 204);
    }
}
