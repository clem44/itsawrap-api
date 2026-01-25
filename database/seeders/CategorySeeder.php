<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Wraps', 'sort_order' => 1, 'color' => "#FC8326", 'icon' => 'wraps'],
            ['name' => 'Bowls', 'sort_order' => 2, 'color' => "#4CAF50", 'icon' => 'bowls'],
            ['name' => 'Salads', 'sort_order' => 3, 'color' => "#4CAF50", 'icon' => 'salads'],
            ['name' => 'Sides', 'sort_order' => 4, 'color' => "#4CAF50", 'icon' => 'side'],
            ['name' => 'Sauces', 'sort_order' => 5, 'color' => "#FC8326", 'icon' => 'sauce'],
            ['name' => 'Burgers', 'sort_order' => 6, 'color' => "#FC8326", 'icon' => 'burgers'],
            ['name' => 'Merchandise', 'sort_order' => 7, 'color' => "#795548", 'icon' => "merchandise"],
            ['name' => 'Other', 'sort_order' => 8, 'color' => "#607D8B"],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
