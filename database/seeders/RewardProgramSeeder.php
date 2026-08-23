<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\RewardProgram;
use Illuminate\Database\Seeder;

class RewardProgramSeeder extends Seeder
{
    public function run(): void
    {
        $wraps = Category::query()->where('name', 'Wraps')->first();

        if ($wraps === null) {
            return;
        }

        RewardProgram::query()->firstOrCreate(
            ['name' => 'Buy 6 Wraps, Get 1 Free Wrap'],
            [
                'is_active' => true,
                'earn_category_id' => $wraps->id,
                'qualifying_item_quantity_required' => 6,
                'reward_category_id' => $wraps->id,
                'reward_quantity' => 1,
            ]
        );
    }
}
