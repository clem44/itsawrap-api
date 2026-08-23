<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\RewardLedgerEntry;
use App\Models\RewardProgram;
use App\Models\Setting;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_show_page_displays_status_update_form(): void
    {
        $admin = $this->makeAdmin();
        $pending = Status::query()->create(['name' => 'pending']);
        $completed = Status::query()->create(['name' => 'completed']);
        $order = $this->makeOrder($pending);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(route('admin.orders.update', $order), false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Pending')
            ->assertSee('Completed')
            ->assertSee('value="'.$completed->id.'"', false);
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->makeAdmin();
        $pending = Status::query()->create(['name' => 'pending']);
        $completed = Status::query()->create(['name' => 'completed']);
        $order = $this->makeOrder($pending);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status_id' => $completed->id,
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success', 'Order status updated successfully.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status_id' => $completed->id,
        ]);
    }

    public function test_admin_order_status_update_requires_existing_status(): void
    {
        $admin = $this->makeAdmin();
        $pending = Status::query()->create(['name' => 'pending']);
        $order = $this->makeOrder($pending);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->put(route('admin.orders.update', $order), [
                'status_id' => 999,
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('status_id');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status_id' => $pending->id,
        ]);
    }

    public function test_admin_order_status_update_to_active_records_reward_progress(): void
    {
        $admin = $this->makeAdmin();
        Setting::query()->create(['key' => 'rewards_enabled', 'value' => 'true']);

        $category = Category::query()->create(['name' => 'Wraps']);
        $wrap = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50]);
        $customer = Customer::query()->create(['name' => 'Rewards Customer']);
        $program = RewardProgram::query()->create([
            'name' => 'Buy 6 Wraps, Get 1 Free Wrap',
            'earn_category_id' => $category->id,
            'qualifying_item_quantity_required' => 6,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
            'is_active' => true,
        ]);
        $pending = Status::query()->create(['name' => 'pending']);
        $active = Status::query()->create(['name' => 'active']);
        $order = $this->makeOrder($pending, $customer);

        $order->orderItems()->create([
            'item_id' => $wrap->id,
            'price' => 12.50,
            'quantity' => 6,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status_id' => $active->id,
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('reward_ledger_entries', [
            'customer_id' => $customer->id,
            'reward_program_id' => $program->id,
            'order_id' => $order->id,
            'type' => RewardLedgerEntry::TYPE_EARNED_PROGRESS,
            'progress_delta' => 6,
        ]);
        $this->assertDatabaseHas('customer_reward_accounts', [
            'customer_id' => $customer->id,
            'reward_program_id' => $program->id,
            'progress_quantity' => 0,
            'rewards_available' => 1,
            'lifetime_qualifying_quantity' => 6,
            'lifetime_rewards_earned' => 1,
        ]);
    }

    private function makeOrder(Status $status, ?Customer $customer = null): Order
    {
        return Order::query()->create([
            'number' => 'ORD-001',
            'customer_id' => $customer?->id,
            'status_id' => $status->id,
            'subtotal' => 25,
            'service_charge' => 0,
            'total' => 25,
        ]);
    }

    private function makeAdmin(): User
    {
        return User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }
}
