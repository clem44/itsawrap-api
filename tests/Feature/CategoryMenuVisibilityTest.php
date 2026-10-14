<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryMenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_table_has_a_menu_visibility_column_that_defaults_to_true(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'menu_visibility'));

        $category = Category::query()->create(['name' => 'Wraps']);

        $this->assertTrue($category->refresh()->menu_visibility);
    }

    public function test_staff_category_api_accepts_and_returns_menu_visibility(): void
    {
        Sanctum::actingAs($this->makeUser(roleId: 2, username: 'category-menu-api'));

        $response = $this->postJson('/api/categories', [
            'name' => 'Seasonal',
            'menu_visibility' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('menu_visibility', false);

        $categoryId = $response->json('id');

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('0.menu_visibility', false);

        $this->getJson('/api/categories/'.$categoryId)
            ->assertOk()
            ->assertJsonPath('menu_visibility', false);

        $this->putJson('/api/categories/'.$categoryId, [
            'menu_visibility' => true,
        ])
            ->assertOk()
            ->assertJsonPath('menu_visibility', true);
    }

    public function test_admin_can_edit_menu_visibility_from_the_category_modal(): void
    {
        $admin = $this->makeUser(roleId: 1, username: 'category-menu-admin');
        $category = Category::query()->create([
            'name' => 'Wraps',
            'menu_visibility' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('id="edit_category_menu_visibility"', false)
            ->assertSee('Menu Visibility');

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Wraps',
                'menu_visibility' => '0',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertFalse($category->refresh()->menu_visibility);
    }

    private function makeUser(int $roleId, string $username): User
    {
        return User::query()->create([
            'firstname' => 'Category',
            'lastname' => 'Tester',
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
        ]);
    }
}
