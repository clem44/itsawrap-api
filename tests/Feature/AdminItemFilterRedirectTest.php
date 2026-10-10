<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemFilterRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_item_create_preserves_current_category_filter(): void
    {
        $admin = $this->makeAdmin();
        $filterCategory = Category::query()->create(['name' => 'Wraps']);
        $itemCategory = Category::query()->create(['name' => 'Bowls']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.items.store', ['category_id' => $filterCategory->id]), [
                'name' => 'Chicken Bowl',
                'cost' => '12.50',
                'category_id' => $itemCategory->id,
            ]);

        $response->assertRedirect(route('admin.items.index', ['category_id' => $filterCategory->id]));
    }

    public function test_admin_item_update_preserves_current_category_filter(): void
    {
        $admin = $this->makeAdmin();
        $filterCategory = Category::query()->create(['name' => 'Wraps']);
        $itemCategory = Category::query()->create(['name' => 'Bowls']);
        $item = Item::query()->create([
            'name' => 'Chicken Bowl',
            'cost' => 12.50,
            'category_id' => $itemCategory->id,
            'active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.items.update', ['item' => $item, 'category_id' => $filterCategory->id]), [
                'name' => 'Chicken Bowl',
                'cost' => '13.00',
                'category_id' => $itemCategory->id,
            ]);

        $response->assertRedirect(route('admin.items.index', ['category_id' => $filterCategory->id]));
    }

    public function test_admin_item_create_does_not_preserve_empty_category_filter(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Wraps']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.items.store', ['category_id' => '']), [
                'name' => 'Chicken Wrap',
                'cost' => '12.50',
                'category_id' => $category->id,
            ]);

        $response->assertRedirect(route('admin.items.index'));
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
