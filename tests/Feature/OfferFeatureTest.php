<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;
use Tests\TestCase;

class OfferFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['mediable.image_optimization.enabled' => false]);
    }

    public function test_admin_can_render_offers_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.offers.index'))
            ->assertOk()
            ->assertSee('Create Offer')
            ->assertSee('Featured Image')
            ->assertSee('data-flatpickr-datetime', false);
    }

    public function test_admin_can_create_update_and_delete_offer_with_featured_image(): void
    {
        $admin = $this->makeAdmin();
        $wraps = Category::query()->create(['name' => 'Wraps']);
        $sides = Category::query()->create(['name' => 'Sides']);
        $firstMedia = $this->makeMedia('offer-first.jpg');
        $secondMedia = $this->makeMedia('offer-second.jpg');

        $this->actingAs($admin)
            ->post(route('admin.offers.store'), [
                'name' => 'Get 10% off Wraps',
                'description' => 'Lunch special',
                'offer_type' => Offer::TYPE_PERCENTAGE_DISCOUNT,
                'discount_type' => Offer::DISCOUNT_PERCENT,
                'discount_value' => '10',
                'qualifying_category_id' => $wraps->id,
                'starts_at' => '2026-08-31T10:00',
                'ends_at' => '2026-09-30T22:00',
                'is_active' => 'on',
                'priority' => 5,
                'media_id' => $firstMedia->id,
            ])
            ->assertRedirect(route('admin.offers.index'));

        $offer = Offer::query()->where('name', 'Get 10% off Wraps')->firstOrFail();

        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'offer_type' => Offer::TYPE_PERCENTAGE_DISCOUNT,
            'discount_type' => Offer::DISCOUNT_PERCENT,
            'qualifying_category_id' => $wraps->id,
            'is_active' => true,
            'priority' => 5,
            'created_by_user_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => Offer::class,
            'mediable_id' => $offer->id,
            'tag' => Offer::IMAGE_TAG,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.offers.update', $offer), [
                'name' => 'Spend $20, Get A Free Side',
                'offer_type' => Offer::TYPE_SPEND_X_GET_Y,
                'discount_type' => Offer::DISCOUNT_FREE_ITEM,
                'minimum_subtotal' => '20.00',
                'reward_category_id' => $sides->id,
                'reward_quantity' => 1,
                'is_active' => 'on',
                'is_stackable' => 'on',
                'priority' => 10,
                'media_id' => $secondMedia->id,
            ])
            ->assertRedirect(route('admin.offers.index'));

        $offer->refresh();

        $this->assertSame('Spend $20, Get A Free Side', $offer->name);
        $this->assertTrue($offer->is_stackable);
        $this->assertSame(10, $offer->priority);
        $this->assertDatabaseMissing('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => Offer::class,
            'mediable_id' => $offer->id,
            'tag' => Offer::IMAGE_TAG,
        ]);
        $this->assertDatabaseHas('mediables', [
            'media_id' => $secondMedia->id,
            'mediable_type' => Offer::class,
            'mediable_id' => $offer->id,
            'tag' => Offer::IMAGE_TAG,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.offers.update', $offer), [
                'name' => 'Spend $20, Get A Free Side',
                'offer_type' => Offer::TYPE_SPEND_X_GET_Y,
                'discount_type' => Offer::DISCOUNT_FREE_ITEM,
                'minimum_subtotal' => '20.00',
                'reward_category_id' => $sides->id,
                'reward_quantity' => 1,
                'is_active' => 'on',
            ])
            ->assertRedirect(route('admin.offers.index'));

        $this->assertDatabaseMissing('mediables', [
            'media_id' => $secondMedia->id,
            'mediable_type' => Offer::class,
            'mediable_id' => $offer->id,
            'tag' => Offer::IMAGE_TAG,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.offers.destroy', $offer))
            ->assertRedirect(route('admin.offers.index'));

        $this->assertSoftDeleted('offers', ['id' => $offer->id]);
    }

    public function test_offer_validation_requires_complete_free_item_rules(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.offers.store'), [
                'name' => 'Buy 6 wraps, 7th free',
                'offer_type' => Offer::TYPE_BUY_X_GET_Y,
                'discount_type' => Offer::DISCOUNT_FREE_ITEM,
                'is_active' => 'on',
            ])
            ->assertSessionHasErrors(['required_quantity', 'reward_item_id', 'reward_quantity']);
    }

    public function test_admin_can_create_fixed_price_bundle_offer_without_reward_item(): void
    {
        $admin = $this->makeAdmin();
        $bundle = $this->makeBundle('Wrap + Fries + Drink');

        $this->actingAs($admin)
            ->post(route('admin.offers.store'), [
                'name' => 'Wrap + Fries + Drink for $14',
                'offer_type' => Offer::TYPE_BUNDLE_FIXED_PRICE,
                'discount_type' => Offer::DISCOUNT_FIXED_PRICE,
                'discount_value' => '14.00',
                'bundle_id' => $bundle->id,
                'is_active' => 'on',
            ])
            ->assertRedirect(route('admin.offers.index'));

        $offer = Offer::query()->where('name', 'Wrap + Fries + Drink for $14')->firstOrFail();

        $this->assertSame(Offer::TYPE_BUNDLE_FIXED_PRICE, $offer->offer_type);
        $this->assertSame(Offer::DISCOUNT_FIXED_PRICE, $offer->discount_type);
        $this->assertSame($bundle->id, $offer->bundle_id);
        $this->assertNull($offer->reward_item_id);
        $this->assertNull($offer->reward_category_id);
    }

    public function test_fixed_price_bundle_requires_bundle_and_fixed_price_discount_type(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.offers.store'), [
                'name' => 'Incomplete Bundle',
                'offer_type' => Offer::TYPE_BUNDLE_FIXED_PRICE,
                'discount_type' => Offer::DISCOUNT_FIXED_AMOUNT,
                'discount_value' => '14.00',
                'is_active' => 'on',
            ])
            ->assertSessionHasErrors(['discount_type', 'bundle_id']);
    }

    public function test_active_fixed_price_bundle_offer_requires_available_bundle(): void
    {
        $bundle = Bundle::query()->create(['name' => 'Draft Bundle', 'is_active' => false]);

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.offers.store'), [
                'name' => 'Unavailable Bundle Offer',
                'offer_type' => Offer::TYPE_BUNDLE_FIXED_PRICE,
                'discount_type' => Offer::DISCOUNT_FIXED_PRICE,
                'discount_value' => '14.00',
                'bundle_id' => $bundle->id,
                'is_active' => 'on',
            ])
            ->assertSessionHasErrors(['bundle_id']);
    }

    public function test_guest_offers_endpoint_returns_only_current_active_offers(): void
    {
        $category = Category::query()->create(['name' => 'Rice Bowls']);
        $item = Item::query()->create([
            'name' => 'Chicken Rice Bowl',
            'category_id' => $category->id,
            'cost' => 14.00,
            'active' => true,
        ]);
        $media = $this->makeMedia('rice-offer.jpg');
        $active = Offer::query()->create([
            'name' => 'Get 10% off Rice Bowls',
            'offer_type' => Offer::TYPE_PERCENTAGE_DISCOUNT,
            'discount_type' => Offer::DISCOUNT_PERCENT,
            'discount_value' => 10,
            'qualifying_category_id' => $category->id,
            'qualifying_item_id' => $item->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
            'priority' => 9,
        ]);
        $active->syncMedia($media, Offer::IMAGE_TAG);

        $wrap = Item::query()->create([
            'name' => 'Chicken Wrap',
            'category_id' => $category->id,
            'cost' => 10.00,
            'active' => true,
        ]);
        $drink = Item::query()->create([
            'name' => 'Drink',
            'category_id' => $category->id,
            'cost' => 3.00,
            'active' => true,
        ]);
        $bundleMedia = $this->makeMedia('wrap-bundle.jpg');
        $bundle = Bundle::query()->create([
            'name' => 'Wrap + Drink',
            'description' => 'Wrap and drink combo',
            'is_active' => true,
        ]);
        $bundle->bundleItems()->create(['item_id' => $wrap->id, 'quantity' => 1, 'sort_order' => 0]);
        $bundle->bundleItems()->create(['item_id' => $drink->id, 'quantity' => 1, 'sort_order' => 1]);
        $bundle->syncMedia($bundleMedia, Bundle::IMAGE_TAG);

        Offer::query()->create([
            'name' => 'Wrap + Drink for $11',
            'offer_type' => Offer::TYPE_BUNDLE_FIXED_PRICE,
            'discount_type' => Offer::DISCOUNT_FIXED_PRICE,
            'discount_value' => 11,
            'bundle_id' => $bundle->id,
            'is_active' => true,
            'priority' => 8,
        ]);
        $inactiveBundle = Bundle::query()->create([
            'name' => 'Inactive Combo',
            'is_active' => false,
        ]);
        Offer::query()->create([
            'name' => 'Inactive Bundle Offer',
            'offer_type' => Offer::TYPE_BUNDLE_FIXED_PRICE,
            'discount_type' => Offer::DISCOUNT_FIXED_PRICE,
            'discount_value' => 10,
            'bundle_id' => $inactiveBundle->id,
            'is_active' => true,
            'priority' => 7,
        ]);

        Offer::query()->create([
            'name' => 'Inactive Offer',
            'offer_type' => Offer::TYPE_FIXED_DISCOUNT,
            'discount_type' => Offer::DISCOUNT_FIXED_AMOUNT,
            'discount_value' => 5,
            'is_active' => false,
        ]);
        Offer::query()->create([
            'name' => 'Expired Offer',
            'offer_type' => Offer::TYPE_FIXED_DISCOUNT,
            'discount_type' => Offer::DISCOUNT_FIXED_AMOUNT,
            'discount_value' => 5,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/offers')
            ->assertOk()
            ->assertJsonCount(2, 'offers')
            ->assertJsonPath('offers.0.name', 'Get 10% off Rice Bowls')
            ->assertJsonPath('offers.0.qualifying_category.name', 'Rice Bowls')
            ->assertJsonPath('offers.0.qualifying_item.name', 'Chicken Rice Bowl')
            ->assertJsonPath('offers.0.featured_image_url', fn (?string $url) => filled($url))
            ->assertJsonPath('offers.1.name', 'Wrap + Drink for $11')
            ->assertJsonPath('offers.1.featured_image_url', fn (?string $url) => filled($url))
            ->assertJsonPath('offers.1.bundle.name', 'Wrap + Drink')
            ->assertJsonPath('offers.1.bundle.items.0.name', 'Chicken Wrap')
            ->assertJsonPath('offers.1.bundle.items.1.name', 'Drink')
            ->assertJsonPath('offers.1.bundle_items.0.name', 'Chicken Wrap')
            ->assertJsonPath('offers.1.bundle_items.1.name', 'Drink');
    }

    public function test_customer_offers_endpoint_matches_guest_active_offers(): void
    {
        Offer::query()->create([
            'name' => 'Customer Offer',
            'offer_type' => Offer::TYPE_FIXED_DISCOUNT,
            'discount_type' => Offer::DISCOUNT_FIXED_AMOUNT,
            'discount_value' => 3,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->makeCustomer());

        $this->getJson('/api/me/offers')
            ->assertOk()
            ->assertJsonCount(1, 'offers')
            ->assertJsonPath('offers.0.name', 'Customer Offer');
    }

    public function test_media_library_can_attach_media_to_offers(): void
    {
        $media = $this->makeMedia('attach-offer.jpg');
        $offer = Offer::query()->create([
            'name' => 'Attachable Offer',
            'offer_type' => Offer::TYPE_FIXED_DISCOUNT,
            'discount_type' => Offer::DISCOUNT_FIXED_AMOUNT,
            'discount_value' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.media-library.attach'), [
                'media_id' => $media->id,
                'mediable_type' => Offer::class,
                'mediable_id' => $offer->id,
                'tag' => Offer::IMAGE_TAG,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $media->id);

        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => Offer::class,
            'mediable_id' => $offer->id,
            'tag' => Offer::IMAGE_TAG,
        ]);
    }

    private function makeMedia(string $name): Media
    {
        return MediaUploader::fromSource(UploadedFile::fake()->image($name, 200, 100))
            ->toDisk('public')
            ->toDirectory('media-library')
            ->onDuplicateIncrement()
            ->upload();
    }

    private function makeBundle(string $name): Bundle
    {
        $category = Category::query()->create(['name' => "{$name} Category"]);
        $wrap = Item::query()->create(['name' => "{$name} Wrap", 'category_id' => $category->id, 'cost' => 10, 'active' => true]);
        $side = Item::query()->create(['name' => "{$name} Side", 'category_id' => $category->id, 'cost' => 4, 'active' => true]);
        $bundle = Bundle::query()->create(['name' => $name, 'is_active' => true]);

        $bundle->bundleItems()->create(['item_id' => $wrap->id, 'quantity' => 1, 'sort_order' => 0]);
        $bundle->bundleItems()->create(['item_id' => $side->id, 'quantity' => 1, 'sort_order' => 1]);

        return $bundle;
    }

    private function makeAdmin(string $username = 'offer-admin'): User
    {
        return User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => $username,
            'email' => "{$username}@example.com",
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }

    private function makeCustomer(string $username = 'offer-customer'): User
    {
        return User::query()->create([
            'firstname' => 'Customer',
            'lastname' => 'User',
            'username' => $username,
            'email' => "{$username}@example.com",
            'password' => Hash::make('password'),
            'role_id' => 3,
        ]);
    }
}
