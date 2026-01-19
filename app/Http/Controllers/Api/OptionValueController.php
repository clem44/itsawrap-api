<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionValueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OptionValue::with('option');

        if ($request->has('option_id')) {
            $query->where('option_id', $request->option_id);
        }

        return response()->json($query->get());
    }

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

    public function show(OptionValue $optionValue): JsonResponse
    {
        return response()->json($optionValue->load('option'));
    }

    public function update(Request $request, OptionValue $optionValue): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
        ]);

        $optionValue->update($validated);

        return response()->json($optionValue);
    }

    public function destroy(OptionValue $optionValue): JsonResponse
    {
        $optionValue->delete();

        return response()->json(null, 204);
    }
}
