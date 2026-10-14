<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_can_be_searched_by_contact_details(): void
    {
        $admin = $this->makeAdmin('customer-search-admin');

        Customer::query()->create([
            'name' => 'Alex Carter',
            'email' => 'alex@example.com',
            'phone' => '264-555-0101',
        ]);
        Customer::query()->create([
            'name' => 'Morgan Bailey',
            'email' => 'morgan@example.com',
            'phone' => '264-555-0202',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.index', ['search' => '555-0101']))
            ->assertOk()
            ->assertSee('Alex Carter')
            ->assertDontSee('Morgan Bailey')
            ->assertSee('Search customers')
            ->assertSee('Clear');
    }

    public function test_orders_can_be_searched_by_number_or_customer(): void
    {
        $admin = $this->makeAdmin('order-search-admin');
        $status = Status::query()->create(['name' => 'pending']);
        $alex = Customer::query()->create(['name' => 'Alex Carter', 'email' => 'alex@example.com']);
        $morgan = Customer::query()->create(['name' => 'Morgan Bailey', 'email' => 'morgan@example.com']);

        Order::query()->create([
            'number' => 'IAW-ALPHA-001',
            'customer_id' => $alex->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'total' => 12.50,
        ]);
        Order::query()->create([
            'number' => 'IAW-BRAVO-002',
            'customer_id' => $morgan->id,
            'status_id' => $status->id,
            'subtotal' => 9.75,
            'total' => 9.75,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['search' => 'alex@example.com']))
            ->assertOk()
            ->assertSee('IAW-ALPHA-001')
            ->assertDontSee('IAW-BRAVO-002')
            ->assertSee('Search orders')
            ->assertSee('Clear');
    }

    public function test_items_can_be_searched_while_scoped_to_a_category(): void
    {
        $admin = $this->makeAdmin('item-search-admin');
        $wraps = Category::query()->create(['name' => 'Wraps']);
        $drinks = Category::query()->create(['name' => 'Drinks']);

        Item::query()->create([
            'name' => 'Crispy Chicken Wrap',
            'short_code' => 'CCW',
            'category_id' => $wraps->id,
            'cost' => 12.50,
            'active' => true,
        ]);
        Item::query()->create([
            'name' => 'Crispy Chicken Bowl',
            'short_code' => 'CCB',
            'category_id' => $drinks->id,
            'cost' => 13.00,
            'active' => true,
        ]);
        Item::query()->create([
            'name' => 'Falafel Wrap',
            'short_code' => 'FW',
            'category_id' => $wraps->id,
            'cost' => 10.00,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.items.index', [
                'search' => 'Crispy',
                'category_id' => $wraps->id,
            ]))
            ->assertOk()
            ->assertSee('Crispy Chicken Wrap')
            ->assertDontSee('Crispy Chicken Bowl')
            ->assertDontSee('Falafel Wrap')
            ->assertSee('Apply filters')
            ->assertSee('Clear');
    }

    private function makeAdmin(string $username): User
    {
        return User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => $username,
            'email' => "{$username}@example.com",
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }
}
