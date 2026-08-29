<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ReferralProgram;
use Illuminate\Database\Seeder;

class ReferralProgramSeeder extends Seeder
{
    public function run(): void
    {
        $wraps = Category::query()->where('name', 'Wraps')->first();

        if ($wraps === null) {
            return;
        }

        ReferralProgram::query()->firstOrCreate(
            ['name' => 'Referral Loyalty'],
            [
                'is_active' => true,
                'required_referrals' => 5,
                'reward_category_id' => $wraps->id,
                'reward_quantity' => 1,
            ]
        );
    }
}
