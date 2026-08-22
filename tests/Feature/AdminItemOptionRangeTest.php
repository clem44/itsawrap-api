<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemOptionRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_item_option_range_bounds(): void
    {
        [$admin, $item, $itemOption] = $this->makeItemOption();

        $response = $this
            ->actingAs($admin)
            ->patchJson(route('admin.items.item-options.update-qty', [$item, $itemOption]), [
                'enable_qty' => true,
                'range' => true,
                'min' => 1,
                'max' => 3,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('range', true)
            ->assertJsonPath('min', 1)
            ->assertJsonPath('max', 3);

        $this->assertDatabaseHas('item_options', [
            'id' => $itemOption->id,
            'range' => true,
            'min' => 1,
            'max' => 3,
        ]);
    }

    public function test_item_option_range_max_must_not_be_less_than_min(): void
    {
        [$admin, $item, $itemOption] = $this->makeItemOption();

        $response = $this
            ->actingAs($admin)
            ->patchJson(route('admin.items.item-options.update-qty', [$item, $itemOption]), [
                'range' => true,
                'min' => 4,
                'max' => 2,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max']);

        $this->assertDatabaseHas('item_options', [
            'id' => $itemOption->id,
            'min' => null,
            'max' => null,
        ]);
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Item, 2: \App\Models\ItemOption}
     */
    private function makeItemOption(): array
    {
        $admin = User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);

        $category = Category::query()->create([
            'name' => 'Wraps',
            'sort_order' => 1,
        ]);

        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'category_id' => $category->id,
            'cost' => 12.50,
            'active' => true,
        ]);

        $option = Option::query()->create([
            'name' => 'Sauce',
        ]);

        $itemOption = ItemOption::query()->create([
            'item_id' => $item->id,
            'option_id' => $option->id,
            'required' => false,
            'range' => false,
            'min' => null,
            'max' => null,
        ]);

        return [$admin, $item, $itemOption];
    }
}

