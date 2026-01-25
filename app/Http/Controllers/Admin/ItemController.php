<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\OptionDependency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(): View
    {
        $items = Item::query()
            ->with(['category', 'itemOptions.option.optionValues.optionDependencies'])
            ->withCount(['itemOptions', 'orderItems'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();
        $options = Option::orderBy('name')->get();

        return view('admin.items.index', compact('items', 'categories', 'options'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0|decimal:0,2',
            'category_id' => 'required|exists:categories,id',
            'image_path' => 'nullable|string|max:500',
            'short_code' => 'nullable|string|max:50',
            'active' => 'nullable|boolean',
            'options' => 'nullable|array',
            'options.*' => 'integer|exists:options,id',
        ]);

        $validated['active'] = $request->has('active');

        $item = Item::create($validated);

        // Attach options to item if provided
        if (!empty($request->input('options'))) {
            foreach ($request->input('options') as $optionId) {
                $item->itemOptions()->create([
                    'option_id' => $optionId,
                    'required' => false,
                ]);
            }
        }

        return redirect()->route('admin.items.index')
            ->with('success', 'Item created successfully.');
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0|decimal:0,2',
            'category_id' => 'required|exists:categories,id',
            'image_path' => 'nullable|string|max:500',
            'short_code' => 'nullable|string|max:50',
            'active' => 'nullable|boolean',
            'options' => 'nullable|array',
            'options.*' => 'integer|exists:options,id',
        ]);

        $validated['active'] = $request->has('active');

        $item->update($validated);

        // Sync options
        $item->itemOptions()->delete();
        if (!empty($request->input('options'))) {
            foreach ($request->input('options') as $optionId) {
                $item->itemOptions()->create([
                    'option_id' => $optionId,
                    'required' => false,
                ]);
            }
        }

        return redirect()->route('admin.items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        if ($item->orderItems()->exists()) {
            return redirect()->route('admin.items.index')
                ->with('error', 'Cannot delete an item that has been ordered.');
        }

        $item->itemOptions()->delete();
        $item->delete();

        return redirect()->route('admin.items.index')
            ->with('success', 'Item deleted successfully.');
    }

    public function updateOptionValues(Request $request, Item $item)
    {
        $validated = $request->validate([
            'values' => 'required|array',
            'values.*' => 'numeric|min:0|decimal:0,2',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'array',
            'dependencies.*.*' => 'integer|exists:options,id',
        ]);

        // Update option value prices
        foreach ($validated['values'] as $optionValueId => $price) {
            OptionValue::where('id', $optionValueId)->update(['price' => $price]);
        }

        // Update dependencies if provided
        if (!empty($validated['dependencies'])) {
            foreach ($validated['dependencies'] as $optionValueId => $childOptionIds) {
                // Delete existing dependencies for this option value
                OptionDependency::where('parent_option_value_id', $optionValueId)->delete();
                
                // Create new dependencies
                foreach ($childOptionIds as $childOptionId) {
                    OptionDependency::create([
                        'parent_option_value_id' => $optionValueId,
                        'child_option_id' => $childOptionId,
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
