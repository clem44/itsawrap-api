<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_edit_page_renders_editable_role_inputs(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'admin-role-editor');
        $user = $this->makeUser(Role::STAFF_ID, 'staff-to-edit');

        $response = $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk();

        $response->assertSee('Role & Permissions', false);
        $response->assertSee('id="role_'.Role::DRIVER_ID.'"', false);
        $response->assertSee('name="role_id"', false);
        $response->assertDontSee('id="role_'.Role::DRIVER_ID.'" type="radio" name="role_id" value="'.Role::DRIVER_ID.'" disabled', false);
    }

    public function test_admin_can_change_a_users_role_from_the_edit_form(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'admin-role-updater');
        $user = $this->makeUser(Role::STAFF_ID, 'staff-role-updated');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'email' => $user->email,
                'role_id' => Role::DRIVER_ID,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(Role::DRIVER_ID, $user->refresh()->role_id);
    }

    private function makeUser(int $roleId, string $username): User
    {
        return User::query()->create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => 'password',
            'role_id' => $roleId,
        ]);
    }
}
