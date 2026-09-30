<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    public function run(): void
    {
        Slider::query()->firstOrCreate(
            ['slug' => 'homepage-main'],
            [
                'name' => 'Homepage Main Carousel',
                'is_active' => true,
                'autoplay' => true,
                'autoplay_interval_ms' => 5000,
            ]
        );
    }
}
