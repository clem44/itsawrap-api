<?php

namespace Tests\Feature;

use App\Models\Slide;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;
use Tests\TestCase;

class HeroSliderFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['mediable.image_optimization.enabled' => false]);
    }

    public function test_guest_can_fetch_an_active_slider_with_only_active_ordered_slides(): void
    {
        $slider = Slider::query()->create([
            'name' => 'Homepage Main Carousel',
            'slug' => 'homepage-main',
            'is_active' => true,
            'autoplay' => true,
            'autoplay_interval_ms' => 5000,
        ]);

        $laterSlide = $slider->slides()->create([
            'sort_order' => 2,
            'is_active' => true,
            'heading' => 'Second slide',
            'overlay_color' => '#102820',
            'overlay_opacity' => 35,
            'transition' => 'slide-left',
            'transition_ms' => 600,
        ]);
        $firstSlide = $slider->slides()->create([
            'sort_order' => 1,
            'is_active' => true,
            'badge' => 'Featured special',
            'heading' => 'Taste the authentic wrap',
            'body' => 'Fresh ingredients wrapped daily.',
            'text_alignment' => 'left',
            'overlay_color' => '#000000',
            'overlay_opacity' => 45,
            'transition' => 'fade',
            'transition_ms' => 500,
        ]);
        $slider->slides()->create([
            'sort_order' => 0,
            'is_active' => false,
            'heading' => 'Draft slide',
        ]);

        $media = $this->makeMedia('hero-wrap.jpg');
        $firstSlide->syncMedia($media, Slide::IMAGE_TAG);
        $firstSlide->buttons()->createMany([
            ['sort_order' => 1, 'label' => 'View Menu', 'url' => '/menu', 'style' => 'outline'],
            ['sort_order' => 0, 'label' => 'Order Now', 'url' => '/order', 'style' => 'solid'],
        ]);

        $this->getJson('/api/guest/sliders/homepage-main')
            ->assertOk()
            ->assertJsonPath('slug', 'homepage-main')
            ->assertJsonPath('autoplay_interval_ms', 5000)
            ->assertJsonCount(2, 'slides')
            ->assertJsonPath('slides.0.id', $firstSlide->id)
            ->assertJsonPath('slides.0.media.id', $media->id)
            ->assertJsonPath('slides.0.media.basename', 'hero-wrap.jpg')
            ->assertJsonPath('slides.0.buttons.0.label', 'Order Now')
            ->assertJsonPath('slides.0.buttons.1.style', 'outline')
            ->assertJsonPath('slides.1.id', $laterSlide->id);
    }

    public function test_guest_cannot_fetch_an_inactive_or_unknown_slider(): void
    {
        Slider::query()->create([
            'name' => 'Hidden carousel',
            'slug' => 'hidden',
            'is_active' => false,
        ]);

        $this->getJson('/api/guest/sliders/hidden')->assertNotFound();
        $this->getJson('/api/guest/sliders/missing')->assertNotFound();
    }

    public function test_admin_can_replace_the_complete_slider_document(): void
    {
        $slider = Slider::query()->create([
            'name' => 'Homepage Main Carousel',
            'slug' => 'homepage-main',
            'is_active' => true,
        ]);
        $removedSlide = $slider->slides()->create([
            'sort_order' => 0,
            'is_active' => true,
            'heading' => 'Remove me',
        ]);
        $keptSlide = $slider->slides()->create([
            'sort_order' => 1,
            'is_active' => true,
            'heading' => 'Old heading',
        ]);
        $media = $this->makeMedia('selected-hero.jpg');

        $this->actingAs($this->makeAdmin())
            ->putJson(route('admin.hero-sliders.update', $slider), [
                'name' => 'Homepage heroes',
                'is_active' => true,
                'autoplay' => false,
                'autoplay_interval_ms' => 7000,
                'slides' => [[
                    'id' => $keptSlide->id,
                    'sort_order' => 0,
                    'is_active' => true,
                    'media_id' => $media->id,
                    'badge' => 'Today only',
                    'heading' => 'A better lunch',
                    'body' => 'Made fresh to order.',
                    'text_alignment' => 'center',
                    'overlay_color' => '#112233',
                    'overlay_opacity' => 40,
                    'transition' => 'fade',
                    'transition_ms' => 600,
                    'buttons' => [[
                        'sort_order' => 0,
                        'label' => 'Order now',
                        'url' => '/order',
                        'style' => 'solid',
                    ]],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Slider saved.')
            ->assertJsonPath('slider.slides.0.heading', 'A better lunch')
            ->assertJsonPath('slider.slides.0.media.id', $media->id);

        $this->assertDatabaseMissing('slides', ['id' => $removedSlide->id]);
        $this->assertDatabaseHas('sliders', [
            'id' => $slider->id,
            'name' => 'Homepage heroes',
            'autoplay' => false,
            'autoplay_interval_ms' => 7000,
        ]);
        $this->assertDatabaseHas('slide_buttons', [
            'slide_id' => $keptSlide->id,
            'label' => 'Order now',
            'url' => '/order',
        ]);
        $this->assertDatabaseHas('mediables', [
            'media_id' => $media->id,
            'mediable_type' => Slide::class,
            'mediable_id' => $keptSlide->id,
            'tag' => Slide::IMAGE_TAG,
        ]);
    }

    public function test_admin_can_open_the_builder_with_the_slider_payload_and_navigation(): void
    {
        Slider::query()->create([
            'name' => 'Homepage Main Carousel',
            'slug' => 'homepage-main',
            'is_active' => true,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.hero-sliders.index'))
            ->assertOk()
            ->assertSee('id="hero-slider-builder"', false)
            ->assertSee('Homepage Main Carousel')
            ->assertSee(route('admin.hero-sliders.index'), false)
            ->assertSee('Hero Slider');
    }

    public function test_admin_slider_save_validates_button_limit_and_safe_urls(): void
    {
        $slider = Slider::query()->create([
            'name' => 'Homepage Main Carousel',
            'slug' => 'homepage-main',
        ]);
        $media = $this->makeMedia('validation-hero.jpg');
        $buttons = collect(range(1, 3))->map(fn (int $index) => [
            'sort_order' => $index - 1,
            'label' => "Button {$index}",
            'url' => $index === 1 ? 'javascript:alert(1)' : '/safe',
            'style' => 'solid',
        ])->all();

        $this->actingAs($this->makeAdmin())
            ->putJson(route('admin.hero-sliders.update', $slider), [
                'name' => 'Homepage Main Carousel',
                'is_active' => true,
                'autoplay' => true,
                'autoplay_interval_ms' => 5000,
                'slides' => [[
                    'sort_order' => 0,
                    'is_active' => true,
                    'media_id' => $media->id,
                    'heading' => 'Hero',
                    'text_alignment' => 'left',
                    'overlay_color' => '#000000',
                    'overlay_opacity' => 45,
                    'transition' => 'fade',
                    'transition_ms' => 500,
                    'buttons' => $buttons,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slides.0.buttons', 'slides.0.buttons.0.url']);
    }

    private function makeMedia(string $name): Media
    {
        return MediaUploader::fromSource(UploadedFile::fake()->image($name, 1400, 788))
            ->toDisk('public')
            ->toDirectory('media-library')
            ->onDuplicateIncrement()
            ->upload();
    }

    private function makeAdmin(): User
    {
        return User::query()->create([
            'firstname' => 'Slider',
            'lastname' => 'Admin',
            'username' => 'slider-admin',
            'email' => 'slider-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }
}
