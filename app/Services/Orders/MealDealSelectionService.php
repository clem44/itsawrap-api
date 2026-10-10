<?php

namespace App\Services\Orders;

use App\Models\Item;
use App\Models\MealDeal;
use App\Models\Option;
use App\Models\OptionValue;
use App\Services\MealDeals\MealDealAvailability;
use Illuminate\Validation\ValidationException;

class MealDealSelectionService
{
    public function __construct(private readonly MealDealAvailability $availability) {}

    /**
     * @param  array<string, mixed>  $selection
     * @return array<string, mixed>
     */
    public function prepare(array $selection, string $path = 'meal_deals'): array
    {
        $deal = MealDeal::query()->where('is_active', true)->find($selection['meal_deal_id'] ?? null);
        if ($deal === null) {
            throw ValidationException::withMessages(["{$path}.meal_deal_id" => 'This Meal Deal is unavailable.']);
        }

        $quantity = (int) ($selection['quantity'] ?? 0);
        if ($quantity < 1 || $quantity > 20) {
            throw ValidationException::withMessages(["{$path}.quantity" => 'Quantity must be between 1 and 20.']);
        }

        $selectedItemId = $selection['item_id'] ?? $deal->primary_item_id;
        $inspection = $this->availability->inspect($deal);
        if (! in_array((int) $selectedItemId, array_column($inspection['eligible_items'], 'id'), true)) {
            throw ValidationException::withMessages(["{$path}.item_id" => 'Choose an available item from this Meal Deal category.']);
        }
        $primary = Item::query()->findOrFail($selectedItemId);

        $submitted = $selection['selections'] ?? [];
        if (! is_array($submitted)) {
            throw ValidationException::withMessages(["{$path}.selections" => 'Selections must be an object.']);
        }
        $groups = $deal->selection_groups ?? [];
        $allowedKeys = array_column($groups, 'key');
        foreach (array_keys($submitted) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'This choice is not part of the Meal Deal.']);
            }
        }

        $chosen = [];
        $primaryOptions = [];
        $extrasPrice = 0.0;
        $snapshot = ['primary_item' => ['id' => $primary->id, 'name' => $primary->name]];
        foreach ($groups as $group) {
            $key = $group['key'];
            $ids = $submitted[$key] ?? [];
            if (! is_array($ids) || count($ids) < (int) $group['min'] || count($ids) > (int) $group['max']) {
                throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'Choose the required number of options.']);
            }
            foreach ($ids as $id) {
                if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
                    throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'Select a valid option.']);
                }
            }
            $ids = array_map('intval', $ids);
            if (! ($group['allow_duplicates'] ?? false) && count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'Duplicate choices are not allowed.']);
            }
            $snapshot[$key] = [];
            foreach ($ids as $id) {
                if (! in_array($id, $group['allowed_ids'], true)) {
                    throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'A selected option is not allowed.']);
                }
                if (($group['kind'] ?? 'option') === 'item') {
                    $choice = Item::query()->whereKey($id)->where('category_id', $group['category_id'] ?? null)
                        ->where('active', true)->first();
                    if ($choice === null) {
                        throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'A selected menu item is unavailable.']);
                    }
                    $price = ($group['paid_extra'] ?? false) ? (float) $choice->cost : 0.0;
                    $snapshot[$key][] = ['kind' => 'item', 'id' => $id, 'name' => $choice->name, 'price' => $price];
                } else {
                    $choice = OptionValue::query()->whereKey($id)->where('option_id', $group['option_id'] ?? null)->first();
                    if ($choice === null) {
                        throw ValidationException::withMessages(["{$path}.selections.{$key}" => 'A selected option value is unavailable.']);
                    }
                    $price = ($group['paid_extra'] ?? false) ? (float) $choice->price : 0.0;
                    $snapshot[$key][] = ['kind' => 'option', 'id' => $id, 'name' => $choice->name, 'price' => $price];
                    $primaryOptions[] = ['option_value_id' => $id, 'price' => $price, 'qty' => 1];
                }
                $extrasPrice += $price;
            }
            if (($group['kind'] ?? 'option') === 'option') {
                $chosen[$group['option_id']] = $ids;
            }
        }

        $proteinOptions = Option::query()->whereIn('name', ['Protein', 'Protein 2'])->pluck('id', 'name');
        $protein = collect($groups)->first(fn ($group) => (int) ($group['option_id'] ?? 0) === (int) ($proteinOptions['Protein'] ?? 0));
        $protein2 = collect($groups)->first(fn ($group) => (int) ($group['option_id'] ?? 0) === (int) ($proteinOptions['Protein 2'] ?? 0));
        if ($protein && $protein2) {
            $first = OptionValue::query()->find($chosen[$protein['option_id']][0] ?? null)?->name;
            $second = OptionValue::query()->find($chosen[$protein2['option_id']][0] ?? null)?->name;
            if ($first !== null && mb_strtolower($first) === mb_strtolower((string) $second)) {
                throw ValidationException::withMessages(["{$path}.selections.{$protein2['key']}" => 'Choose a different second protein.']);
            }
        }

        foreach ($deal->fixed_option_value_ids ?? [] as $id) {
            $value = OptionValue::query()->find($id);
            if ($value === null) {
                throw ValidationException::withMessages(["{$path}.meal_deal_id" => 'An included option is unavailable.']);
            }
            $snapshot['included'][] = ['id' => $id, 'name' => $value->name];
            $primaryOptions[] = ['option_value_id' => $id, 'price' => 0, 'qty' => 1];
        }

        $base = (float) $deal->base_price;
        $charge = round($base * (float) $deal->service_charge_rate, 2);

        return [
            'meal_deal_id' => $deal->id,
            'name' => $deal->name,
            'quantity' => $quantity,
            'item_id' => $primary->id,
            'base_price' => $base,
            'extras_price' => round($extrasPrice, 2),
            'service_charge' => $charge,
            'selections' => $snapshot,
            'participant_client_id' => $selection['participant_client_id'] ?? null,
            'items' => [[
                'item_id' => $primary->id, 'price' => $base, 'quantity' => $quantity, 'options' => $primaryOptions,
            ]],
        ];
    }
}
