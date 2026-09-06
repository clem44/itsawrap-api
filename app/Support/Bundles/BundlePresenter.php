<?php

namespace App\Support\Bundles;

use App\Models\Bundle;
use App\Models\BundleItem;
use App\Models\BundleItemOptionValue;

class BundlePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Bundle $bundle): array
    {
        return [
            'id' => $bundle->id,
            'name' => $bundle->name,
            'description' => $bundle->description,
            'starts_at' => $bundle->starts_at?->toISOString(),
            'ends_at' => $bundle->ends_at?->toISOString(),
            'is_active' => $bundle->is_active,
            'sort_order' => $bundle->sort_order,
            'featured_image_url' => $bundle->featuredImageUrl(),
            'items' => $bundle->bundleItems
                ->map(fn (BundleItem $bundleItem) => $this->presentItem($bundleItem))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentItem(BundleItem $bundleItem): array
    {
        $item = $bundleItem->item;

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
            'option_values' => $bundleItem->optionValues
                ->map(fn (BundleItemOptionValue $optionValue) => $this->presentOptionValue($optionValue))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentOptionValue(BundleItemOptionValue $optionValue): array
    {
        return [
            'id' => $optionValue->id,
            'item_option_id' => $optionValue->item_option_id,
            'option_id' => $optionValue->itemOption?->option_id,
            'option_name' => $optionValue->itemOption?->option?->name,
            'option_value_id' => $optionValue->option_value_id,
            'name' => $optionValue->optionValue?->name,
            'quantity' => $optionValue->quantity,
            'parent_option_value_id' => $optionValue->parent_option_value_id,
            'price_override' => $optionValue->price_override,
        ];
    }
}
