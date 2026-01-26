<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\OptionDependency;
use App\Models\ItemOption;
use App\Models\ItemOptionValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $items = Item::query()
            ->with([
                'category',
                'itemOptions.option.optionValues',
                'itemOptions.itemOptionValues.parentDependencies.childOption.itemOptionValues.optionValue',
            ])
            ->withCount([
                'itemOptions as item_options_count' => function ($q) {
                    $q->where(function ($query) {
                        $query->whereNull('type')
                            ->orWhere('type', '!=', 'dependent');
                    });
                },
                'itemOptions',
                'orderItems',
            ])
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->category_id);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();
        $options = Option::with('optionValues')->orderBy('name')->get();

        $allOptions = $options->map(fn($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'optionValues' => $o->optionValues
                ->map(fn($ov) => [
                    'id' => $ov->id,
                    'name' => $ov->name,
                    'price' => $ov->price,
                ])->values()->toArray(),
        ])->values();

        $itemsData = $items->getCollection()->map(fn($item) => [
            'id' => $item->id,
            'itemOptions' => $item->itemOptions->map(fn($io) => [
                'id' => $io->option_id,
                'itemOptionId' => $io->id,
                'name' => $io->option->name,
                'type' => $io->type,
                'range' => (bool) $io->range,
                'max' => $io->max,
                'min' => $io->min,
                'qty' => $io->qty,
                'enable_qty' => (bool) $io->enable_qty,
                'optionValues' => $io->option->optionValues->map(function ($ov) use ($io) {
                    $itemOptionValue = $io->itemOptionValues->firstWhere('option_value_id', $ov->id);

                    return [
                        'id' => $ov->id,
                        'name' => $ov->name,
                        'price' => $itemOptionValue?->price ?? $ov->price,
                        'optionDependencies' => (
                            ($itemOptionValue?->parentDependencies ?? collect())
                                ->map(fn($od) => [
                                    'childOptionId' => $od->childOption?->option_id,
                                    'childItemOptionId' => $od->child_option_id,
                                    'optionValues' => $od->childOption
                                        ?->itemOptionValues
                                        ->map(fn($iov) => [
                                            'id' => $iov->option_value_id,
                                            'itemOptionValueId' => $iov->id,
                                            'name' => $iov->optionValue?->name,
                                            'price' => $iov->price,
                                        ])->values()->toArray() ?? [],
                                ])
                                ->filter(fn($dep) => !is_null($dep['childOptionId']))
                        )->values()->toArray(),
                    ];
                })->values()->toArray(),
            ])->values()->toArray(),
        ])->values();

        return view('admin.items.index', compact('items', 'categories', 'options', 'allOptions', 'itemsData'));
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
            'active' => 'nullable',
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
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cost' => 'required|numeric|min:0|decimal:0,2',
                'category_id' => 'required|exists:categories,id',
                'short_code' => 'nullable|string|max:50',
                'active' => 'nullable',
                'options' => 'nullable|array',
                'options.*' => 'integer|exists:options,id',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput($request->all() + [
                        'form_action' => 'edit',
                        'edit_id' => $item->id,
                    ]);
            }

            $validated = $validator->validated();

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
            //dd($item, "item should be saved");

            return redirect()->route('admin.items.index')
                ->with('success', 'Item updated successfully.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['update' => 'Update failed. Please check the form values and try again.'])
                ->withInput($request->all() + [
                    'form_action' => 'edit',
                    'edit_id' => $item->id,
                ]);
        }
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
            'dependency_values' => 'nullable|array',
            'dependency_values.*' => 'numeric|min:0|decimal:0,2',
            'dependency_value_overrides' => 'nullable|array',
        ]);

        // Update item option value prices (per item, not base OptionValue)
        foreach ($validated['values'] as $optionValueId => $price) {
            $optionValue = OptionValue::find($optionValueId);
            if (!$optionValue) {
                continue;
            }

            $itemOption = $item->itemOptions()
                ->where('option_id', $optionValue->option_id)
                ->first();

            if (!$itemOption) {
                continue;
            }

            $itemOption->itemOptionValues()->updateOrCreate(
                ['option_value_id' => $optionValueId],
                ['price' => $price]
            );
        }

        // Update dependent item option value prices if provided
        if (!empty($validated['dependency_values'])) {
            foreach ($validated['dependency_values'] as $itemOptionValueId => $price) {
                ItemOptionValue::where('id', $itemOptionValueId)->update(['price' => $price]);
            }
        }

        // Update dependencies if provided
        if (!empty($validated['dependencies'])) {
            foreach ($validated['dependencies'] as $optionValueId => $childOptionIds) {
                // Resolve the parent ItemOptionValue for this item and option value
                $optionValue = OptionValue::find($optionValueId);
                if (!$optionValue) {
                    continue;
                }

                $parentItemOption = $item->itemOptions()
                    ->where('option_id', $optionValue->option_id)
                    ->first();

                if (!$parentItemOption) {
                    continue;
                }

                $parentItemOptionValue = $parentItemOption->itemOptionValues()->firstOrCreate(
                    ['option_value_id' => $optionValueId],
                    []
                );

                $existingChildOptionIds = OptionDependency::where('parent_option_value_id', $parentItemOptionValue->id)
                    ->with('childOption')
                    ->get()
                    ->map(fn($dep) => $dep->childOption?->option_id)
                    ->filter()
                    ->values()
                    ->all();

                $normalizedChildOptionIds = collect($childOptionIds)
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $toAdd = array_values(array_diff($normalizedChildOptionIds, $existingChildOptionIds));
                $toRemove = array_values(array_diff($existingChildOptionIds, $normalizedChildOptionIds));

                if (!empty($toRemove)) {
                    OptionDependency::where('parent_option_value_id', $parentItemOptionValue->id)
                        ->whereHas('childOption', fn($q) => $q->whereIn('option_id', $toRemove))
                        ->delete();
                }

                // For each new child option, create ItemOption and ItemOptionValue records
                foreach ($toAdd as $childOptionId) {
                    $parentOverrides = $validated['dependency_value_overrides'][$optionValueId] ?? [];
                    $childOverrides = $parentOverrides[$childOptionId] ?? [];

                    // Find or create ItemOption for this child option
                    $itemOption = $item->itemOptions()->create([
                        'option_id' => $childOptionId,
                        'required' => false,
                        'type' => 'dependent',
                        'range' => null,
                        'min' => null,
                        'max' => null,
                    ]);
                    
                    // Get all option values for the child option
                    $childOption = Option::with('optionValues')->find($childOptionId);
                    
                    if ($childOption) {
                        // Create ItemOptionValue for each value with type 'dependent'
                        foreach ($childOption->optionValues as $childOptionValue) {
                            $overridePrice = $childOverrides[$childOptionValue->id] ?? null;
                            $itemOption->itemOptionValues()->updateOrCreate(
                                ['option_value_id' => $childOptionValue->id],
                                ['price' => $overridePrice ?? $childOptionValue->price]
                            );
                        }
                    }
                    
                    // Create the dependency
                    OptionDependency::create([
                        'parent_option_value_id' => $parentItemOptionValue->id,
                        'child_option_id' => $itemOption->id,
                    ]);
                }
            }
        }

        // Remove dependent item options with no remaining dependencies
        ItemOption::where('item_id', $item->id)
            ->where('type', 'dependent')
            ->whereDoesntHave('childDependencies')
            ->delete();

        return response()->json(['success' => true]);
    }

    public function updateItemOptionQty(Request $request, Item $item, ItemOption $itemOption): JsonResponse
    {
        if ($itemOption->item_id !== $item->id) {
            abort(404);
        }

        $validated = $request->validate([
            'enable_qty' => 'nullable|boolean',
            'range' => 'nullable|boolean',
        ]);

        $itemOption->update([
            'enable_qty' => (bool) ($validated['enable_qty'] ?? false),
            'range'      => (bool) ($validated['range'] ?? false),
        ]);

        if (!$itemOption->enable_qty) {
            $itemOption->itemOptionValues()->update(['qty' => null]);
        }

        return response()->json([
            'success' => true,
            'enable_qty' => $itemOption->enable_qty,
            'range' => $itemOption->range,
        ]);
    }

    public function destroyItemOption(Item $item, ItemOption $itemOption): JsonResponse
    {
        if ($itemOption->item_id !== $item->id) {
            abort(404);
        }

        $itemOption->delete();

        return response()->json(['success' => true]);
    }
}
