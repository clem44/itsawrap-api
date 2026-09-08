<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionValue;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An ItemOption owns its ItemOptionValues — the per-item prices, stock flags
 * and dependencies — so recreating the groups on every save silently emptied
 * the customiser for any item that was edited, whatever the edit was for.
 */
class AdminItemOptionPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_an_item_keeps_its_configured_option_values(): void
    {
        [$admin, $item, $protein, $chicken] = $this->scenario();

        $this->actingAs($admin)->put(route('admin.items.update', $item), [
            'name' => 'Spinach Wrap',
            'description' => 'Now with a photo',
            'cost' => 0,
            'category_id' => $item->category_id,
            'active' => '1',
            'options' => [$protein->id],
        ])->assertRedirect();

        $group = $item->fresh()->itemOptions()->first();

        $this->assertNotNull($group, 'The option group should have survived the edit.');
        $this->assertSame(1, $group->itemOptionValues()->count(), 'Its values should have survived too.');
        $this->assertSame('12.00', (string) $group->itemOptionValues()->first()->price);
        $this->assertSame($chicken->id, $group->itemOptionValues()->first()->option_value_id);
    }

    public function test_attaching_media_does_not_empty_the_customiser(): void
    {
        [$admin, $item, $protein] = $this->scenario();

        $this->actingAs($admin)->put(route('admin.items.update', $item), [
            'name' => 'Spinach Wrap',
            'cost' => 0,
            'category_id' => $item->category_id,
            'active' => '1',
            'image_path' => 'media-library/spinach-wrap.jpg',
            'options' => [$protein->id],
        ])->assertRedirect();

        $this->assertSame(1, ItemOptionValue::query()->count());
    }

    public function test_removing_an_option_drops_that_group_only(): void
    {
        [$admin, $item, $protein] = $this->scenario();

        $sauce = Option::query()->create(['name' => 'Sauce']);
        $item->itemOptions()->create(['option_id' => $sauce->id, 'sort_order' => 2, 'required' => false]);

        $this->actingAs($admin)->put(route('admin.items.update', $item), [
            'name' => 'Spinach Wrap',
            'cost' => 0,
            'category_id' => $item->category_id,
            'active' => '1',
            'options' => [$protein->id],
        ])->assertRedirect();

        $this->assertSame([$protein->id], $item->fresh()->itemOptions()->pluck('option_id')->all());
        $this->assertSame(1, ItemOptionValue::query()->count());
    }

    public function test_adding_an_option_creates_a_group_beside_the_existing_one(): void
    {
        [$admin, $item, $protein] = $this->scenario();
        $sauce = Option::query()->create(['name' => 'Sauce']);

        $this->actingAs($admin)->put(route('admin.items.update', $item), [
            'name' => 'Spinach Wrap',
            'cost' => 0,
            'category_id' => $item->category_id,
            'active' => '1',
            'options' => [$protein->id, $sauce->id],
        ])->assertRedirect();

        $groups = $item->fresh()->itemOptions()->orderBy('sort_order')->get();

        $this->assertSame([$protein->id, $sauce->id], $groups->pluck('option_id')->all());
        $this->assertSame([1, 2], $groups->pluck('sort_order')->all());
        $this->assertSame(1, ItemOptionValue::query()->count());
    }

    public function test_a_dependent_group_is_left_to_the_dependency_editor(): void
    {
        [$admin, $item, $protein] = $this->scenario();

        $temperature = Option::query()->create(['name' => 'Temperature']);
        $dependent = $item->itemOptions()->create([
            'option_id' => $temperature->id,
            'sort_order' => 9,
            'required' => false,
            'type' => 'dependent',
        ]);

        $this->actingAs($admin)->put(route('admin.items.update', $item), [
            'name' => 'Spinach Wrap',
            'cost' => 0,
            'category_id' => $item->category_id,
            'active' => '1',
            'options' => [$protein->id],
        ])->assertRedirect();

        $this->assertNotNull(ItemOption::query()->find($dependent->id), 'Dependent groups are not managed by this form.');
    }

    /**
     * @return array{0: User, 1: Item, 2: Option, 3: OptionValue}
     */
    private function scenario(): array
    {
        $admin = User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);

        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $category->id, 'cost' => 0, 'active' => true]);

        $protein = Option::query()->create(['name' => 'Protein']);
        $chicken = OptionValue::query()->create(['option_id' => $protein->id, 'name' => 'Chicken', 'price' => 12]);

        $group = $item->itemOptions()->create(['option_id' => $protein->id, 'sort_order' => 1, 'required' => true]);
        $group->itemOptionValues()->create(['option_value_id' => $chicken->id, 'price' => 12, 'in_stock' => true]);

        return [$admin, $item, $protein, $chicken];
    }
}
