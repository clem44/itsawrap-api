<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemOptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ItemOption::with(['item', 'option', 'itemOptionValues.optionValue']);

        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'option_id' => 'required|exists:options,id',
            'required' => 'boolean',
            'type' => 'string|in:single,multiple',
            'range' => 'integer|min:0',
            'max' => 'nullable|integer|min:0',
            'min' => 'nullable|integer|min:0',
        ]);

        $itemOption = ItemOption::create($validated);

        return response()->json($itemOption->load(['item', 'option']), 201);
    }

    public function show(ItemOption $itemOption): JsonResponse
    {
        return response()->json($itemOption->load(['item', 'option', 'itemOptionValues.optionValue']));
    }

    public function update(Request $request, ItemOption $itemOption): JsonResponse
    {
        $validated = $request->validate([
            'required' => 'boolean',
            'type' => 'string|in:single,multiple',
            'range' => 'integer|min:0',
            'max' => 'nullable|integer|min:0',
            'min' => 'nullable|integer|min:0',
        ]);

        $itemOption->update($validated);

        return response()->json($itemOption->load(['item', 'option']));
    }

    public function destroy(ItemOption $itemOption): JsonResponse
    {
        $itemOption->delete();

        return response()->json(null, 204);
    }
}
