<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'service_charge', 'value' => '0'],
            ['key' => 'currency', 'value' => 'USD'],
            ['key' => 'currency_symbol', 'value' => '$'],
            ['key' => 'business_name', 'value' => 'It\'s A Wrap'],
            ['key' => 'business_address', 'value' => ''],
            ['key' => 'business_phone', 'value' => ''],
            ['key' => 'receipt_footer', 'value' => 'Thank you for your order!'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
