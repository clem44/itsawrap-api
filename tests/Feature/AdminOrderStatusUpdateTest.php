<?php

namespace Tests\Feature;

use App\Models\Order;
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

    private function makeOrder(Status $status): Order
    {
        return Order::query()->create([
            'number' => 'ORD-001',
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
