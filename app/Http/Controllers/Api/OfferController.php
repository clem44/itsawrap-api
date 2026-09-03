<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class OfferController extends Controller
{
    #[OA\Get(
        path: '/guest/offers',
        summary: 'List active customer offers',
        description: 'Returns currently active promotional offers for guest checkout and customer account surfaces.',
        tags: ['Offers'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active offers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'offers',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Offer')
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    #[OA\Get(
        path: '/me/offers',
        summary: 'List active customer offers',
        description: 'Returns currently active promotional offers for the signed-in customer account area.',
        tags: ['Offers'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active offers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'offers',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Offer')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $offers = Offer::query()
            ->currentlyActive()
            ->with(['qualifyingCategory', 'qualifyingItem', 'rewardCategory', 'rewardItem'])
            ->withMedia(Offer::IMAGE_TAG)
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->map(fn (Offer $offer) => $this->present($offer))
            ->values();

        return response()->json(['offers' => $offers]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Offer $offer): array
    {
        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'description' => $offer->description,
            'offer_type' => $offer->offer_type,
            'discount_type' => $offer->discount_type,
            'discount_value' => $offer->discount_value,
            'minimum_subtotal' => $offer->minimum_subtotal,
            'required_quantity' => $offer->required_quantity,
            'reward_quantity' => $offer->reward_quantity,
            'bundle_items' => $this->presentBundleItems($offer),
            'starts_at' => $offer->starts_at?->toISOString(),
            'ends_at' => $offer->ends_at?->toISOString(),
            'is_stackable' => $offer->is_stackable,
            'priority' => $offer->priority,
            'featured_image_url' => $offer->featuredImageUrl(),
            'qualifying_category' => $this->presentRelated($offer->qualifyingCategory),
            'qualifying_item' => $this->presentRelated($offer->qualifyingItem),
            'reward_category' => $this->presentRelated($offer->rewardCategory),
            'reward_item' => $this->presentRelated($offer->rewardItem),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentRelated(mixed $model): ?array
    {
        if (! $model) {
            return null;
        }

        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presentBundleItems(Offer $offer): array
    {
        $itemIds = $offer->bundle_item_ids ?? [];

        if ($itemIds === []) {
            return [];
        }

        $positions = array_flip($itemIds);

        return \App\Models\Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'name'])
            ->sortBy(fn ($item) => $positions[$item->id] ?? PHP_INT_MAX)
            ->map(fn ($item) => $this->presentRelated($item))
            ->values()
            ->all();
    }
}
