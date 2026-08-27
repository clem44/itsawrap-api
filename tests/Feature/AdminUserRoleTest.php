<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

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
