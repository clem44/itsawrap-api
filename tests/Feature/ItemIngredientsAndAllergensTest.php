<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItemIngredientsAndAllergensTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_table_has_ingredients_and_allergens_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('items', ['ingredients', 'allergens']));
    }

    public function test_item_can_store_ingredients_and_allergens(): void
    {
        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'ingredients' => 'Chicken, lettuce, tomato, garlic sauce',
            'allergens' => 'Milk, wheat',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'ingredients' => 'Chicken, lettuce, tomato, garlic sauce',
            'allergens' => 'Milk, wheat',
        ]);
    }

    public function test_admin_can_update_item_ingredients_and_allergens(): void
    {
        $admin = User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'item-fields-admin',
            'email' => 'item-fields-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
        $category = Category::query()->create(['name' => 'Wraps']);
        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'category_id' => $category->id,
            'cost' => 12.50,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.items.update', $item), [
                'name' => 'Chicken Wrap',
                'description' => 'A grilled chicken wrap.',
                'ingredients' => 'Chicken, lettuce, tomato, garlic sauce',
                'allergens' => 'Milk, wheat',
                'cost' => '12.50',
                'category_id' => $category->id,
                'active' => 'on',
                'options' => [],
            ])
            ->assertRedirect(route('admin.items.index'));

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'ingredients' => 'Chicken, lettuce, tomato, garlic sauce',
            'allergens' => 'Milk, wheat',
        ]);
    }
}
