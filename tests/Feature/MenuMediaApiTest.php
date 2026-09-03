<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;
use Tests\TestCase;

class MenuMediaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['mediable.image_optimization.enabled' => false]);
    }

    public function test_guest_menu_includes_primary_media_for_categories_and_items(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'category_id' => $category->id,
            'cost' => 12.50,
            'active' => true,
        ]);
        $categoryMedia = $this->makeMedia('wraps.jpg');
        $itemMedia = $this->makeMedia('chicken-wrap.jpg');

        $category->syncMedia($categoryMedia, Category::IMAGE_TAG);
        $item->syncMedia($itemMedia, Item::IMAGE_TAG);

        $this->getJson('/api/guest/menu')
            ->assertOk()
            ->assertJsonPath('categories.0.primary_media.id', $categoryMedia->id)
            ->assertJsonPath('categories.0.primary_media.basename', 'wraps.jpg')
            ->assertJsonPath('categories.0.primary_image_url', fn (?string $url) => filled($url))
            ->assertJsonPath('items.0.primary_media.id', $itemMedia->id)
            ->assertJsonPath('items.0.primary_media.basename', 'chicken-wrap.jpg')
            ->assertJsonPath('items.0.primary_image_url', fn (?string $url) => filled($url))
            ->assertJsonPath('items.0.category.primary_media.id', $categoryMedia->id)
            ->assertJsonMissingPath('categories.0.media')
            ->assertJsonMissingPath('items.0.media')
            ->assertJsonMissingPath('items.0.category.media');
    }

    public function test_staff_category_and_item_apis_include_primary_media(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'category_id' => $category->id,
            'cost' => 12.50,
            'active' => true,
        ]);
        $categoryMedia = $this->makeMedia('staff-wraps.jpg');
        $itemMedia = $this->makeMedia('staff-chicken-wrap.jpg');

        $category->syncMedia($categoryMedia, Category::IMAGE_TAG);
        $item->syncMedia($itemMedia, Item::IMAGE_TAG);

        Sanctum::actingAs($this->makeStaffUser());

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('0.primary_media.id', $categoryMedia->id)
            ->assertJsonPath('0.primary_image_url', fn (?string $url) => filled($url))
            ->assertJsonMissingPath('0.media');

        $this->getJson('/api/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('primary_media.id', $categoryMedia->id)
            ->assertJsonPath('items.0.primary_media.id', $itemMedia->id)
            ->assertJsonMissingPath('media')
            ->assertJsonMissingPath('items.0.media');

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonPath('0.primary_media.id', $itemMedia->id)
            ->assertJsonPath('0.primary_image_url', fn (?string $url) => filled($url))
            ->assertJsonPath('0.category.primary_media.id', $categoryMedia->id)
            ->assertJsonMissingPath('0.media')
            ->assertJsonMissingPath('0.category.media');

        $this->getJson('/api/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('primary_media.id', $itemMedia->id)
            ->assertJsonPath('category.primary_media.id', $categoryMedia->id)
            ->assertJsonMissingPath('media')
            ->assertJsonMissingPath('category.media');
    }

    private function makeMedia(string $name): Media
    {
        return MediaUploader::fromSource(UploadedFile::fake()->image($name, 200, 100))
            ->toDisk('public')
            ->toDirectory('media-library')
            ->onDuplicateIncrement()
            ->upload();
    }

    private function makeStaffUser(): User
    {
        return User::query()->create([
            'firstname' => 'Staff',
            'lastname' => 'User',
            'username' => 'menu-media-staff',
            'email' => 'menu-media-staff@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
        ]);
    }
}
