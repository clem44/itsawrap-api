<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Plank\Mediable\Media;

class OfferController extends Controller
{
    public function index(MediaLibraryPresenter $mediaPresenter): View
    {
        $offers = Offer::query()
            ->with([
                'qualifyingCategory',
                'qualifyingItem',
                'rewardCategory',
                'rewardItem',
                'bundle' => fn ($query) => $query->withMedia(Bundle::IMAGE_TAG)->with('bundleItems.item.category'),
            ])
            ->withMedia(Offer::IMAGE_TAG)
            ->orderByDesc('is_active')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $bundles = Bundle::query()
            ->withMedia(Bundle::IMAGE_TAG)
            ->with('bundleItems.item.category')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $oldMedia = null;

        if (old('form_action') && old('media_id')) {
            $media = Media::query()->find(old('media_id'));
            $oldMedia = $media ? $mediaPresenter->present($media) : null;
        }

        return view('admin.offers.index', compact('offers', 'categories', 'items', 'bundles', 'mediaPresenter', 'oldMedia'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOffer($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_stackable'] = $request->boolean('is_stackable');
        $validated['created_by_user_id'] = $request->user()?->id;

        $offer = Offer::query()->create($validated);
        $this->syncPrimaryImage($offer, $request);

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer created successfully.');
    }

    public function update(Request $request, Offer $offer): RedirectResponse
    {
        $validated = $this->validateOffer($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_stackable'] = $request->boolean('is_stackable');

        $offer->update($validated);
        $this->syncPrimaryImage($offer, $request);

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer updated successfully.');
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $offer->delete();

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateOffer(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'offer_type' => ['required', Rule::in(Offer::offerTypes())],
            'discount_type' => ['required', Rule::in(Offer::discountTypes())],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'qualifying_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'qualifying_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'reward_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'reward_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'bundle_id' => ['nullable', 'integer', 'exists:bundles,id'],
            'minimum_subtotal' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'required_quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'reward_quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), 'after:starts_at')],
            'is_active' => ['nullable'],
            'is_stackable' => ['nullable'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (in_array($request->input('discount_type'), [Offer::DISCOUNT_PERCENT, Offer::DISCOUNT_FIXED_AMOUNT, Offer::DISCOUNT_FIXED_PRICE], true) && ! $request->filled('discount_value')) {
                $validator->errors()->add('discount_value', 'Discount value is required for percentage, fixed amount, and fixed price offers.');
            }

            if ($request->input('discount_type') === Offer::DISCOUNT_PERCENT && (float) $request->input('discount_value', 0) > 100) {
                $validator->errors()->add('discount_value', 'Percentage discounts cannot exceed 100.');
            }

            if ($request->input('offer_type') === Offer::TYPE_BUNDLE_FIXED_PRICE) {
                if ($request->input('discount_type') !== Offer::DISCOUNT_FIXED_PRICE) {
                    $validator->errors()->add('discount_type', 'Bundle fixed price offers must use the fixed price discount type.');
                }

                if (! $request->filled('bundle_id')) {
                    $validator->errors()->add('bundle_id', 'Select a bundle for a fixed price bundle offer.');
                }

                if ($request->boolean('is_active') && $request->filled('bundle_id')) {
                    $bundleAvailable = Bundle::query()
                        ->whereKey($request->integer('bundle_id'))
                        ->availableForOrdering()
                        ->exists();

                    if (! $bundleAvailable) {
                        $validator->errors()->add('bundle_id', 'Active fixed price bundle offers must use an active bundle with available items.');
                    }
                }
            }

            if (in_array($request->input('offer_type'), [Offer::TYPE_BUY_X_GET_Y, Offer::TYPE_SPEND_X_GET_Y], true) && $request->input('discount_type') === Offer::DISCOUNT_FREE_ITEM) {
                if (! $request->filled('reward_category_id') && ! $request->filled('reward_item_id')) {
                    $validator->errors()->add('reward_item_id', 'A reward category or reward item is required for free-item offers.');
                }

                if (! $request->filled('reward_quantity')) {
                    $validator->errors()->add('reward_quantity', 'Reward quantity is required for free-item offers.');
                }
            }

            if ($request->input('offer_type') === Offer::TYPE_BUY_X_GET_Y && ! $request->filled('required_quantity')) {
                $validator->errors()->add('required_quantity', 'Required quantity is required for buy-X-get-Y offers.');
            }

            if ($request->input('offer_type') === Offer::TYPE_SPEND_X_GET_Y && ! $request->filled('minimum_subtotal')) {
                $validator->errors()->add('minimum_subtotal', 'Minimum subtotal is required for spend-threshold offers.');
            }
        });

        $validated = $validator->validate();
        $validated['priority'] = $validated['priority'] ?? 0;

        unset($validated['media_id']);

        return $validated;
    }

    private function syncPrimaryImage(Offer $offer, Request $request): void
    {
        if ($request->filled('media_id')) {
            $offer->syncMedia((int) $request->input('media_id'), Offer::IMAGE_TAG);

            return;
        }

        $offer->detachMediaTags(Offer::IMAGE_TAG);
    }
}
