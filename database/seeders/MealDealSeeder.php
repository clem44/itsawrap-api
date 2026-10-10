<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\MealDeal;
use App\Models\Option;
use Illuminate\Database\Seeder;

class MealDealSeeder extends Seeder
{
    public function run(): void
    {
        $drinks = Category::query()->firstOrCreate(['name' => 'Drinks'], ['sort_order' => 9, 'color' => '#4CAF50', 'icon' => 'drink']);
        foreach ([
            'V8 Original — 100% Vegetable Juice',
            'Sanpellegrino Aranciata — Sparkling Orange',
            'Sanpellegrino Pompelmo — Sparkling Grapefruit',
            'Vinut Soursop — Juice Drink with Pulp',
            'Vinut Lychee — Juice Drink with Pulp',
        ] as $name) {
            Item::query()->firstOrCreate(['name' => $name], ['category_id' => $drinks->id, 'cost' => 3, 'active' => true]);
        }

        $wraps = Category::query()->firstOrCreate(['name' => 'Wraps']);
        $solo = Item::query()->firstOrCreate(['name' => 'Solo Wrap'], ['category_id' => $wraps->id, 'cost' => 0, 'active' => true, 'menu_visible' => false]);
        $solo->update(['menu_visible' => false]);
        $protein = Option::query()->firstOrCreate(['name' => 'Protein']);
        $protein2 = Option::query()->firstOrCreate(['name' => 'Protein 2']);
        $proteinIds = [];
        foreach (['Chicken', 'Steak', 'Tuna', 'Tofu'] as $name) {
            $proteinIds[] = $protein->optionValues()->firstOrCreate(['name' => $name], ['price' => 0])->id;
            $protein2->optionValues()->firstOrCreate(['name' => $name], ['price' => 0]);
        }

        $groups = [];
        foreach ([['protein', 'Choose a protein', $protein, 1, 1], ['sauces', 'Choose two Mystery Sauces', Option::query()->firstOrCreate(['name' => 'Sauce']), 2, 2], ['free_additions', 'Free Additions', Option::query()->firstOrCreate(['name' => 'Free Additions']), 0, 6], ['extras', 'Premium Extras and Cheeses', Option::query()->firstOrCreate(['name' => 'Extras']), 0, 10]] as [$key, $label, $option, $min, $max]) {
            $itemOption = $solo->itemOptions()->firstOrCreate(['option_id' => $option->id], ['required' => $min > 0, 'type' => 'multiple']);
            foreach ($option->optionValues as $value) {
                $itemOption->itemOptionValues()->firstOrCreate(['option_value_id' => $value->id], ['price' => $key === 'extras' ? $value->price : 0, 'in_stock' => true]);
            }
            $groups[] = [
                'key' => $key,
                'label' => $label,
                'kind' => 'option',
                'option_id' => $option->id,
                'allowed_ids' => $key === 'protein' ? $proteinIds : $option->optionValues->pluck('id')->all(),
                'min' => $min,
                'max' => $max,
                'allow_duplicates' => $key === 'sauces',
                'paid_extra' => $key === 'extras',
            ];
        }

        foreach ([
            ['side', 'Side', 'Choose a Regular Side', Item::query()->whereIn('name', ['Seasoned Fries', 'Sweet Potato Fries', 'Onion Rings'])->get()],
            ['drink', 'Drink', 'Choose an included drink', Item::query()->where('category_id', $drinks->id)->get()],
        ] as [$key, $name, $title, $items]) {
            $option = Option::query()->firstOrCreate(['name' => $name], ['title' => $title]);
            $itemOption = $solo->itemOptions()->firstOrCreate(['option_id' => $option->id], ['required' => true, 'type' => 'single', 'min' => 1, 'max' => 1]);
            $ids = [];
            foreach ($items as $item) {
                $value = $option->optionValues()->firstOrCreate(['name' => $item->name], ['price' => 0]);
                $itemOption->itemOptionValues()->firstOrCreate(['option_value_id' => $value->id], ['price' => 0, 'in_stock' => true]);
                $ids[] = $value->id;
            }
            $groups[] = ['key' => $key, 'label' => $title, 'kind' => 'option', 'option_id' => $option->id,
                'allowed_ids' => $ids, 'min' => 1, 'max' => 1, 'allow_duplicates' => false, 'paid_extra' => false];
        }

        MealDeal::query()->firstOrCreate(['name' => 'The Albena'], [
            'description' => 'Solo Wrap with one protein, side, drink and two Mystery Sauces.',
            'base_price' => 13,
            'service_charge_rate' => 0.15,
            'marketing_max_saving' => 4,
            'primary_category_id' => $wraps->id,
            'primary_item_id' => $solo->id,
            'selection_groups' => $groups,
            'fixed_option_value_ids' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
