<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(): View
    {
        $items = Item::query()
            ->with(['category', 'itemOptions.option'])
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
}
