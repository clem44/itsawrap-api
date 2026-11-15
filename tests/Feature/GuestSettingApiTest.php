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

    public function test_guest_can_read_a_public_setting_value(): void
    {
        Setting::setValue('service_charge', '0.15');

        $this->getJson('/api/guest/settings/service_charge')
            ->assertOk()
            ->assertExactJson(['value' => '0.15']);
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
