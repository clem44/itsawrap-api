<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_update_their_name_and_phone(): void
    {
        $user = $this->makeCustomerUser();
        $customer = Customer::query()->create([
            'user_id' => $user->id,
            'name' => 'Alex Carter',
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'phone' => '555-0100',
            'email' => $user->email,
            'source' => 'web-customer',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/me/profile', [
            'firstname' => 'Jordan',
            'lastname' => 'Baptiste',
            'phone' => '264-555-0199',
        ])->assertOk()
            ->assertExactJson([
                'firstname' => 'Jordan',
                'lastname' => 'Baptiste',
                'phone' => '264-555-0199',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'firstname' => 'Jordan',
            'lastname' => 'Baptiste',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Jordan Baptiste',
            'firstname' => 'Jordan',
            'lastname' => 'Baptiste',
            'phone' => '264-555-0199',
        ]);
    }

    public function test_a_customer_can_clear_their_phone_without_resending_their_name(): void
    {
        $user = $this->makeCustomerUser();
        Customer::query()->create([
            'user_id' => $user->id,
            'name' => 'Alex Carter',
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'phone' => '555-0100',
            'email' => $user->email,
            'source' => 'web-customer',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/me/profile', ['phone' => null])
            ->assertOk()
            ->assertExactJson([
                'firstname' => 'Alex',
                'lastname' => 'Carter',
                'phone' => null,
            ]);

        $this->assertDatabaseHas('customers', [
            'user_id' => $user->id,
            'phone' => null,
        ]);
    }

    public function test_a_customer_can_change_their_password_when_the_current_password_is_correct(): void
    {
        $user = $this->makeCustomerUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/me/password', [
            'current_password' => 'password123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertOk()
            ->assertExactJson(['message' => 'Password updated successfully.']);

        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_a_customer_cannot_change_their_password_with_the_wrong_current_password(): void
    {
        $user = $this->makeCustomerUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/me/password', [
            'current_password' => 'not-the-current-password',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_a_customer_password_must_be_confirmed_and_at_least_eight_characters(): void
    {
        Sanctum::actingAs($this->makeCustomerUser());

        $this->putJson('/api/me/password', [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_staff_cannot_update_a_customer_self_service_profile_or_password(): void
    {
        $staff = User::query()->create([
            'firstname' => 'Staff',
            'lastname' => 'User',
            'username' => 'staff@example.com',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'role_id' => Role::ADMIN_ID,
        ]);
        Sanctum::actingAs($staff);

        $this->patchJson('/api/me/profile', ['firstname' => 'Nope'])->assertForbidden();
        $this->putJson('/api/me/password', [
            'current_password' => 'password123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertForbidden();
    }

    private function makeCustomerUser(): User
    {
        return User::query()->create([
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'username' => 'alex@example.com',
            'email' => 'alex@example.com',
            'password' => 'password123',
            'role_id' => Role::CUSTOMER_ID,
        ]);
    }
}
