<?php

namespace App\Services\MealDeals;

use App\Models\Item;
use App\Models\MealDeal;
use App\Models\OptionValue;
use Illuminate\Support\Collection;

class MealDealAvailability
{
    /**
     * @return array{selection_groups: array<int, array<string, mixed>>, eligible_items: array<int, array<string, mixed>>, issues: array<int, string>}
     */
    public function inspect(MealDeal $deal): array
    {
        $groups = $deal->selection_groups ?? [];
        if (! $deal->primary_category_id) {
            return ['selection_groups' => $groups, 'eligible_items' => [], 'issues' => ['Choose a food category.']];
        }

        $items = Item::query()
            ->where('category_id', $deal->primary_category_id)
            ->where('active', true)
            ->when($deal->primary_item_id, fn ($query) => $query->whereKey($deal->primary_item_id),
                fn ($query) => $query->where('menu_visible', true))
            ->with('category')->orderBy('name')->get();

        $issues = [];
        if ($items->isEmpty()) {
            $issues[] = $deal->primary_item_id
                ? 'The selected item is unavailable.'
                : 'No available items exist in the selected food category.';
        }

        $choiceSets = [];
        $presentedGroups = collect($groups)->map(function (array $group) use (&$issues, &$choiceSets): array {
            $choices = $this->availableChoices($group);
            $choiceSets[$group['key']] = $choices;
            $required = ($group['allow_duplicates'] ?? false) ? min(1, (int) $group['min']) : (int) $group['min'];
            if ($choices->count() < $required) {
                $issues[] = sprintf('%s has %d available choice(s); %d required.',
                    $group['label'], $choices->count(), $required);
            }

            return [...$group, 'choices' => $choices->map(fn ($choice) => [
                'id' => $choice->id, 'name' => $choice->name,
            ])->values()->all()];
        })->all();

        $fixedIds = $deal->fixed_option_value_ids ?? [];
        if (OptionValue::query()->whereIn('id', $fixedIds)->count() !== count($fixedIds)) {
            $issues[] = 'An included fixed option no longer exists.';
        }

        return [
            'selection_groups' => $presentedGroups,
            'eligible_items' => $items->map(fn (Item $item) => $this->presentItem($item, $deal, $presentedGroups, $choiceSets))->values()->all(),
            'issues' => $issues,
        ];
    }

    private function availableChoices(array $group): Collection
    {
        if (($group['kind'] ?? 'option') === 'item') {
            return Item::query()->where('category_id', $group['category_id'] ?? null)
                ->whereIn('id', $group['allowed_ids'] ?? [])->where('active', true)
                ->withMedia(Item::IMAGE_TAG)->orderBy('name')->get();
        }

        return OptionValue::query()->where('option_id', $group['option_id'] ?? null)
            ->whereIn('id', $group['allowed_ids'] ?? [])
            ->withMedia(OptionValue::IMAGE_TAG)->orderBy('name')->get();
    }

    private function presentItem(Item $item, MealDeal $deal, array $groups, array $choiceSets): array
    {
        $options = collect($groups)->map(function (array $group) use ($choiceSets): array {
            $kind = $group['kind'] ?? 'option';
            $key = $group['key'];
            $choices = $choiceSets[$key];

            return [
                'id' => 'meal-deal-group-'.$key,
                'meal_deal_key' => $key,
                'meal_deal_kind' => $kind,
                'option_id' => $group['option_id'] ?? null,
                'option' => ['name' => $group['label'], 'title' => $group['label']],
                'required' => (int) $group['min'] > 0,
                'min' => (int) $group['min'],
                'max' => (int) $group['max'],
                'allow_duplicates' => (bool) ($group['allow_duplicates'] ?? false),
                'type' => (int) $group['max'] > 1 ? 'multiple' : 'single',
                'item_option_values' => $choices->map(function ($choice) use ($key, $group, $kind) {
                    $choice->includePrimaryImageMedia();

                    return [
                        'id' => 'meal-deal-choice-'.$key.'-'.$choice->id,
                        'choice_id' => $choice->id,
                        'option_value' => [
                            'id' => $choice->id,
                            'name' => $choice->name,
                            'primary_image_url' => $choice->primary_image_url,
                            'primary_media' => $choice->primary_media,
                        ],
                        'price' => ($group['paid_extra'] ?? false) ? (float) ($kind === 'item' ? $choice->cost : $choice->price) : 0,
                    ];
                })->values()->all(),
            ];
        })->all();

        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'cost' => (float) $deal->base_price,
            'category' => ['id' => $item->category_id, 'name' => $item->category?->name],
            'item_options' => $options,
        ];
    }
}
