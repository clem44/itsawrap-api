<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'firstname' => 'Admin',
                'lastname' => 'User',
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => 'admin123',
                'role_id' => 1,
            ]
        );
    }
}
