<?php

namespace App\Support\Bundles;

use App\Models\Bundle;
use App\Models\BundleItem;
use App\Models\BundleItemOptionValue;
use App\Models\ItemOptionValue;
use App\Models\Offer;

class BundlePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Bundle $bundle): array
    {
        $items = $bundle->bundleItems
            ->map(fn (BundleItem $bundleItem) => $this->presentItem($bundleItem))
            ->values()
            ->all();

        $regularPrice = round(array_sum(array_column($items, 'line_total')), 2);
        $bundlePrice = $this->bundlePrice($bundle);

        return [
            'id' => $bundle->id,
            'name' => $bundle->name,
            'description' => $bundle->description,
            'starts_at' => $bundle->starts_at?->toISOString(),
            'ends_at' => $bundle->ends_at?->toISOString(),
            'is_active' => $bundle->is_active,
            'sort_order' => $bundle->sort_order,
            'featured_image_url' => $bundle->featuredImageUrl(),
            // What the bundle's contents cost if ordered individually.
            'regular_price' => $regularPrice,
            // Set only when a fixed-price offer is attached to this bundle;
            // without one the bundle is a saved build, not a discount.
            'bundle_price' => $bundlePrice,
            'savings' => $bundlePrice !== null ? round(max(0, $regularPrice - $bundlePrice), 2) : null,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentItem(BundleItem $bundleItem): array
    {
        $item = $bundleItem->item;
        $quantity = max(1, (int) $bundleItem->quantity);
        $unitPrice = (float) ($bundleItem->price_override ?? $item?->cost ?? 0);

        $optionValues = $bundleItem->optionValues
            ->map(fn (BundleItemOptionValue $optionValue) => $this->presentOptionValue($optionValue))
            ->values()
            ->all();

        // Items built entirely from options carry a 0.00 base price, so the
        // options are the line's real cost.
        $optionsTotal = array_sum(array_map(
            fn (array $option): float => (float) $option['price'] * max(1, (int) $option['quantity']),
            $optionValues,
        ));

        return [
            'id' => $bundleItem->id,
            'item_id' => $bundleItem->item_id,
            'name' => $bundleItem->label_override ?: $item?->name,
            'item_name' => $item?->name,
            'category' => $item?->category ? [
                'id' => $item->category->id,
                'name' => $item->category->name,
            ] : null,
            'quantity' => $bundleItem->quantity,
            'sort_order' => $bundleItem->sort_order,
            'unit_price' => $bundleItem->price_override ?? $item?->cost,
            'price_override' => $bundleItem->price_override,
            'label_override' => $bundleItem->label_override,
            'line_total' => round(($unitPrice + $optionsTotal) * $quantity, 2),
            'option_values' => $optionValues,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentOptionValue(BundleItemOptionValue $optionValue): array
    {
        $itemOptionValue = $this->itemOptionValue($optionValue);

        return [
            'id' => $optionValue->id,
            'item_option_id' => $optionValue->item_option_id,
            // The row tying this value to this item is where the price lives,
            // and is what an ordering client needs to put the line in a cart.
            'item_option_value_id' => $itemOptionValue?->id,
            'option_id' => $optionValue->itemOption?->option_id,
            'option_name' => $optionValue->itemOption?->option?->name,
            'option_value_id' => $optionValue->option_value_id,
            'name' => $optionValue->optionValue?->name,
            'quantity' => $optionValue->quantity,
            'parent_option_value_id' => $optionValue->parent_option_value_id,
            'price_override' => $optionValue->price_override,
            'price' => (float) ($optionValue->price_override ?? $itemOptionValue?->price ?? 0),
        ];
    }

    private function itemOptionValue(BundleItemOptionValue $optionValue): ?ItemOptionValue
    {
        return $optionValue->itemOption?->itemOptionValues
            ->firstWhere('option_value_id', $optionValue->option_value_id);
    }

    /**
     * The fixed price of the offer attached to this bundle, when one is live.
     */
    private function bundlePrice(Bundle $bundle): ?float
    {
        if (! $bundle->relationLoaded('offers')) {
            return null;
        }

        $offer = $bundle->offers
            ->first(fn (Offer $offer): bool => $offer->discount_type === 'fixed_price'
                && $offer->discount_value !== null
                && $this->offerIsLive($offer));

        return $offer !== null ? round((float) $offer->discount_value, 2) : null;
    }

    private function offerIsLive(Offer $offer): bool
    {
        if (! $offer->is_active) {
            return false;
        }

        $now = now();

        return ($offer->starts_at === null || $offer->starts_at->lte($now))
            && ($offer->ends_at === null || $offer->ends_at->gte($now));
    }
}
