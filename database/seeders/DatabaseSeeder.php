<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OpenApi\Attributes\Items;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StatusSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            SettingSeeder::class,
            OptionSeeder::class,
            ItemsSeeder::class,
            BranchSeeder::class,
        ]);
    }
}
