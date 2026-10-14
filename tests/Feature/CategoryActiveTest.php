<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_table_has_an_active_column_that_defaults_to_true(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'active'));

        $category = Category::query()->create(['name' => 'Wraps']);

        $this->assertTrue($category->refresh()->active);
    }

    public function test_admin_can_mark_a_category_as_inactive(): void
    {
        $admin = User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'category-active-admin',
            'email' => 'category-active-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
        $category = Category::query()->create(['name' => 'Wraps']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Wraps',
                'description' => null,
                'icon' => null,
                'color' => null,
                'sort_order' => 0,
                'active' => '0',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'active' => false,
        ]);
    }

    public function test_category_edit_modal_contains_the_active_input(): void
    {
        $admin = User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'category-modal-admin',
            'email' => 'category-modal-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('id="edit_category_active"', false)
            ->assertSee('Active Category');
    }
}
