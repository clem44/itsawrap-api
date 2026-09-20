<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;
use Tests\TestCase;

class OptionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['mediable.image_optimization.enabled' => false]);
    }

    public function test_option_api_stores_title_and_description(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/options', [
            'name' => 'Size',
            'title' => 'Choose a size',
            'description' => 'Select the portion size for this item.',
            'values' => [
                ['name' => 'Large', 'price' => 2.00],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Size')
            ->assertJsonPath('title', 'Choose a size')
            ->assertJsonPath('description', 'Select the portion size for this item.')
            ->assertJsonPath('option_values.0.name', 'Large');

        $this->assertDatabaseHas('options', [
            'name' => 'Size',
            'title' => 'Choose a size',
            'description' => 'Select the portion size for this item.',
        ]);
    }

    public function test_option_api_updates_title_and_description(): void
    {
        Sanctum::actingAs($this->makeUser());

        $option = Option::query()->create([
            'name' => 'Sauce',
            'title' => 'Pick sauce',
            'description' => 'Original sauce copy.',
        ]);

        $response = $this->putJson("/api/options/{$option->id}", [
            'name' => 'Sauces',
            'title' => 'Choose sauces',
            'description' => 'Updated sauce copy.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('name', 'Sauces')
            ->assertJsonPath('title', 'Choose sauces')
            ->assertJsonPath('description', 'Updated sauce copy.');

        $this->assertDatabaseHas('options', [
            'id' => $option->id,
            'name' => 'Sauces',
            'title' => 'Choose sauces',
            'description' => 'Updated sauce copy.',
        ]);
    }

    public function test_option_api_create_attaches_primary_media_from_media_id(): void
    {
        Sanctum::actingAs($this->makeUser());

        $media = $this->makeMedia('option-protein.jpg');

        $response = $this->postJson('/api/options', [
            'name' => 'Protein',
            'title' => 'Choose protein',
            'description' => 'Select your protein.',
            'media_id' => $media->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Protein')
            ->assertJsonPath('primary_media.id', $media->id)
            ->assertJsonPath('primary_media.basename', 'option-protein.jpg')
            ->assertJsonPath('primary_image_url', fn (?string $url) => filled($url))
            ->assertJsonMissingPath('media');

        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => Option::class,
            'mediable_id' => $response->json('id'),
            'tag' => Option::IMAGE_TAG,
        ]);
    }

    public function test_option_api_update_attaches_primary_media_from_media_id(): void
    {
        Sanctum::actingAs($this->makeUser());

        $option = Option::query()->create([
            'name' => 'Sauce',
            'title' => 'Pick sauce',
            'description' => 'Original sauce copy.',
        ]);
        $media = $this->makeMedia('option-sauce.jpg');

        $this->putJson("/api/options/{$option->id}", [
            'name' => 'Sauces',
            'media_id' => $media->id,
        ])
            ->assertOk()
            ->assertJsonPath('primary_media.id', $media->id)
            ->assertJsonPath('primary_media.basename', 'option-sauce.jpg')
            ->assertJsonMissingPath('media');

        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => Option::class,
            'mediable_id' => $option->id,
            'tag' => Option::IMAGE_TAG,
        ]);
    }

    public function test_option_api_read_calls_include_primary_media(): void
    {
        Sanctum::actingAs($this->makeUser());

        $option = Option::query()->create([
            'name' => 'Temperature',
            'title' => 'Choose temperature',
            'description' => 'How should it be cooked?',
        ]);
        $media = $this->makeMedia('option-temperature.jpg');
        $option->syncMedia($media, Option::IMAGE_TAG);

        $this->getJson('/api/options')
            ->assertOk()
            ->assertJsonPath('0.primary_media.id', $media->id)
            ->assertJsonMissingPath('0.media');

        $this->getJson("/api/options/{$option->id}")
            ->assertOk()
            ->assertJsonPath('primary_media.id', $media->id)
            ->assertJsonPath('primary_media.basename', 'option-temperature.jpg')
            ->assertJsonMissingPath('media');
    }

    public function test_option_api_create_attaches_primary_media_to_nested_option_values(): void
    {
        Sanctum::actingAs($this->makeUser());

        $media = $this->makeMedia('option-value-large.jpg');

        $response = $this->postJson('/api/options', [
            'name' => 'Size',
            'values' => [
                [
                    'name' => 'Large',
                    'price' => 2.00,
                    'media_id' => $media->id,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('option_values.0.primary_media.id', $media->id)
            ->assertJsonPath('option_values.0.primary_media.basename', 'option-value-large.jpg')
            ->assertJsonPath('option_values.0.primary_image_url', fn (?string $url) => filled($url))
            ->assertJsonMissingPath('option_values.0.media');

        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => OptionValue::class,
            'mediable_id' => $response->json('option_values.0.id'),
            'tag' => OptionValue::IMAGE_TAG,
        ]);
    }

    public function test_option_value_api_create_update_and_read_attach_primary_media(): void
    {
        Sanctum::actingAs($this->makeUser());

        $option = Option::query()->create(['name' => 'Protein']);
        $firstMedia = $this->makeMedia('option-value-chicken.jpg');
        $secondMedia = $this->makeMedia('option-value-steak.jpg');

        $response = $this->postJson('/api/option-values', [
            'option_id' => $option->id,
            'name' => 'Chicken',
            'price' => 3.00,
            'media_id' => $firstMedia->id,
        ])
            ->assertCreated()
            ->assertJsonPath('primary_media.id', $firstMedia->id)
            ->assertJsonPath('primary_media.basename', 'option-value-chicken.jpg')
            ->assertJsonMissingPath('media');

        $optionValueId = $response->json('id');

        $this->putJson("/api/option-values/{$optionValueId}", [
            'name' => 'Steak',
            'media_id' => $secondMedia->id,
        ])
            ->assertOk()
            ->assertJsonPath('primary_media.id', $secondMedia->id)
            ->assertJsonPath('primary_media.basename', 'option-value-steak.jpg')
            ->assertJsonMissingPath('media');

        $this->assertDatabaseMissing('mediables', [
            'media_id' => $firstMedia->id,
            'mediable_type' => OptionValue::class,
            'mediable_id' => $optionValueId,
            'tag' => OptionValue::IMAGE_TAG,
        ]);

        $this->assertDatabaseHas('mediables', [
            'media_id' => $secondMedia->id,
            'mediable_type' => OptionValue::class,
            'mediable_id' => $optionValueId,
            'tag' => OptionValue::IMAGE_TAG,
        ]);

        $this->getJson('/api/option-values?option_id='.$option->id)
            ->assertOk()
            ->assertJsonPath('0.primary_media.id', $secondMedia->id)
            ->assertJsonMissingPath('0.media');

        $this->getJson("/api/option-values/{$optionValueId}")
            ->assertOk()
            ->assertJsonPath('primary_media.id', $secondMedia->id)
            ->assertJsonMissingPath('media');
    }

    private function makeMedia(string $name): Media
    {
        return MediaUploader::fromSource(UploadedFile::fake()->image($name, 200, 100))
            ->toDisk('public')
            ->toDirectory('media-library')
            ->onDuplicateIncrement()
            ->upload();
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'firstname' => 'API',
            'lastname' => 'User',
            'username' => 'api-user',
            'email' => 'api-user@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }
}
