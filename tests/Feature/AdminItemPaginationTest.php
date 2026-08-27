<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminItemPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_items_index_uses_admin_pagination_template(): void
    {
        $admin = User::query()->create([
            'firstname' => 'Items',
            'lastname' => 'Admin',
            'username' => 'items-admin',
            'email' => 'items-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
        $category = Category::query()->create(['name' => 'Wraps']);

        foreach (range(1, 16) as $index) {
            Item::query()->create([
                'name' => sprintf('Wrap %02d', $index),
                'category_id' => $category->id,
                'cost' => 12.50,
                'active' => true,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.items.index'))
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
}
