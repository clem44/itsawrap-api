<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\OrderParticipant;
use App\Models\RewardProgram;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_registration_creates_a_customer_user_and_linked_customer_with_a_token(): void
    {
        $response = $this->postJson('/api/guest/register', [
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'email' => 'alex@example.com',
            'phone' => '555-0100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'itsawrapweb',
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->json('token'));

        $this->assertDatabaseHas('users', [
            'email' => 'alex@example.com',
            'username' => 'alex@example.com',
            'role_id' => 3,
        ]);

        $userId = User::query()->where('email', 'alex@example.com')->value('id');

        $this->assertDatabaseHas('customers', [
            'user_id' => $userId,
            'email' => 'alex@example.com',
            'source' => 'web-customer',
        ]);
    }

    public function test_me_rewards_returns_the_authenticated_customers_own_summary(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);

        RewardProgram::query()->create([
            'name' => 'Buy 6 Get 1 Free',
            'is_active' => true,
            'earn_category_id' => $category->id,
            'qualifying_item_quantity_required' => 6,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
        ]);

        $user = $this->makeCustomerUser();
        Customer::query()->create(['user_id' => $user->id, 'name' => 'Alex Carter', 'source' => 'web-customer']);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/rewards')
            ->assertOk()
            ->assertJsonPath('rewards_enabled', true)
            ->assertJsonPath('programs.0.name', 'Buy 6 Get 1 Free')
            ->assertJsonPath('programs.0.progress_quantity', 0);
    }

    public function test_me_orders_reuses_the_same_customer_record_across_multiple_orders(): void
    {
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);

        $user = $this->makeCustomerUser();
        Sanctum::actingAs($user);

        $payload = [
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ];

        $this->postJson('/api/me/orders', $payload, ['Idempotency-Key' => 'me-order-1'])->assertCreated();
        $this->postJson('/api/me/orders', $payload, ['Idempotency-Key' => 'me-order-2'])->assertCreated();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_an_order_placed_without_a_number_gets_one_generated_by_the_api(): void
    {
        // itsawrapweb's authenticated checkout path never generates a client-side
        // number (unlike the guest path), so the API must fall back to one itself
        // rather than saving the order with number = null.
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);

        Sanctum::actingAs($this->makeCustomerUser());

        $response = $this->postJson('/api/me/orders', [
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated();
        $number = $response->json('number');

        $this->assertNotNull($number);
        $this->assertNotSame('', $number);
        $this->assertDatabaseHas('orders', ['id' => $response->json('id'), 'number' => $number]);
    }

    public function test_me_orders_store_group_participants_on_the_authenticated_customers_order(): void
    {
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $wrap = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $bowl = Item::query()->create(['name' => 'Rice Bowl', 'category_id' => $category->id, 'cost' => 9.00, 'active' => true]);

        Sanctum::actingAs($this->makeCustomerUser());

        $response = $this->postJson('/api/me/orders', [
            'subtotal' => 21.50,
            'service_charge' => 0,
            'total' => 21.50,
            'is_delivery' => false,
            'participants' => [
                ['client_id' => 'person-0', 'name' => 'Alex Carter', 'is_primary' => true],
                ['client_id' => 'person-1', 'name' => 'Jamie'],
            ],
            'items' => [
                [
                    'item_id' => $wrap->id,
                    'price' => 12.50,
                    'quantity' => 1,
                    'participant_client_id' => 'person-0',
                ],
                [
                    'item_id' => $bowl->id,
                    'price' => 9.00,
                    'quantity' => 1,
                    'participant_client_id' => 'person-1',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('participants.0.name', 'Alex Carter')
            ->assertJsonPath('participants.0.is_primary', true)
            ->assertJsonPath('participants.1.name', 'Jamie')
            ->assertJsonPath('order_items.0.participant.name', 'Alex Carter')
            ->assertJsonPath('order_items.1.participant.name', 'Jamie');

        $this->assertDatabaseCount('customers', 1);

        $alex = OrderParticipant::query()
            ->where('order_id', $response->json('id'))
            ->where('client_id', 'person-0')
            ->firstOrFail();

        $this->assertTrue($alex->is_primary);
        $this->assertDatabaseHas('orders', [
            'id' => $response->json('id'),
            'customer_id' => Customer::query()->value('id'),
            'source' => 'web-customer',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $response->json('id'),
            'order_participant_id' => $alex->id,
            'item_id' => $wrap->id,
        ]);
    }

    public function test_a_staff_token_cannot_access_customer_self_service_routes(): void
    {
        Sanctum::actingAs($this->makeStaffUser());

        $this->getJson('/api/me/rewards')->assertStatus(403);
    }

    public function test_a_customer_token_cannot_access_staff_only_routes(): void
    {
        Sanctum::actingAs($this->makeCustomerUser());

        $this->getJson('/api/orders')->assertStatus(403);
    }

    private function makeStaffUser(): User
    {
        return User::query()->create([
            'firstname' => 'Staff',
            'lastname' => 'User',
            'username' => 'staff-user',
            'email' => 'staff-user@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }

    private function makeCustomerUser(): User
    {
        return User::query()->create([
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'username' => 'alex@example.com',
            'email' => 'alex@example.com',
            'password' => 'password',
            'role_id' => 3,
        ]);
    }
}
