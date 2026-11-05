<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Item;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveMealDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'allergens' => ['nullable', 'string', 'max:2000'],
            'additional_info' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'service_charge_percent' => ['required_without:service_charge_rate', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'service_charge_rate' => ['required_without:service_charge_percent', 'numeric', 'min:0', 'max:1', 'decimal:0,4'],
            'marketing_max_saving' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'primary_category_id' => ['required', 'integer', 'exists:categories,id'],
            'primary_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'selection_groups' => ['required', 'array', 'min:1'],
            'selection_groups.*.kind' => ['nullable', Rule::in(['option', 'item'])],
            'selection_groups.*.option_id' => ['nullable', 'integer', 'exists:options,id'],
            'selection_groups.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'selection_groups.*.allowed_ids' => ['required', 'array', 'min:1'],
            'selection_groups.*.allowed_ids.*' => ['required', 'integer'],
            'selection_groups.*.min' => ['required', 'integer', 'min:0', 'max:20'],
            'selection_groups.*.max' => ['required', 'integer', 'min:1', 'max:20'],
            'selection_groups.*.allow_duplicates' => ['nullable', 'boolean'],
            'selection_groups.*.paid_extra' => ['nullable', 'boolean'],
            'selection_groups.*.dependencies' => ['nullable', 'array'],
            'selection_groups.*.dependencies.*.parent_choice_id' => ['required', 'integer', 'exists:option_values,id'],
            'selection_groups.*.dependencies.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'selection_groups.*.dependencies.*.allowed_ids' => ['required', 'array', 'min:1'],
            'selection_groups.*.dependencies.*.allowed_ids.*' => ['required', 'integer', 'exists:option_values,id'],
            'selection_groups.*.dependencies.*.prices' => ['nullable', 'array'],
            'selection_groups.*.dependencies.*.prices.*' => ['nullable', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2'],
            'fixed_option_value_ids' => ['nullable', 'array'],
            'fixed_option_value_ids.*' => ['integer', 'exists:option_values,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    public function mealDealData(): array
    {
        $data = $this->validated();

        if (array_key_exists('service_charge_percent', $data)) {
            $data['service_charge_rate'] = round((float) $data['service_charge_percent'] / 100, 4);
        }
        unset($data['service_charge_percent']);

        if (! empty($data['primary_item_id']) && ! Item::query()->whereKey($data['primary_item_id'])->where('category_id', $data['primary_category_id'])->exists()) {
            throw ValidationException::withMessages(['primary_item_id' => 'Choose an item from the selected category.']);
        }

        foreach ($data['selection_groups'] as $index => &$group) {
            $group = $this->normalizeSelectionGroup($group, $index);
        }
        unset($group);

        $keys = collect($data['selection_groups'])->pluck('key');
        if ($keys->unique()->count() !== $keys->count()) {
            throw ValidationException::withMessages(['selection_groups' => 'Choose each option or menu category only once.']);
        }

        unset($data['media_id']);
        $data['primary_item_id'] = $data['primary_item_id'] ?? null;
        $data['primary_group_key'] = null;
        $data['fixed_option_value_ids'] = $data['fixed_option_value_ids'] ?? [];
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $this->boolean('is_active');

        return $data;
    }

    private function normalizeSelectionGroup(array $group, int $index): array
    {
        $group['kind'] = $group['kind'] ?? 'option';
        $group['min'] = (int) $group['min'];
        $group['max'] = (int) $group['max'];
        if ($group['min'] > $group['max']) {
            throw ValidationException::withMessages(["selection_groups.{$index}.min" => 'Minimum cannot exceed maximum.']);
        }

        $group['allowed_ids'] = array_values(array_unique(array_map('intval', $group['allowed_ids'])));
        $group['allow_duplicates'] = (bool) ($group['allow_duplicates'] ?? false);
        $group['paid_extra'] = (bool) ($group['paid_extra'] ?? false);

        if ($group['kind'] === 'item') {
            $category = Category::query()->find($group['category_id'] ?? null);
            if (! $category) {
                throw ValidationException::withMessages(["selection_groups.{$index}.category_id" => 'Choose a menu item category.']);
            }

            $group['key'] = 'item_category_'.$category->id;
            $group['label'] = $category->name;
            $group['option_id'] = null;
            $group['dependencies'] = [];
            $validChoiceCount = Item::query()->where('category_id', $category->id)->whereIn('id', $group['allowed_ids'])->count();
        } else {
            $option = Option::query()->find($group['option_id'] ?? null);
            if (! $option) {
                throw ValidationException::withMessages(["selection_groups.{$index}.option_id" => 'Choose an item option.']);
            }

            $group['key'] = 'option_'.$option->id;
            $group['label'] = $option->title ?: $option->name;
            $group['category_id'] = null;
            $group['dependencies'] = $this->normalizeDependencies($group, $index);
            $validChoiceCount = OptionValue::query()->where('option_id', $option->id)->whereIn('id', $group['allowed_ids'])->count();
        }

        if ($validChoiceCount !== count($group['allowed_ids'])) {
            throw ValidationException::withMessages(["selection_groups.{$index}.allowed_ids" => 'Choices must belong to the selected source.']);
        }

        return $group;
    }

    private function normalizeDependencies(array $group, int $groupIndex): array
    {
        return collect($group['dependencies'] ?? [])->map(function (array $dependency, int $dependencyIndex) use ($group, $groupIndex): array {
            $parentChoiceId = (int) $dependency['parent_choice_id'];
            $childOptionId = (int) $dependency['option_id'];
            $allowedIds = array_values(array_unique(array_map('intval', $dependency['allowed_ids'] ?? [])));

            if (! in_array($parentChoiceId, $group['allowed_ids'], true)) {
                throw ValidationException::withMessages([
                    "selection_groups.{$groupIndex}.dependencies.{$dependencyIndex}.parent_choice_id" => 'The parent choice must be enabled in this choice group.',
                ]);
            }
            if ($childOptionId === (int) $group['option_id']) {
                throw ValidationException::withMessages([
                    "selection_groups.{$groupIndex}.dependencies.{$dependencyIndex}.option_id" => 'A choice cannot depend on its own option.',
                ]);
            }
            if (OptionValue::query()->whereKey($parentChoiceId)->where('option_id', $group['option_id'])->doesntExist()) {
                throw ValidationException::withMessages([
                    "selection_groups.{$groupIndex}.dependencies.{$dependencyIndex}.parent_choice_id" => 'The parent choice does not belong to this item option.',
                ]);
            }
            if (OptionValue::query()->where('option_id', $childOptionId)->whereIn('id', $allowedIds)->count() !== count($allowedIds)) {
                throw ValidationException::withMessages([
                    "selection_groups.{$groupIndex}.dependencies.{$dependencyIndex}.allowed_ids" => 'Dependent choices must belong to the selected dependent option.',
                ]);
            }

            $prices = collect($dependency['prices'] ?? [])
                ->only(array_map('strval', $allowedIds))
                ->mapWithKeys(fn ($price, $id) => [(string) $id => round((float) ($price ?? 0), 2)])
                ->all();

            return [
                'key' => "dependency_{$group['key']}_{$parentChoiceId}_{$childOptionId}",
                'parent_choice_id' => $parentChoiceId,
                'option_id' => $childOptionId,
                'allowed_ids' => $allowedIds,
                'prices' => $prices,
            ];
        })->unique('parent_choice_id')->values()->all();
    }
}
