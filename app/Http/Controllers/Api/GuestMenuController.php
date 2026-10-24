<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Item;
use App\Models\OptionValue;
use App\Support\Bundles\BundlePresenter;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class GuestMenuController extends Controller
{
    #[OA\Get(
        path: '/guest/menu',
        summary: 'Get the storefront menu',
        description: 'Returns menu-visible categories, items with their option values, and active bundles. Option values include their primary media fields.',
        tags: ['Guest'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Storefront menu',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'categories', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Item')),
                        new OA\Property(property: 'bundles', type: 'array', items: new OA\Items(ref: '#/components/schemas/Bundle')),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function __invoke(BundlePresenter $bundlePresenter): JsonResponse
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
                'itemOptions.itemOptionValues.optionValue' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG),
                'itemOptions.itemOptionValues.optionDependency.childOption.option',
                'itemOptions.itemOptionValues.optionDependency.childOption.itemOptionValues.optionValue' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG),
            ])
            ->where('active', true)
            ->where('menu_visible', true)
            ->orderBy('name')
            ->get()
            ->each(function (Item $item): void {
                $item->includePrimaryImageMedia();
                $item->category?->includePrimaryImageMedia();
                $this->includeOptionValueMedia($item);
            });

        $bundles = Bundle::query()
            ->availableForOrdering()
            ->withMedia(Bundle::IMAGE_TAG)
            ->with(Bundle::apiRelations())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Bundle $bundle) => $bundlePresenter->present($bundle))
            ->values();

        return response()->json([
            'categories' => $categories,
            'items' => $items,
            'bundles' => $bundles,
        ]);
    }

    private function includeOptionValueMedia(Item $item): void
    {
        foreach ($item->itemOptions as $itemOption) {
            foreach ($itemOption->itemOptionValues as $itemOptionValue) {
                $itemOptionValue->optionValue?->includePrimaryImageMedia();

                $childOption = $itemOptionValue->optionDependency?->childOption;

                if ($childOption === null) {
                    continue;
                }

                foreach ($childOption->itemOptionValues as $childItemOptionValue) {
                    $childItemOptionValue->optionValue?->includePrimaryImageMedia();
                }
            }
        }
    }
}
