<?php

namespace Database\Seeders;

use App\Models\Option;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            [
                'name' => 'Extras',
                'optionValues' => [
                    ['name' => 'Cheese', 'price' => 1.50],
                    ['name' => 'Bacon',  'price' => 1.50],
                    ['name' => 'Avocado', 'price' => 1.50],
                    ['name' => 'Cheddar', 'price' => 1.50],
                    ['name' => 'Goat Cheese', 'price' => 1.50],
                    ['name' => 'Parmesan Cheese', 'price' => 1.50],
                    ['name' => 'Caramelized Onions', 'price' => 1.50],
                ],
            ],
            [
                'name' => 'Side',
                'optionValues' => [
                    ['name' => 'Chip N Dip', 'price' => 10.00],
                    ['name' => 'Onion Rings',  'price' => 4.00],
                    ['name' => 'Seasoned Fries', 'price' => 4.00],
                    ['name' => 'Sweet Potato Fries', 'price' => 4.00],
                ],
            ],
            ['name' => 'Sauce'],
            [
                'name' => 'Protein',
                'optionValues' => [
                    ['name' => 'Chicken', 'price' => 10.00],
                    ['name' => 'Steak',  'price' => 18.00],
                    ['name' => 'Tuna Steak', 'price' => 12.00],
                    ['name' => 'Tofu', 'price' => 10.00],
                ],
            ],
            [
                'name' => 'Temperature',
                'optionValues' => [
                    ['name' => 'Rare', 'price' => 0.00],
                    ['name' => 'Medium-Rare',  'price' => 0.00],
                    ['name' => 'Medium', 'price' => 0.00],
                    ['name' => 'Medium-Well', 'price' => 0.00],
                    ['name' => 'Well-Done', 'price' => 0.00],
                ],
            ],
            ['name' => 'Size'],
            ['name' => 'Color'],
            [
                'name' => 'Free Additions',
                'optionValues' => [
                    ['name' => 'Cherry Tomatoes', 'price' => 0.00],
                    ['name' => 'Red Onions',  'price' => 0.00],
                    ['name' => 'Cucumbers', 'price' => 0.00],
                    ['name' => 'Olives', 'price' => 0.00],
                    ['name' => 'Pickles', 'price' => 0.00],
                    ['name' => 'Bell Peppers', 'price' => 0.00],
                ],
            ],
            [
                'name' => 'Preferences',
                'optionValues' => [
                    ['name' => 'No Lettuce', 'price' => 0.00],
                    ['name' => 'No Tomato',  'price' => 0.00],
                    ['name' => 'Mayo', 'price' => 0.00],
                    ['name' => 'Ketchup', 'price' => 0.00],
                    ['name' => 'Mustard', 'price' => 0.00],
                ],
            ],
        ];

        foreach ($options as $data) {
            $values = $data['optionValues'] ?? [];
            unset($data['optionValues']);

            // parent
            $option = Option::firstOrCreate(['name' => $data['name']], $data);

            // children (avoid duplicates)
            foreach ($values as $val) {
                $option->optionValues()->updateOrCreate(
                    ['name' => $val['name']],                 // unique per option (recommended)
                    ['price' => $val['price']]                // fields to update
                );
            }
        }
    }
}
