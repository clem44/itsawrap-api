<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_status_index_renders_modal_workflow_and_actions(): void
    {
        $admin = $this->makeAdmin();
        $status = Status::query()->create([
            'name' => 'pending',
            'description' => 'Order is pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.statuses.index'))
            ->assertOk()
            ->assertSee('Status Management')
            ->assertSee('New Status')
            ->assertSee('openCreate()', false)
            ->assertSee('openEdit', false)
            ->assertSee(route('admin.statuses.update', $status), false)
            ->assertSee(route('admin.statuses.destroy', $status), false);
    }

    public function test_admin_can_create_update_and_delete_unused_status(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.statuses.store'), [
                'name' => 'ready',
                'description' => 'Order is ready for pickup',
            ])
            ->assertRedirect(route('admin.statuses.index'));

        $status = Status::query()->where('name', 'ready')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.statuses.update', $status), [
                'name' => 'ready_for_pickup',
                'description' => 'Order is ready',
            ])
            ->assertRedirect(route('admin.statuses.index'));

        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'name' => 'ready_for_pickup',
            'description' => 'Order is ready',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.statuses.destroy', $status))
            ->assertRedirect(route('admin.statuses.index'));

        $this->assertDatabaseMissing('statuses', [
            'id' => $status->id,
        ]);
    }

    public function test_admin_cannot_delete_status_that_is_assigned_to_orders(): void
    {
        $admin = $this->makeAdmin();
        $status = Status::query()->create(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Status Customer']);

        Order::query()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'total' => 12.50,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.statuses.destroy', $status))
            ->assertRedirect(route('admin.statuses.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
        ]);
    }

    public function test_admin_statuses_index_uses_admin_pagination_template(): void
    {
        $admin = $this->makeAdmin();

        foreach (range(1, 16) as $index) {
            Status::query()->create([
                'name' => sprintf('status_%02d', $index),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.statuses.index'))
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

    private function makeAdmin(): User
    {
        return User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'status-admin',
            'email' => 'status-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }
}
