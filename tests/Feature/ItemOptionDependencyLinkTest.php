<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOptionValue;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ordering clients read an option's dependency through the parent value's
 * option_dependency_id. A dependency created without that back-reference is
 * present in the database and visible in the admin, but never reaches the
 * customiser — the customer picks Steak and is never asked for a temperature.
 */
class ItemOptionDependencyLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_dependency_points_the_parent_value_at_it(): void
    {
        [$admin, $item, $steak, $temperature] = $this->scenario();

        $this->actingAs($admin)->postJson(route('admin.items.update-option-values', $item), [
            'values' => [$steak->option_value_id => 18],
            'dependencies' => [$steak->option_value_id => [$temperature->id]],
        ])->assertOk();

        $steak->refresh();

        $this->assertNotNull($steak->option_dependency_id, 'The parent value must reference its dependency.');
        $this->assertSame(
            $steak->option_dependency_id,
            $steak->parentDependencies()->first()?->id,
            'The back-reference must point at the dependency that names this value as parent.',
        );
    }

    public function test_the_guest_menu_serves_the_dependency_to_ordering_clients(): void
    {
        [$admin, $item, $steak, $temperature] = $this->scenario();

        $this->actingAs($admin)->postJson(route('admin.items.update-option-values', $item), [
            'values' => [$steak->option_value_id => 18],
            'dependencies' => [$steak->option_value_id => [$temperature->id]],
        ])->assertOk();

        $response = $this->getJson('/api/guest/menu')->assertOk();

        $values = collect($response->json('items'))
            ->firstWhere('id', $item->id)['item_options'][0]['item_option_values'];
        $steakValue = collect($values)->firstWhere('option_value_id', $steak->option_value_id);

        $this->assertNotNull($steakValue['option_dependency'], 'The customiser needs the dependency in the payload.');
        $this->assertSame('Temperature', $steakValue['option_dependency']['child_option']['option']['name']);
    }

    /**
     * The rows already in the database have a dependency whose parent value
     * does not point back at it. Re-saving the option values in the admin has
     * to be enough to repair that — otherwise the only route is deleting the
     * dependency and adding it again.
     */
    public function test_resaving_repairs_a_dependency_that_lost_its_back_reference(): void
    {
        [$admin, $item, $steak, $temperature] = $this->scenario();

        $this->actingAs($admin)->postJson(route('admin.items.update-option-values', $item), [
            'values' => [$steak->option_value_id => 18],
            'dependencies' => [$steak->option_value_id => [$temperature->id]],
        ])->assertOk();

        // Put the value back into the broken state seen in production.
        $steak->update(['option_dependency_id' => null]);

        $this->actingAs($admin)->postJson(route('admin.items.update-option-values', $item), [
            'values' => [$steak->option_value_id => 18],
            'dependencies' => [$steak->option_value_id => [$temperature->id]],
        ])->assertOk();

        $this->assertNotNull($steak->refresh()->option_dependency_id, 'Re-saving should relink the dependency.');
    }

    public function test_removing_the_last_dependency_clears_the_back_reference(): void
    {
        [$admin, $item, $steak, $temperature] = $this->scenario();

        $this->actingAs($admin)->postJson(route('admin.items.update-option-values', $item), [
            'values' => [$steak->option_value_id => 18],
            'dependencies' => [$steak->option_value_id => [$temperature->id]],
        ])->assertOk();

        $this->assertNotNull($steak->refresh()->option_dependency_id);

        $this->actingAs($admin)->postJson(route('admin.items.update-option-values', $item), [
            'values' => [$steak->option_value_id => 18],
            'dependencies' => [$steak->option_value_id => []],
        ])->assertOk();

        $this->assertNull($steak->refresh()->option_dependency_id, 'A value with no dependency must not point at one.');
    }

    /**
     * @return array{0: User, 1: Item, 2: ItemOptionValue, 3: Option}
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
        $steakValue = OptionValue::query()->create(['option_id' => $protein->id, 'name' => 'Steak', 'price' => 18]);

        $temperature = Option::query()->create(['name' => 'Temperature']);
        OptionValue::query()->create(['option_id' => $temperature->id, 'name' => 'Medium', 'price' => 0]);

        $group = $item->itemOptions()->create(['option_id' => $protein->id, 'sort_order' => 1, 'required' => true]);
        $steak = $group->itemOptionValues()->create([
            'option_value_id' => $steakValue->id,
            'price' => 18,
            'in_stock' => true,
        ]);

        return [$admin, $item, $steak, $temperature];
    }
}
