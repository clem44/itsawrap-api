<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Status;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminItemPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_items_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin('items-admin');
        $category = Category::query()->create(['name' => 'Wraps']);

        foreach (range(1, 16) as $index) {
            Item::query()->create([
                'name' => sprintf('Wrap %02d', $index),
                'category_id' => $category->id,
                'cost' => 12.50,
                'active' => true,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.items.index'))
            ->assertOk()
            ->assertSee('class="admin-pagination"', false)
            ->assertSee('Showing', false)
            ->assertSee('1-15', false)
            ->assertSee('of', false)
            ->assertSee('16', false)
            ->assertSee('Previous', false)
            ->assertSee('Next', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_admin_orders_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin('orders-admin');
        $status = Status::query()->create(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Pagination Customer']);

        foreach (range(1, 16) as $index) {
            Order::query()->create([
                'number' => sprintf('ORD-%03d', $index),
                'customer_id' => $customer->id,
                'status_id' => $status->id,
                'subtotal' => 12.50,
                'total' => 12.50,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('class="admin-pagination"', false)
            ->assertSee('Showing', false)
            ->assertSee('1-15', false)
            ->assertSee('of', false)
            ->assertSee('16', false)
            ->assertSee('Previous', false)
            ->assertSee('Next', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_admin_sessions_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin('sessions-admin');

        foreach (range(1, 16) as $index) {
            CashSession::query()->create([
                'user_id' => $admin->id,
                'opening_amount' => 100,
                'total_sales' => 0,
                'is_open' => true,
                'opened_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee('class="admin-pagination"', false)
            ->assertSee('Showing', false)
            ->assertSee('1-15', false)
            ->assertSee('of', false)
            ->assertSee('16', false)
            ->assertSee('Previous', false)
            ->assertSee('Next', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_admin_customers_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin('customers-admin');

        foreach (range(1, 16) as $index) {
            Customer::query()->create([
                'name' => sprintf('Customer %02d', $index),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('class="admin-pagination"', false)
            ->assertSee('Showing', false)
            ->assertSee('1-15', false)
            ->assertSee('of', false)
            ->assertSee('16', false)
            ->assertSee('Previous', false)
            ->assertSee('Next', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_admin_tips_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin('tips-admin');
        $order = $this->makeOrder();

        foreach (range(1, 16) as $index) {
            Tip::query()->create([
                'order_id' => $order->id,
                'amount' => $index,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.tips.index'))
            ->assertOk()
            ->assertSee('class="admin-pagination"', false)
            ->assertSee('Showing', false)
            ->assertSee('1-15', false)
            ->assertSee('of', false)
            ->assertSee('16', false)
            ->assertSee('Previous', false)
            ->assertSee('Next', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_admin_payments_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin('payments-admin');
        $order = $this->makeOrder();

        foreach (range(1, 16) as $index) {
            Payment::query()->create([
                'order_id' => $order->id,
                'amount' => $index,
                'method' => 'cash',
                'status' => 'paid',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('class="admin-pagination"', false)
            ->assertSee('Showing', false)
            ->assertSee('1-15', false)
            ->assertSee('of', false)
            ->assertSee('16', false)
            ->assertSee('Previous', false)
            ->assertSee('Next', false)
            ->assertSee('aria-current="page"', false);
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

    private function makeOrder(): Order
    {
        $status = Status::query()->firstOrCreate(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Pagination Customer']);

        return Order::query()->create([
            'number' => 'ORD-PAGINATION',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'total' => 12.50,
        ]);
    }
}
