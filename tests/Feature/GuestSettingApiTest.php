<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuestSettingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_public_setting_values(): void
    {
        $publicSettings = [
            'service_charge' => '0.15',
            'delivery_fee' => '3.50',
            'open_time' => '11:00:00',
            'close_time' => '21:30:00',
            'business_phone' => '264 235 5433',
            'business_address' => 'The Dune Preserve, Anguilla',
            'currency' => 'XCD',
            'selected_branch_id' => '2',
        ];

        foreach ($publicSettings as $key => $value) {
            Setting::setValue($key, $value);

            $this->getJson("/api/guest/settings/{$key}")
                ->assertOk()
                ->assertExactJson(['value' => $value]);
        }
    }

    public function test_guest_cannot_read_a_setting_that_is_not_public(): void
    {
        Setting::setValue('payment_gateway_secret', 'do-not-expose');

        $this->getJson('/api/guest/settings/payment_gateway_secret')
            ->assertNotFound();
    }

    public function test_customer_cannot_read_the_staff_settings_collection(): void
    {
        $customer = User::query()->create([
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'username' => 'alex@example.com',
            'email' => 'alex@example.com',
            'password' => 'password',
            'role_id' => Role::CUSTOMER_ID,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/settings')->assertForbidden();
    }
}
