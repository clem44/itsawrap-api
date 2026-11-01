<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMealDealRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\MealDeal;
use App\Models\Option;
use App\Services\MealDeals\MealDealAvailability;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(SaveMealDealRequest $request): RedirectResponse
    {
        $data = $request->mealDealData();
        DB::transaction(function () use ($data, $request): void {
            $deal = MealDeal::query()->create($data);
            $this->syncImage($deal, $request);
        });

        return to_route('admin.meal-deals.index')->with('success', 'Meal Deal created.');
    }

    public function update(SaveMealDealRequest $request, MealDeal $mealDeal): RedirectResponse
    {
        $data = $request->mealDealData();
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

    private function syncImage(MealDeal $deal, Request $request): void
    {
        if ($request->filled('media_id')) {
            $deal->syncMedia((int) $request->input('media_id'), MealDeal::IMAGE_TAG);
        } else {
            $deal->detachMediaTags(MealDeal::IMAGE_TAG);
        }
    }
}
