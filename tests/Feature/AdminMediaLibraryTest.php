<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;
use Tests\TestCase;

class AdminMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['mediable.image_optimization.enabled' => false]);
    }

    public function test_admin_can_view_embedded_media_library_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.media-library.show'))
            ->assertOk()
            ->assertSee('data-media-library', false)
            ->assertSee(route('admin.media-library.index'), false);
    }

    public function test_admin_can_upload_and_list_media_files(): void
    {
        $mediaId = $this->uploadImage('summer-beach-sunset.jpg', 'Beach sunset')
            ->assertCreated()
            ->assertJsonPath('data.basename', 'summer-beach-sunset.jpg')
            ->assertJsonPath('data.alt', 'Beach sunset')
            ->json('data.id');

        $this->assertDatabaseHas('media', [
            'id' => $mediaId,
            'filename' => 'summer-beach-sunset',
            'extension' => 'jpg',
            'aggregate_type' => Media::TYPE_IMAGE,
            'alt' => 'Beach sunset',
        ]);

        $this->actingAs($this->makeAdmin('list-admin'))
            ->getJson(route('admin.media-library.index', ['query' => 'summer-beach', 'type' => 'image']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mediaId)
            ->assertJsonPath('data.0.preview_url', fn (?string $url) => filled($url));
    }

    public function test_admin_can_update_media_alt_text(): void
    {
        $media = $this->makeMedia('wrap.jpg');

        $this->actingAs($this->makeAdmin())
            ->patchJson(route('admin.media-library.update', $media), [
                'alt' => 'Wrapped sandwich on a plate',
            ])
            ->assertOk()
            ->assertJsonPath('data.alt', 'Wrapped sandwich on a plate');

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'alt' => 'Wrapped sandwich on a plate',
        ]);
    }

    public function test_admin_can_delete_unattached_media(): void
    {
        $media = $this->makeMedia('delete-me.jpg');

        $this->actingAs($this->makeAdmin())
            ->deleteJson(route('admin.media-library.destroy', $media))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->getDiskPath());
    }

    public function test_admin_cannot_delete_attached_media(): void
    {
        $media = $this->makeMedia('attached.jpg');
        $item = $this->makeItem();

        $item->attachMedia($media, 'primary_image');

        $this->actingAs($this->makeAdmin())
            ->deleteJson(route('admin.media-library.destroy', $media))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This media file is attached to existing records and cannot be deleted.');

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_admin_can_attach_media_to_allowed_item_tag(): void
    {
        $media = $this->makeMedia('attach.jpg');
        $item = $this->makeItem();

        $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.media-library.attach'), [
                'media_id' => $media->id,
                'mediable_type' => Item::class,
                'mediable_id' => $item->id,
                'tag' => 'primary_image',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $media->id);

        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => Item::class,
            'mediable_id' => $item->id,
            'tag' => 'primary_image',
        ]);
    }

    public function test_item_create_and_update_sync_primary_image_media(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Wraps']);
        $firstMedia = $this->makeMedia('first.jpg');
        $secondMedia = $this->makeMedia('second.jpg');

        $this->actingAs($admin)
            ->post(route('admin.items.store'), [
                'name' => 'Chicken Wrap',
                'description' => 'Grilled chicken wrap',
                'cost' => '12.50',
                'category_id' => $category->id,
                'media_id' => $firstMedia->id,
                'active' => 'on',
            ])
            ->assertRedirect(route('admin.items.index'));

        $item = Item::query()->where('name', 'Chicken Wrap')->firstOrFail();

        $this->assertDatabaseHas('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => Item::class,
            'mediable_id' => $item->id,
            'tag' => 'primary_image',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.items.update', $item), [
                'name' => 'Chicken Wrap',
                'description' => 'Grilled chicken wrap',
                'cost' => '12.50',
                'category_id' => $category->id,
                'media_id' => $secondMedia->id,
                'active' => 'on',
            ])
            ->assertRedirect(route('admin.items.index'));

        $this->assertDatabaseMissing('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => Item::class,
            'mediable_id' => $item->id,
            'tag' => 'primary_image',
        ]);

        $this->assertDatabaseHas('mediables', [
            'media_id' => $secondMedia->id,
            'mediable_type' => Item::class,
            'mediable_id' => $item->id,
            'tag' => 'primary_image',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.items.update', $item), [
                'name' => 'Chicken Wrap',
                'description' => 'Grilled chicken wrap',
                'cost' => '12.50',
                'category_id' => $category->id,
                'active' => 'on',
            ])
            ->assertRedirect(route('admin.items.index'));

        $this->assertDatabaseMissing('mediables', [
            'media_id' => $secondMedia->id,
            'mediable_type' => Item::class,
            'mediable_id' => $item->id,
            'tag' => 'primary_image',
        ]);
    }

    private function uploadImage(string $name, string $alt = '')
    {
        return $this->actingAs($this->makeAdmin('upload-admin'))
            ->postJson(route('admin.media-library.store'), [
                'file' => UploadedFile::fake()->image($name, 200, 100),
                'alt' => $alt,
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

    private function makeItem(): Item
    {
        $category = Category::query()->create(['name' => 'Wraps']);

        return Item::query()->create([
            'name' => 'Veggie Wrap',
            'category_id' => $category->id,
            'cost' => 10.00,
            'active' => true,
        ]);
    }

    private function makeAdmin(string $username = 'media-admin'): User
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
