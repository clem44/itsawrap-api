<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleItem;
use App\Models\Item;
use App\Models\ItemOptionValue;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Plank\Mediable\Media;

class BundleController extends Controller
{
    public function index(MediaLibraryPresenter $mediaPresenter): View
    {
        $bundles = Bundle::query()
            ->withCount('bundleItems')
            ->withMedia(Bundle::IMAGE_TAG)
            ->with([
                'bundleItems.item.category',
                'bundleItems.optionValues.itemOption.option',
                'bundleItems.optionValues.optionValue',
                'bundleItems.optionValues.parentOptionValue',
            ])
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $items = Item::query()
            ->with([
                'category',
                'itemOptions.option',
                'itemOptions.itemOptionValues.optionValue',
            ])
            ->orderBy('name')
            ->get();

        $oldMedia = null;

        if (old('form_action') && old('media_id')) {
            $media = Media::query()->find(old('media_id'));
            $oldMedia = $media ? $mediaPresenter->present($media) : null;
        }

        return view('admin.bundles.index', compact('bundles', 'items', 'mediaPresenter', 'oldMedia'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBundle($request);
        $validated['bundle']['is_active'] = $request->boolean('is_active');
        $validated['bundle']['created_by_user_id'] = $request->user()?->id;

        $bundle = DB::transaction(function () use ($validated): Bundle {
            $bundle = Bundle::query()->create($validated['bundle']);
            $this->syncBundleItems($bundle, $validated['items']);

            return $bundle;
        });

        $this->syncPrimaryImage($bundle, $request);

        return redirect()->route('admin.bundles.index')
            ->with('success', 'Bundle created successfully.');
    }

    public function update(Request $request, Bundle $bundle): RedirectResponse
    {
        $validated = $this->validateBundle($request);
        $validated['bundle']['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($bundle, $validated): void {
            $bundle->update($validated['bundle']);
            $bundle->bundleItems()->delete();
            $this->syncBundleItems($bundle, $validated['items']);
        });

        $this->syncPrimaryImage($bundle, $request);

        return redirect()->route('admin.bundles.index')
            ->with('success', 'Bundle updated successfully.');
    }

    public function destroy(Bundle $bundle): RedirectResponse
    {
        if ($bundle->offers()->exists()) {
            return redirect()->route('admin.bundles.index')
                ->with('error', 'Cannot delete a bundle that is used by an offer.');
        }

        $bundle->delete();

        return redirect()->route('admin.bundles.index')
            ->with('success', 'Bundle deleted successfully.');
    }

    /**
     * @return array{bundle: array<string, mixed>, items: array<int, array<string, mixed>>}
     */
    private function validateBundle(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), 'after:starts_at')],
            'is_active' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'items.*.price_override' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'items.*.label_override' => ['nullable', 'string', 'max:255'],
            'items.*.option_values' => ['nullable', 'array'],
            'items.*.option_values.*.item_option_id' => ['required', 'integer', 'exists:item_options,id'],
            'items.*.option_values.*.option_value_id' => ['required', 'integer', 'exists:option_values,id'],
            'items.*.option_values.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.option_values.*.parent_option_value_id' => ['nullable', 'integer', 'exists:option_values,id'],
            'items.*.option_values.*.price_override' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            foreach ((array) $request->input('items', []) as $itemIndex => $itemPayload) {
                $itemId = $itemPayload['item_id'] ?? null;

                foreach ((array) ($itemPayload['option_values'] ?? []) as $optionIndex => $optionPayload) {
                    $itemOptionId = $optionPayload['item_option_id'] ?? null;
                    $optionValueId = $optionPayload['option_value_id'] ?? null;

                    if (! $itemId || ! $itemOptionId || ! $optionValueId) {
                        continue;
                    }

                    $exists = ItemOptionValue::query()
                        ->where('item_option_id', $itemOptionId)
                        ->where('option_value_id', $optionValueId)
                        ->whereHas('itemOption', fn ($query) => $query->where('item_id', $itemId))
                        ->exists();

                    if (! $exists) {
                        $validator->errors()->add(
                            "items.{$itemIndex}.option_values.{$optionIndex}.option_value_id",
                            'The selected option value does not belong to the selected bundle item.'
                        );
                    }
                }
            }
        });

        $validated = $validator->validate();

        $bundle = collect($validated)
            ->except(['items', 'media_id'])
            ->all();
        $bundle['sort_order'] = $bundle['sort_order'] ?? 0;

        return [
            'bundle' => $bundle,
            'items' => array_values($validated['items']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncBundleItems(Bundle $bundle, array $items): void
    {
        foreach ($items as $index => $itemPayload) {
            $optionValues = array_values($itemPayload['option_values'] ?? []);
            unset($itemPayload['option_values']);

            $bundleItem = $bundle->bundleItems()->create([
                'item_id' => $itemPayload['item_id'],
                'quantity' => $itemPayload['quantity'],
                'sort_order' => $itemPayload['sort_order'] ?? $index,
                'price_override' => $itemPayload['price_override'] ?? null,
                'label_override' => $itemPayload['label_override'] ?? null,
            ]);

            $this->syncBundleItemOptionValues($bundleItem, $optionValues);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $optionValues
     */
    private function syncBundleItemOptionValues(BundleItem $bundleItem, array $optionValues): void
    {
        foreach ($optionValues as $optionValuePayload) {
            $bundleItem->optionValues()->create([
                'item_option_id' => $optionValuePayload['item_option_id'],
                'option_value_id' => $optionValuePayload['option_value_id'],
                'quantity' => $optionValuePayload['quantity'] ?? 1,
                'parent_option_value_id' => $optionValuePayload['parent_option_value_id'] ?? null,
                'price_override' => $optionValuePayload['price_override'] ?? null,
            ]);
        }
    }

    private function syncPrimaryImage(Bundle $bundle, Request $request): void
    {
        if ($request->filled('media_id')) {
            $bundle->syncMedia((int) $request->input('media_id'), Bundle::IMAGE_TAG);

            return;
        }

        $bundle->detachMediaTags(Bundle::IMAGE_TAG);
    }
}
