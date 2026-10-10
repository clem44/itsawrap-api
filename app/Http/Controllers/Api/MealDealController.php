<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MealDeal;
use App\Services\MealDeals\MealDealAvailability;
use App\Services\Orders\MealDealSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MealDealController extends Controller
{
    #[OA\Get(path: '/guest/meal-deals', summary: 'List available Meal Deals', tags: ['Meal Deals'], responses: [new OA\Response(response: 200, description: 'Available deals and diagnostics for active deals that cannot be ordered')])]
    public function index(MealDealAvailability $availability): JsonResponse
    {
        $deals = MealDeal::query()->where('is_active', true)->with(['primaryItem', 'primaryCategory'])->withMedia(MealDeal::IMAGE_TAG)->orderBy('sort_order')->orderBy('name')->get();

        $definitions = $deals->map(function (MealDeal $deal) use ($availability): array {
            $inspection = $availability->inspect($deal);

            return [
                'id' => $deal->id,
                'name' => $deal->name,
                'description' => $deal->description,
                'base_price' => (float) $deal->base_price,
                'service_charge_rate' => (float) $deal->service_charge_rate,
                'service_charge' => round((float) $deal->base_price * (float) $deal->service_charge_rate, 2),
                'marketing_max_saving' => $deal->marketing_max_saving === null ? null : (float) $deal->marketing_max_saving,
                'image_url' => $deal->primary_image_url,
                'primary_category_id' => $deal->primary_category_id,
                'primary_category_name' => $deal->primaryCategory?->name,
                'primary_item_id' => $deal->primary_item_id,
                'selection_groups' => $inspection['selection_groups'],
                'eligible_items' => $inspection['eligible_items'],
                'availability_issues' => $inspection['issues'],
            ];
        });

        return response()->json([
            'meal_deals' => $definitions->filter(fn (array $deal) => $deal['availability_issues'] === [])
                ->map(fn (array $deal) => collect($deal)->except('availability_issues')->all())->values(),
            'unavailable_meal_deals' => $definitions->filter(fn (array $deal) => $deal['availability_issues'] !== [])
                ->map(fn (array $deal) => [
                    'id' => $deal['id'],
                    'name' => $deal['name'],
                    'issues' => $deal['availability_issues'],
                ])->values(),
        ]);
    }

    #[OA\Post(path: '/guest/meal-deals/quote', summary: 'Quote configured Meal Deals', tags: ['Meal Deals'], responses: [new OA\Response(response: 200, description: 'Server calculated deal prices'), new OA\Response(response: 422, description: 'Invalid selection')])]
    public function quote(Request $request, MealDealSelectionService $selections): JsonResponse
    {
        $validated = $request->validate([
            'meal_deals' => ['required', 'array', 'min:1', 'max:10'],
            'meal_deals.*.meal_deal_id' => ['required', 'integer'],
            'meal_deals.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'meal_deals.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'meal_deals.*.selections' => ['required', 'array'],
            'meal_deals.*.selections.*' => ['array', 'max:20'],
            'meal_deals.*.selections.*.*' => ['required', 'integer', 'min:1'],
        ]);
        $priced = collect($validated['meal_deals'])->map(fn (array $deal, int $index) => $selections->prepare($deal, "meal_deals.{$index}"));

        return response()->json([
            'meal_deals' => $priced->map(fn (array $deal) => collect($deal)->except('items')->all())->values(),
            'subtotal' => round($priced->sum(fn (array $deal) => ($deal['base_price'] + $deal['extras_price']) * $deal['quantity']), 2),
            'service_charge' => round($priced->sum(fn (array $deal) => $deal['service_charge'] * $deal['quantity']), 2),
        ]);
    }
}
