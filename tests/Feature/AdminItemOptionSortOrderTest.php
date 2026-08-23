<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemOptionSortOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_item_create_stores_options_in_submitted_order(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        [$sauce, $extras, $protein] = $this->makeOptions();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.items.store'), [
                'name' => 'Chicken Wrap',
                'cost' => '12.50',
                'category_id' => $category->id,
                'options' => [$protein->id, $sauce->id, $extras->id],
            ]);

        $response->assertRedirect(route('admin.items.index'));

        $item = Item::query()->where('name', 'Chicken Wrap')->firstOrFail();

        $this->assertSame(
            [$protein->id, $sauce->id, $extras->id],
            $item->itemOptions()->pluck('option_id')->all()
        );

        $this->assertSame([1, 2, 3], $item->itemOptions()->pluck('sort_order')->all());
    }

    public function test_admin_item_update_stores_options_in_submitted_order(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        [$sauce, $extras, $protein] = $this->makeOptions();

        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'cost' => 12.50,
            'category_id' => $category->id,
            'active' => true,
        ]);

        ItemOption::query()->create(['item_id' => $item->id, 'option_id' => $sauce->id, 'sort_order' => 1]);
        ItemOption::query()->create(['item_id' => $item->id, 'option_id' => $extras->id, 'sort_order' => 2]);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.items.update', $item), [
                'name' => 'Chicken Wrap',
                'cost' => '12.50',
                'category_id' => $category->id,
                'options' => [$protein->id, $sauce->id, $extras->id],
            ]);

        $response->assertRedirect(route('admin.items.index'));

        $this->assertSame(
            [$protein->id, $sauce->id, $extras->id],
            $item->fresh()->itemOptions()->pluck('option_id')->all()
        );

        $this->assertSame([1, 2, 3], $item->fresh()->itemOptions()->pluck('sort_order')->all());
    }

    public function test_admin_can_reorder_existing_item_options(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        [$sauce, $extras, $protein] = $this->makeOptions();

        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'cost' => 12.50,
            'category_id' => $category->id,
            'active' => true,
        ]);

        $sauceItemOption = ItemOption::query()->create(['item_id' => $item->id, 'option_id' => $sauce->id, 'sort_order' => 1]);
        $extrasItemOption = ItemOption::query()->create(['item_id' => $item->id, 'option_id' => $extras->id, 'sort_order' => 2]);
        $proteinItemOption = ItemOption::query()->create(['item_id' => $item->id, 'option_id' => $protein->id, 'sort_order' => 3]);

        $response = $this
            ->actingAs($admin)
            ->patchJson(route('admin.items.item-options.update-order', $item), [
                'item_options' => [
                    $proteinItemOption->id,
                    $sauceItemOption->id,
                    $extrasItemOption->id,
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('item_options.0.id', $proteinItemOption->id)
            ->assertJsonPath('item_options.0.sort_order', 1);

        $this->assertSame(
            [$protein->id, $sauce->id, $extras->id],
            $item->fresh()->itemOptions()->pluck('option_id')->all()
        );

        $this->assertSame([1, 2, 3], $item->fresh()->itemOptions()->pluck('sort_order')->all());
    }

    public function test_item_option_reorder_rejects_options_from_another_item(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        [$sauce, $extras] = $this->makeOptions();

        $item = Item::query()->create([
            'name' => 'Chicken Wrap',
            'cost' => 12.50,
            'category_id' => $category->id,
            'active' => true,
        ]);

        $otherItem = Item::query()->create([
            'name' => 'Steak Wrap',
            'cost' => 14.50,
            'category_id' => $category->id,
            'active' => true,
        ]);

        $itemOption = ItemOption::query()->create(['item_id' => $item->id, 'option_id' => $sauce->id, 'sort_order' => 1]);
        $otherItemOption = ItemOption::query()->create(['item_id' => $otherItem->id, 'option_id' => $extras->id, 'sort_order' => 1]);

        $response = $this
            ->actingAs($admin)
            ->patchJson(route('admin.items.item-options.update-order', $item), [
                'item_options' => [$otherItemOption->id, $itemOption->id],
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_options']);

        $this->assertDatabaseHas('item_options', [
            'id' => $itemOption->id,
            'sort_order' => 1,
        ]);
    }

    /**
     * @return array{0: \App\Models\Option, 1: \App\Models\Option, 2: \App\Models\Option}
     */
    private function makeOptions(): array
    {
        return [
            Option::query()->create(['name' => 'Sauce']),
            Option::query()->create(['name' => 'Extras']),
            Option::query()->create(['name' => 'Protein']),
        ];
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
