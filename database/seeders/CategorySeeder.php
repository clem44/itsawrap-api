<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Wraps', 'sort_order' => 1],
            ['name' => 'Bowls', 'sort_order' => 2],
            ['name' => 'Salads', 'sort_order' => 3],
            ['name' => 'Sides', 'sort_order' => 4],
            ['name' => 'Sauces', 'sort_order' => 5],
            ['name' => 'Burgers', 'sort_order' => 6],
            ['name' => 'Merchandise', 'sort_order' => 7],
            ['name' => 'Other', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
