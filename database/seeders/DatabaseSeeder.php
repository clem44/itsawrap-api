<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StatusSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            SettingSeeder::class,
        ]);
    }
}
