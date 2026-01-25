<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $items = [
            ['name' => 'Garlic Pesto Wrap'],
            ['name' => 'Gluten-Free Spinach Wrap'],
            ['name' => 'Whole Wheat Wrap'],
            ['name' => 'Caesar Salad'],
            ['name' => 'Greek Salad'],
            ['name' => 'Garden Salad'],
            ['name' => 'Creamy Chicken Bowl','cost'=>14.00],
            ['name' => 'Encrusted Tuna Steak Bowl','cost'=>18.00],
            ['name' => 'Savory Steak Bowl','cost'=>20.00],
            ['name' => 'Tofu Veggie Bowl'],
            ['name' => 'Spinach Wrap'],
            ['name' => 'Grilled Tofu Bowl','cost'=>12.00],
            ['name' => 'Sundried Tomatoes Wrap'],
            ['name' => 'Classic Wrap'],
            ['name' => 'Cheese Burger'],
            ['name' => 'Sliders','cost'=>8.00],
            ['name' => 'Caesar Salad','cost'=>8.00],
            ['name' => 'Garden Salad','cost'=>8.00],
            ['name' => 'Chicken','cost'=>6.00],
            ['name' => 'Steak','cost'=>9.00],
            ['name' => 'Tofu','cost'=>5.00],
            ['name' => 'Tuna','cost'=>8.00],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
