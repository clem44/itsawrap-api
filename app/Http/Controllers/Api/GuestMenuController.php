<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;

class GuestMenuController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = Category::query()
            ->whereHas('items', fn ($query) => $query->where('active', true))
            ->withMedia(Category::IMAGE_TAG)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each->includePrimaryImageMedia();

        $items = Item::query()
            ->withMedia(Item::IMAGE_TAG)
            ->with([
                'category' => fn ($query) => $query->withMedia(Category::IMAGE_TAG),
                'taxes',
                'itemOptions.option',
                'itemOptions.itemOptionValues.optionValue',
                'itemOptions.itemOptionValues.optionDependency.childOption.option',
                'itemOptions.itemOptionValues.optionDependency.childOption.itemOptionValues.optionValue',
            ])
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->each(function (Item $item): void {
                $item->includePrimaryImageMedia();
                $item->category?->includePrimaryImageMedia();
            });

        return response()->json([
            'categories' => $categories,
            'items' => $items,
        ]);
    }
}
