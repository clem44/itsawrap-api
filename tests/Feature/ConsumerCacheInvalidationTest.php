<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Option;
use App\Models\OptionValue;
use App\Support\Cache\ConsumerCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * itsawrapweb caches the menu, offers and bundles it reads from this API, and
 * shares this cache store. Editing anything those payloads are built from has
 * to drop those entries, or the storefront serves stale food data until its
 * TTL lapses.
 */
class ConsumerCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warmConsumerCache();
    }

    public function test_saving_an_item_clears_the_storefront_caches(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);

        Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $category->id, 'cost' => 12, 'active' => true]);

        $this->assertConsumerCacheCleared();
    }

    public function test_repricing_an_item_clears_the_storefront_caches(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $category->id, 'cost' => 12, 'active' => true]);

        $this->warmConsumerCache();
        $item->update(['cost' => 14]);

        $this->assertConsumerCacheCleared();
    }

    public function test_deleting_an_item_clears_the_storefront_caches(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $category->id, 'cost' => 12, 'active' => true]);

        $this->warmConsumerCache();
        $item->delete();

        $this->assertConsumerCacheCleared();
    }

    public function test_changing_an_option_value_price_clears_the_storefront_caches(): void
    {
        $option = Option::query()->create(['name' => 'Protein']);
        $value = OptionValue::query()->create(['option_id' => $option->id, 'name' => 'Chicken', 'price' => 12]);

        $this->warmConsumerCache();
        $value->update(['price' => 13]);

        $this->assertConsumerCacheCleared();
    }

    public function test_saving_an_offer_clears_the_storefront_caches(): void
    {
        Offer::query()->create([
            'name' => 'Wrap + Fries',
            'offer_type' => 'bundle_fixed_price',
            'discount_type' => 'fixed_price',
            'discount_value' => 14,
            'is_active' => true,
        ]);

        $this->assertConsumerCacheCleared();
    }

    public function test_saving_a_bundle_clears_the_storefront_caches(): void
    {
        Bundle::query()->create(['name' => 'Green Machine', 'is_active' => true, 'sort_order' => 0]);

        $this->assertConsumerCacheCleared();
    }

    private function warmConsumerCache(): void
    {
        foreach (ConsumerCache::keys() as $key) {
            Cache::put($key, ['stale' => true], 300);
        }
    }

    private function assertConsumerCacheCleared(): void
    {
        foreach (ConsumerCache::keys() as $key) {
            $this->assertFalse(Cache::has($key), "{$key} should have been cleared for the storefront.");
        }
    }
}
