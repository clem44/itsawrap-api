<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\Offer;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;
use Tests\TestCase;

class BundleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['mediable.image_optimization.enabled' => false]);
    }

    public function test_admin_can_render_bundles_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.bundles.index'))
            ->assertOk()
            ->assertSee('Create Bundle')
            ->assertSee('Featured Image')
            ->assertSee('Bundle Items')
            ->assertSee('Default Options');
    }

    public function test_admin_can_create_update_and_delete_bundle_with_featured_image(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Combos']);
        $wrap = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 10, 'active' => true]);
        $drink = Item::query()->create(['name' => 'Drink', 'category_id' => $category->id, 'cost' => 3, 'active' => true]);
        $fries = Item::query()->create(['name' => 'Fries', 'category_id' => $category->id, 'cost' => 4, 'active' => true]);
        [$itemOption, $optionValue] = $this->makeItemOptionValue($wrap, 'Sauce', 'BBQ');
        $firstMedia = $this->makeMedia('bundle-first.jpg');
        $secondMedia = $this->makeMedia('bundle-second.jpg');

        $this->actingAs($admin)
            ->post(route('admin.bundles.store'), [
                'name' => 'Wrap + Drink',
                'description' => 'Lunch bundle',
                'sort_order' => 2,
                'is_active' => 'on',
                'media_id' => $firstMedia->id,
                'items' => [
                    [
                        'item_id' => $wrap->id,
                        'quantity' => 1,
                        'sort_order' => 0,
                        'price_override' => '9.50',
                        'label_override' => 'Lunch Wrap',
                        'option_values' => [
                            [
                                'item_option_id' => $itemOption->id,
                                'option_value_id' => $optionValue->id,
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    [
                        'item_id' => $drink->id,
                        'quantity' => 1,
                        'sort_order' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.bundles.index'));

        $bundle = Bundle::query()->where('name', 'Wrap + Drink')->firstOrFail();

        $this->assertDatabaseHas('bundles', [
            'id' => $bundle->id,
            'description' => 'Lunch bundle',
            'sort_order' => 2,
            'is_active' => true,
            'created_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('bundle_items', [
            'bundle_id' => $bundle->id,
            'item_id' => $wrap->id,
            'quantity' => 1,
            'price_override' => '9.50',
            'label_override' => 'Lunch Wrap',
        ]);
        $this->assertDatabaseHas('bundle_item_option_values', [
            'item_option_id' => $itemOption->id,
            'option_value_id' => $optionValue->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => Bundle::class,
            'mediable_id' => $bundle->id,
            'tag' => Bundle::IMAGE_TAG,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bundles.update', $bundle), [
                'name' => 'Wrap + Fries',
                'description' => 'Updated lunch bundle',
                'sort_order' => 4,
                'is_active' => 'on',
                'media_id' => $secondMedia->id,
                'items' => [
                    [
                        'item_id' => $wrap->id,
                        'quantity' => 2,
                        'sort_order' => 0,
                    ],
                    [
                        'item_id' => $fries->id,
                        'quantity' => 1,
                        'sort_order' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.bundles.index'));

        $bundle->refresh();

        $this->assertSame('Wrap + Fries', $bundle->name);
        $this->assertSame(4, $bundle->sort_order);
        $this->assertDatabaseMissing('bundle_items', [
            'bundle_id' => $bundle->id,
            'item_id' => $drink->id,
        ]);
        $this->assertDatabaseHas('bundle_items', [
            'bundle_id' => $bundle->id,
            'item_id' => $fries->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => Bundle::class,
            'mediable_id' => $bundle->id,
            'tag' => Bundle::IMAGE_TAG,
        ]);
        $this->assertDatabaseHas('mediables', [
            'media_id' => $secondMedia->id,
            'mediable_type' => Bundle::class,
            'mediable_id' => $bundle->id,
            'tag' => Bundle::IMAGE_TAG,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.bundles.destroy', $bundle))
            ->assertRedirect(route('admin.bundles.index'));

        $this->assertSoftDeleted('bundles', ['id' => $bundle->id]);
    }

    public function test_admin_cannot_delete_bundle_used_by_offer(): void
    {
        $bundle = $this->makeBundle();
        Offer::query()->create([
            'name' => 'Bundle Offer',
            'offer_type' => Offer::TYPE_BUNDLE_FIXED_PRICE,
            'discount_type' => Offer::DISCOUNT_FIXED_PRICE,
            'discount_value' => 12,
            'bundle_id' => $bundle->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.bundles.destroy', $bundle))
            ->assertRedirect(route('admin.bundles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bundles', ['id' => $bundle->id, 'deleted_at' => null]);
    }

    public function test_guest_bundles_endpoint_returns_only_current_active_bundles(): void
    {
        $active = $this->makeBundle('Lunch Combo');
        $activeMedia = $this->makeMedia('lunch-combo.jpg');
        $active->syncMedia($activeMedia, Bundle::IMAGE_TAG);
        $inactiveItemCategory = Category::query()->create(['name' => 'Inactive Item Category']);
        $inactiveItem = Item::query()->create([
            'name' => 'Hidden Wrap',
            'category_id' => $inactiveItemCategory->id,
            'cost' => 12,
            'active' => false,
        ]);
        $bundleWithInactiveItem = Bundle::query()->create([
            'name' => 'Hidden Item Combo',
            'is_active' => true,
        ]);
        $bundleWithInactiveItem->bundleItems()->create(['item_id' => $inactiveItem->id, 'quantity' => 1]);

        Bundle::query()->create([
            'name' => 'Inactive Combo',
            'is_active' => false,
        ]);
        Bundle::query()->create([
            'name' => 'Expired Combo',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/bundles')
            ->assertOk()
            ->assertJsonCount(1, 'bundles')
            ->assertJsonPath('bundles.0.name', 'Lunch Combo')
            ->assertJsonPath('bundles.0.featured_image_url', fn (?string $url) => filled($url))
            ->assertJsonPath('bundles.0.items.0.name', 'Chicken Wrap')
            ->assertJsonPath('bundles.0.items.0.quantity', 1);

        $this->getJson('/api/guest/menu')
            ->assertOk()
            ->assertJsonPath('bundles.0.name', 'Lunch Combo');
    }

    public function test_media_library_can_attach_media_to_bundles(): void
    {
        $media = $this->makeMedia('attach-bundle.jpg');
        $bundle = $this->makeBundle('Attachable Bundle');

        $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.media-library.attach'), [
                'media_id' => $media->id,
                'mediable_type' => Bundle::class,
                'mediable_id' => $bundle->id,
                'tag' => Bundle::IMAGE_TAG,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $media->id);

        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => Bundle::class,
            'mediable_id' => $bundle->id,
            'tag' => Bundle::IMAGE_TAG,
        ]);
    }

    private function makeBundle(string $name = 'Lunch Combo'): Bundle
    {
        $category = Category::query()->firstOrCreate(['name' => 'Combos']);
        $wrap = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 10, 'active' => true]);
        $drink = Item::query()->create(['name' => 'Drink', 'category_id' => $category->id, 'cost' => 3, 'active' => true]);
        $bundle = Bundle::query()->create(['name' => $name, 'is_active' => true]);

        $bundle->bundleItems()->create(['item_id' => $wrap->id, 'quantity' => 1, 'sort_order' => 0]);
        $bundle->bundleItems()->create(['item_id' => $drink->id, 'quantity' => 1, 'sort_order' => 1]);

        return $bundle;
    }

    /**
     * @return array{ItemOption, OptionValue}
     */
    private function makeItemOptionValue(Item $item, string $optionName, string $valueName): array
    {
        $option = Option::query()->create(['name' => $optionName]);
        $optionValue = OptionValue::query()->create([
            'option_id' => $option->id,
            'name' => $valueName,
            'price' => 0,
        ]);
        $itemOption = ItemOption::query()->create([
            'item_id' => $item->id,
            'option_id' => $option->id,
            'required' => false,
            'type' => 'single',
        ]);
        $itemOption->itemOptionValues()->create([
            'option_value_id' => $optionValue->id,
            'price' => 0,
            'in_stock' => true,
        ]);

        return [$itemOption, $optionValue];
    }

    private function makeMedia(string $name): Media
    {
        return MediaUploader::fromSource(UploadedFile::fake()->image($name, 200, 100))
            ->toDisk('public')
            ->toDirectory('media-library')
            ->onDuplicateIncrement()
            ->upload();
    }

    private function makeAdmin(string $username = 'bundle-admin'): User
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
}
