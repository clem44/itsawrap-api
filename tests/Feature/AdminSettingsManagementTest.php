<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_index(): void
    {
        $setting = Setting::query()->create(['key' => 'store_name', 'value' => "It's A Wrap"]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Application Settings')
            ->assertSee($setting->key)
            ->assertSee($setting->value)
            ->assertSee('New Setting');
    }

    public function test_admin_can_create_a_setting(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.settings.store'), [
                'key' => 'receipt_footer',
                'value' => 'Thank you for visiting.',
                'form_action' => 'create',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'key' => 'receipt_footer',
            'value' => 'Thank you for visiting.',
        ]);
    }

    public function test_admin_can_update_a_setting(): void
    {
        $setting = Setting::query()->create(['key' => 'store_name', 'value' => 'Old Name']);

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.update', $setting), [
                'key' => 'store_name',
                'value' => "It's A Wrap AXA",
                'form_action' => 'edit',
                'edit_id' => $setting->id,
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'key' => 'store_name',
            'value' => "It's A Wrap AXA",
        ]);
    }

    public function test_setting_keys_must_be_unique(): void
    {
        Setting::query()->create(['key' => 'store_name', 'value' => 'Existing']);

        $this->actingAs($this->makeAdmin())
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.store'), [
                'key' => 'store_name',
                'value' => 'Duplicate',
                'form_action' => 'create',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasErrors(['key']);

        $this->assertDatabaseCount('settings', 1);
    }

    private function makeAdmin(): User
    {
        return User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'settings-admin',
            'email' => 'settings-admin@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }
}
