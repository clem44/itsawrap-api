<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionDependency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionDependencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OptionDependency::with(['parentOptionValue', 'childOption']);

        if ($request->has('parent_option_value_id')) {
            $query->where('parent_option_value_id', $request->parent_option_value_id);
        }

        if ($request->has('child_option_id')) {
            $query->where('child_option_id', $request->child_option_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_option_value_id' => 'required|exists:item_option_values,id',
            'child_option_id' => 'required|exists:item_options,id',
        ]);

        $optionDependency = OptionDependency::create($validated);

        return response()->json($optionDependency->load(['parentOptionValue', 'childOption']), 201);
    }

    public function show(OptionDependency $optionDependency): JsonResponse
    {
        return response()->json($optionDependency->load(['parentOptionValue', 'childOption']));
    }

    public function update(Request $request, OptionDependency $optionDependency): JsonResponse
    {
        $validated = $request->validate([
            'parent_option_value_id' => 'exists:item_option_values,id',
            'child_option_id' => 'exists:item_options,id',
        ]);

        $optionDependency->update($validated);

        return response()->json($optionDependency->load(['parentOptionValue', 'childOption']));
    }

    public function destroy(OptionDependency $optionDependency): JsonResponse
    {
        $optionDependency->delete();

        return response()->json(null, 204);
    }
}
