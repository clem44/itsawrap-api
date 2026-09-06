<?php

namespace App\Services\Orders;

use App\Models\Bundle;
use App\Models\BundleItemOptionValue;
use Illuminate\Validation\ValidationException;

class BundleOrderExpander
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function expand(array $validated): array
    {
        $bundleSelections = array_values($validated['bundles'] ?? []);

        if ($bundleSelections === []) {
            return $validated;
        }

        $bundles = Bundle::query()
            ->availableForOrdering()
            ->whereIn('id', collect($bundleSelections)->pluck('bundle_id')->all())
            ->with([
                'bundleItems.item',
                'bundleItems.optionValues.itemOption.itemOptionValues',
                'bundleItems.optionValues.optionValue',
            ])
            ->get()
            ->keyBy('id');

        $items = array_values($validated['items'] ?? []);

        foreach ($bundleSelections as $selectionIndex => $selection) {
            $bundle = $bundles->get((int) $selection['bundle_id']);

            if ($bundle === null) {
                throw ValidationException::withMessages([
                    "bundles.{$selectionIndex}.bundle_id" => 'The selected bundle is not available for ordering.',
                ]);
            }

            foreach ($bundle->bundleItems as $bundleItem) {
                $item = [
                    'item_id' => $bundleItem->item_id,
                    'price' => $bundleItem->price_override ?? $bundleItem->item?->cost ?? 0,
                    'quantity' => $bundleItem->quantity * (int) $selection['quantity'],
                    'comment' => $selection['comment'] ?? null,
                    'options' => $bundleItem->optionValues
                        ->map(fn (BundleItemOptionValue $optionValue) => [
                            'option_value_id' => $optionValue->option_value_id,
                            'parent_option_value_id' => $optionValue->parent_option_value_id,
                            'price' => $this->optionPrice($optionValue),
                            'qty' => $optionValue->quantity,
                        ])
                        ->values()
                        ->all(),
                ];

                if (isset($selection['participant_client_id'])) {
                    $item['participant_client_id'] = $selection['participant_client_id'];
                }

                $items[] = $item;
            }
        }

        $validated['items'] = $items;
        unset($validated['bundles']);

        return $validated;
    }

    private function optionPrice(BundleItemOptionValue $optionValue): mixed
    {
        if ($optionValue->price_override !== null) {
            return $optionValue->price_override;
        }

        return $optionValue->itemOption?->itemOptionValues
            ->firstWhere('option_value_id', $optionValue->option_value_id)
            ?->price ?? 0;
    }
}
