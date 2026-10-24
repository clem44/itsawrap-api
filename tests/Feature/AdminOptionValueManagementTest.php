<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOptionValueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_index_exposes_chip_and_modal_delete_controls_for_option_values(): void
    {
        $option = Option::query()->create(['name' => 'Protein']);
        $optionValue = OptionValue::query()->create([
            'option_id' => $option->id,
            'name' => 'Chicken',
            'price' => 3,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.options.index'))
            ->assertOk()
            ->assertSee(route('admin.option-values.destroy', [$option, $optionValue]), false)
            ->assertSee('aria-label="Delete Chicken"', false)
            ->assertSee(':delete-action="getDeleteValueAction()"', false);
    }

    public function test_admin_can_delete_an_option_value(): void
    {
        $option = Option::query()->create(['name' => 'Protein']);
        $optionValue = OptionValue::query()->create([
            'option_id' => $option->id,
            'name' => 'Chicken',
            'price' => 3,
        ]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.option-values.destroy', [$option, $optionValue]))
            ->assertRedirect(route('admin.options.index'))
            ->assertSessionHas('success', 'Option value deleted successfully.');

        $this->assertDatabaseMissing('option_values', ['id' => $optionValue->id]);
    }

    private function makeAdmin(): User
    {
        return User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'option-admin',
            'email' => 'option-admin@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }
}
