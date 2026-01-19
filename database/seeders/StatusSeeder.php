<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'pending', 'description' => 'Order is pending'],
            ['name' => 'active', 'description' => 'Order is being processed'],
            ['name' => 'completed', 'description' => 'Order is completed'],
            ['name' => 'cancelled', 'description' => 'Order was cancelled'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate(['name' => $status['name']], $status);
        }
    }
}
