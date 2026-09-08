<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionValue;
use App\Models\Option;
use App\Models\OptionDependency;
use App\Models\OptionValue;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request, MediaLibraryPresenter $mediaPresenter): View
    {
        $items = Item::query()
            ->withMedia('primary_image')
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

        $allOptions = $options->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'optionValues' => $o->optionValues
                ->map(fn ($ov) => [
                    'id' => $ov->id,
                    'name' => $ov->name,
                    'price' => $ov->price,
                ])->values()->toArray(),
        ])->values();

        $itemsData = $items->getCollection()->map(fn ($item) => [
            'id' => $item->id,
            'itemOptions' => $item->itemOptions->map(fn ($io) => [
                'id' => $io->option_id,
                'itemOptionId' => $io->id,
                'name' => $io->option->name,
                'sort_order' => $io->sort_order,
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
                        // The modal lists every value the option owns so they can
                        // be priced onto this item. Only the ones with an
                        // item_option_values row are actually offered to
                        // customers — without this flag an item whose values are
                        // missing looks identically configured to one that has
                        // them, because the price falls back to the option's own.
                        'attached' => $itemOptionValue !== null,
                        'price' => $itemOptionValue?->price ?? $ov->price,
                        'optionDependencies' => (
                            ($itemOptionValue?->parentDependencies ?? collect())
                                ->map(fn ($od) => [
                                    'childOptionId' => $od->childOption?->option_id,
                                    'childItemOptionId' => $od->child_option_id,
                                    'optionValues' => $od->childOption
                                        ?->itemOptionValues
                                        ->map(fn ($iov) => [
                                            'id' => $iov->option_value_id,
                                            'itemOptionValueId' => $iov->id,
                                            'name' => $iov->optionValue?->name,
                                            'price' => $iov->price,
                                        ])->values()->toArray() ?? [],
                                ])
                                ->filter(fn ($dep) => ! is_null($dep['childOptionId']))
                        )->values()->toArray(),
                    ];
                })->values()->toArray(),
            ])->values()->toArray(),
        ])->values();

        $itemEditData = $items->getCollection()
            ->mapWithKeys(function (Item $item) use ($mediaPresenter) {
                $primaryMedia = $item->firstMedia('primary_image');
                $data = $item->only(['id', 'name', 'description', 'cost', 'category_id', 'image_path', 'short_code', 'active']);

                $data['media_id'] = $primaryMedia?->id ?? $item->media_id;
                $data['primary_media'] = $primaryMedia ? $mediaPresenter->present($primaryMedia) : null;

                return [$item->id => $data];
            });

        return view('admin.items.index', compact('items', 'categories', 'options', 'allOptions', 'itemsData', 'itemEditData'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0|decimal:0,2',
            'category_id' => 'required|exists:categories,id',
            'media_id' => 'nullable|integer|exists:media,id',
            'image_path' => 'nullable|string|max:500',
            'short_code' => 'nullable|string|max:50',
            'active' => 'nullable',
            'options' => 'nullable|array',
            'options.*' => 'integer|exists:options,id',
        ]);

        $validated['active'] = $request->has('active');

        $item = Item::create($validated);
        $this->syncPrimaryImage($item, $request);

        // Attach options to item if provided
        if (! empty($request->input('options'))) {
            foreach (array_values($request->input('options')) as $index => $optionId) {
                $item->itemOptions()->create([
                    'option_id' => $optionId,
                    'sort_order' => $index + 1,
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
                'media_id' => 'nullable|integer|exists:media,id',
                'image_path' => 'nullable|string|max:500',
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
            $this->syncPrimaryImage($item, $request);

            $this->syncItemOptions($item, (array) $request->input('options', []));
            // dd($item, "item should be saved");

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

        ItemOption::where('item_id', $item->id)->delete();
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
            if (! $optionValue) {
                continue;
            }

            $itemOption = $item->itemOptions()
                ->where('option_id', $optionValue->option_id)
                ->first();

            if (! $itemOption) {
                continue;
            }

            $itemOption->itemOptionValues()->updateOrCreate(
                ['option_value_id' => $optionValueId],
                ['price' => $price]
            );
        }

        // Update dependent item option value prices if provided
        if (! empty($validated['dependency_values'])) {
            foreach ($validated['dependency_values'] as $itemOptionValueId => $price) {
                ItemOptionValue::where('id', $itemOptionValueId)->update(['price' => $price]);
            }
        }

        // Update dependencies if provided
        if (! empty($validated['dependencies'])) {
            foreach ($validated['dependencies'] as $optionValueId => $childOptionIds) {
                // Resolve the parent ItemOptionValue for this item and option value
                $optionValue = OptionValue::find($optionValueId);
                if (! $optionValue) {
                    continue;
                }

                $parentItemOption = $item->itemOptions()
                    ->where('option_id', $optionValue->option_id)
                    ->first();

                if (! $parentItemOption) {
                    continue;
                }

                $parentItemOptionValue = $parentItemOption->itemOptionValues()->firstOrCreate(
                    ['option_value_id' => $optionValueId],
                    []
                );

                $existingChildOptionIds = OptionDependency::where('parent_option_value_id', $parentItemOptionValue->id)
                    ->with('childOption')
                    ->get()
                    ->map(fn ($dep) => $dep->childOption?->option_id)
                    ->filter()
                    ->values()
                    ->all();

                $normalizedChildOptionIds = collect($childOptionIds)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $toAdd = array_values(array_diff($normalizedChildOptionIds, $existingChildOptionIds));
                $toRemove = array_values(array_diff($existingChildOptionIds, $normalizedChildOptionIds));

                if (! empty($toRemove)) {
                    OptionDependency::where('parent_option_value_id', $parentItemOptionValue->id)
                        ->whereHas('childOption', fn ($q) => $q->whereIn('option_id', $toRemove))
                        ->delete();
                }

                // For each new child option, create ItemOption and ItemOptionValue records
                foreach ($toAdd as $childOptionId) {
                    $parentOverrides = $validated['dependency_value_overrides'][$optionValueId] ?? [];
                    $childOverrides = $parentOverrides[$childOptionId] ?? [];

                    // Find or create ItemOption for this child option
                    $itemOption = $item->itemOptions()->create([
                        'option_id' => $childOptionId,
                        'sort_order' => (ItemOption::where('item_id', $item->id)->max('sort_order') ?? 0) + 1,
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

                    // Create the dependency, then point the parent value at
                    // it. The ordering API reads dependencies through that
                    // back-reference (ItemOptionValue::optionDependency), so a
                    // dependency without it exists in the database but never
                    // reaches the customer's customiser.
                    $dependency = OptionDependency::create([
                        'parent_option_value_id' => $parentItemOptionValue->id,
                        'child_option_id' => $itemOption->id,
                    ]);

                    $parentItemOptionValue->update(['option_dependency_id' => $dependency->id]);
                }

                // Dependencies created before the back-reference was written
                // are already present, so they never reach the branch above.
                // Re-pointing the parent value on every save repairs those
                // rows in place instead of making someone remove the
                // dependency and add it again just to relink it.
                $this->relinkDependency($parentItemOptionValue);
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
            'min' => 'nullable|integer|min:0',
            'max' => 'nullable|integer|min:0',
        ]);

        if (
            array_key_exists('min', $validated)
            && array_key_exists('max', $validated)
            && $validated['min'] !== null
            && $validated['max'] !== null
            && $validated['max'] < $validated['min']
        ) {
            return response()->json([
                'message' => 'The max value must be greater than or equal to the min value.',
                'errors' => [
                    'max' => ['The max value must be greater than or equal to the min value.'],
                ],
            ], 422);
        }

        $itemOption->update([
            'enable_qty' => (bool) ($validated['enable_qty'] ?? $itemOption->enable_qty),
            'range' => (bool) ($validated['range'] ?? $itemOption->range),
            'min' => array_key_exists('min', $validated) ? $validated['min'] : $itemOption->min,
            'max' => array_key_exists('max', $validated) ? $validated['max'] : $itemOption->max,
        ]);

        if (! $itemOption->enable_qty) {
            $itemOption->itemOptionValues()->update(['qty' => null]);
        }

        return response()->json([
            'success' => true,
            'enable_qty' => $itemOption->enable_qty,
            'range' => $itemOption->range,
            'min' => $itemOption->min,
            'max' => $itemOption->max,
        ]);
    }

    public function updateItemOptionOrder(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'item_options' => 'required|array|min:1',
            'item_options.*' => 'integer|exists:item_options,id',
        ]);

        $itemOptionIds = collect($validated['item_options'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $ownedItemOptionIds = ItemOption::query()
            ->where('item_id', $item->id)
            ->whereIn('id', $itemOptionIds)
            ->pluck('id');

        if ($ownedItemOptionIds->count() !== $itemOptionIds->count()) {
            return response()->json([
                'message' => 'One or more item options do not belong to this item.',
                'errors' => [
                    'item_options' => ['One or more item options do not belong to this item.'],
                ],
            ], 422);
        }

        foreach ($itemOptionIds as $index => $itemOptionId) {
            ItemOption::query()
                ->where('id', $itemOptionId)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'item_options' => ItemOption::query()
                ->whereIn('id', $itemOptionIds)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'sort_order']),
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

    /**
     * Bring an item's option groups in line with the ids the edit form posted.
     *
     * Deleting and recreating the groups would be simpler, but an ItemOption
     * owns its ItemOptionValues — the per-item prices, stock flags and option
     * dependencies — and those cascade away with it. Re-adding the group gives
     * back an empty one, so saving an item for any reason (a name tweak, a
     * photo) silently emptied its customiser. Groups the editor kept are
     * therefore left alone and only their order is refreshed.
     *
     * Dependent groups are not represented in this list at all: they belong to
     * the option-dependency editor, which creates them with type 'dependent'
     * and removes them when their parent value is unlinked.
     *
     * @param  array<int, mixed>  $optionIds
     */
    /**
     * Point an option value at the dependency that names it as parent, or
     * clear the link when the last one has been removed.
     *
     * Ordering clients resolve dependencies through this column, so a value
     * that is out of step with the option_dependencies table has a dependency
     * the admin can see and the customiser cannot.
     */
    private function relinkDependency(ItemOptionValue $parentItemOptionValue): void
    {
        $dependencyId = OptionDependency::query()
            ->where('parent_option_value_id', $parentItemOptionValue->id)
            ->value('id');

        if ($parentItemOptionValue->option_dependency_id !== $dependencyId) {
            $parentItemOptionValue->update(['option_dependency_id' => $dependencyId]);
        }
    }

    private function syncItemOptions(Item $item, array $optionIds): void
    {
        $optionIds = collect($optionIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $existing = $item->itemOptions()
            ->whereNull('type')
            ->get()
            ->keyBy('option_id');

        $existing
            ->reject(fn (ItemOption $group): bool => $optionIds->contains($group->option_id))
            ->each(fn (ItemOption $group) => $group->delete());

        $optionIds->each(function (int $optionId, int $index) use ($item, $existing): void {
            $group = $existing->get($optionId);

            if ($group !== null) {
                // Keep the group — and everything hanging off it — and just
                // record where the editor moved it to.
                $group->update(['sort_order' => $index + 1]);

                return;
            }

            $item->itemOptions()->create([
                'option_id' => $optionId,
                'sort_order' => $index + 1,
                'required' => false,
            ]);
        });
    }

    private function syncPrimaryImage(Item $item, Request $request): void
    {
        if ($request->filled('media_id')) {
            $item->syncMedia((int) $request->input('media_id'), 'primary_image');

            return;
        }

        $item->detachMediaTags('primary_image');
    }
}
