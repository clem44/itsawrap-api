<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\MealDeal;
use App\Models\Option;
use App\Services\MealDeals\MealDealAvailability;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Plank\Mediable\Media;

class MealDealController extends Controller
{
    public function index(MediaLibraryPresenter $mediaPresenter, MealDealAvailability $availability): View
    {
        $oldMedia = old('media_id') ? Media::query()->find(old('media_id')) : null;
        $deals = MealDeal::query()->with(['primaryItem', 'primaryCategory'])->withMedia(MealDeal::IMAGE_TAG)->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.meal-deals.index', [
            'deals' => $deals,
            'availabilityIssues' => $deals->mapWithKeys(fn (MealDeal $deal) => [$deal->id => $deal->is_active ? $availability->inspect($deal)['issues'] : []]),
            'items' => Item::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'options' => Option::query()->with('optionValues')->orderBy('name')->get(),
            'mediaPresenter' => $mediaPresenter,
            'oldMedia' => $oldMedia ? $mediaPresenter->present($oldMedia) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request): void {
            $deal = MealDeal::query()->create($data);
            $this->syncImage($deal, $request);
        });

        return to_route('admin.meal-deals.index')->with('success', 'Meal Deal created.');
    }

    public function update(Request $request, MealDeal $mealDeal): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request, $mealDeal): void {
            $mealDeal->update($data);
            $this->syncImage($mealDeal, $request);
        });

        return to_route('admin.meal-deals.index')->with('success', 'Meal Deal updated.');
    }

    public function destroy(MealDeal $mealDeal): RedirectResponse
    {
        $mealDeal->delete();

        return to_route('admin.meal-deals.index')->with('success', 'Meal Deal removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'service_charge_rate' => ['required', 'numeric', Rule::in([0.15])],
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
            'fixed_option_value_ids' => ['nullable', 'array'],
            'fixed_option_value_ids.*' => ['integer', 'exists:option_values,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        if (! empty($data['primary_item_id']) && ! Item::query()->whereKey($data['primary_item_id'])->where('category_id', $data['primary_category_id'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['primary_item_id' => 'Choose an item from the selected category.']);
        }
        foreach ($data['selection_groups'] as $index => &$group) {
            $group['kind'] = $group['kind'] ?? 'option';
            $group['min'] = (int) $group['min'];
            $group['max'] = (int) $group['max'];
            if ($group['min'] > $group['max']) {
                throw \Illuminate\Validation\ValidationException::withMessages(["selection_groups.{$index}.min" => 'Minimum cannot exceed maximum.']);
            }
            $group['allowed_ids'] = array_values(array_unique(array_map('intval', $group['allowed_ids'])));
            $group['allow_duplicates'] = (bool) ($group['allow_duplicates'] ?? false);
            $group['paid_extra'] = (bool) ($group['paid_extra'] ?? false);
            if ($group['kind'] === 'item') {
                $category = Category::query()->find($group['category_id'] ?? null);
                if (! $category) {
                    throw \Illuminate\Validation\ValidationException::withMessages(["selection_groups.{$index}.category_id" => 'Choose a menu item category.']);
                }
                $group['key'] = 'item_category_'.$category->id;
                $group['label'] = $category->name;
                $group['option_id'] = null;
                $exists = Item::query()->where('category_id', $category->id)->whereIn('id', $group['allowed_ids'])->count();
            } else {
                $option = Option::query()->find($group['option_id'] ?? null);
                if (! $option) {
                    throw \Illuminate\Validation\ValidationException::withMessages(["selection_groups.{$index}.option_id" => 'Choose an item option.']);
                }
                $group['key'] = 'option_'.$option->id;
                $group['label'] = $option->title ?: $option->name;
                $group['category_id'] = null;
                $exists = \App\Models\OptionValue::query()->where('option_id', $option->id)->whereIn('id', $group['allowed_ids'])->count();
            }
            if ($exists !== count($group['allowed_ids'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(["selection_groups.{$index}.allowed_ids" => 'Choices must belong to the selected source.']);
            }
        }
        unset($group);
        $keys = collect($data['selection_groups'])->pluck('key');
        if ($keys->unique()->count() !== $keys->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['selection_groups' => 'Choose each option or menu category only once.']);
        }

        unset($data['media_id']);
        $data['primary_item_id'] = $data['primary_item_id'] ?? null;
        $data['primary_group_key'] = null;
        $data['fixed_option_value_ids'] = $data['fixed_option_value_ids'] ?? [];
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function syncImage(MealDeal $deal, Request $request): void
    {
        if ($request->filled('media_id')) {
            $deal->syncMedia((int) $request->input('media_id'), MealDeal::IMAGE_TAG);
        } else {
            $deal->detachMediaTags(MealDeal::IMAGE_TAG);
        }
    }
}
