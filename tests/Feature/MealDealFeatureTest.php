<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\MealDeal;
use App\Models\Option;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MealDealFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_meal_deal_definition(): void
    {
        $this->seed();
        $admin = User::query()->create([
            'firstname' => 'Admin', 'lastname' => 'User', 'username' => 'meal-admin',
            'email' => 'meal-admin@example.com', 'password' => Hash::make('password'), 'role_id' => 1,
        ]);
        $deal = MealDeal::query()->where('name', 'The Albena')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.meal-deals.index'))
            ->assertOk()
            ->assertSee('The Albena')
            ->assertSee('Create Meal Deal')
            ->assertSee('Edit Meal Deal')
            ->assertSee('Delete Meal Deal');
        $this->actingAs($admin)->from(route('admin.meal-deals.index'))->post(route('admin.meal-deals.store'), [
            'form_action' => 'create',
            'name' => '',
        ])->assertRedirect(route('admin.meal-deals.index'))->assertSessionHasErrors(['name']);
        $this->actingAs($admin)->get(route('admin.meal-deals.index'))->assertOk()->assertSee('modalOpen: true', false);
        $this->actingAs($admin)->post(route('admin.meal-deals.store'), [
            'name' => 'Test Deal',
            'base_price' => 12,
            'service_charge_rate' => 0.15,
            'primary_category_id' => $deal->primary_category_id,
            'primary_item_id' => $deal->primary_item_id,
            'selection_groups' => $deal->selection_groups,
            'is_active' => 1,
        ])->assertRedirect(route('admin.meal-deals.index'));
        $this->assertDatabaseHas('meal_deals', ['name' => 'Test Deal']);
        $this->actingAs($admin)->put(route('admin.meal-deals.update', $deal), [
            'name' => 'The Albena',
            'base_price' => 13,
            'service_charge_rate' => 0.15,
            'marketing_max_saving' => 4,
            'primary_category_id' => $deal->primary_category_id,
            'primary_item_id' => $deal->primary_item_id,
            'selection_groups' => $deal->selection_groups,
            'sort_order' => 2,
            'is_active' => 1,
        ])->assertRedirect(route('admin.meal-deals.index'));
        $this->assertSame(2, $deal->fresh()->sort_order);

        $this->actingAs($admin)->post(route('admin.meal-deals.store'), [
            'name' => 'Choose a Wrap Deal',
            'base_price' => '13.00',
            'service_charge_rate' => '0.15',
            'primary_category_id' => $deal->primary_category_id,
            'primary_item_id' => '',
            'selection_groups' => $deal->selection_groups,
        ])->assertRedirect(route('admin.meal-deals.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('meal_deals', ['name' => 'Choose a Wrap Deal', 'primary_category_id' => $deal->primary_category_id, 'primary_item_id' => null]);

        $side = Item::query()->where('name', 'V8 Original — 100% Vegetable Juice')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.meal-deals.store'), [
            'name' => 'Menu Item Choice Deal', 'base_price' => 10, 'service_charge_rate' => 0.15,
            'primary_category_id' => $deal->primary_category_id, 'primary_item_id' => $deal->primary_item_id,
            'selection_groups' => [[
                'kind' => 'item', 'category_id' => $side->category_id,
                'allowed_ids' => [$side->id], 'min' => 1, 'max' => 1,
            ]],
        ])->assertRedirect(route('admin.meal-deals.index'))->assertSessionHasNoErrors();
        $itemGroup = MealDeal::query()->where('name', 'Menu Item Choice Deal')->firstOrFail()->selection_groups[0];
        $this->assertSame('item', $itemGroup['kind']);
        $this->assertSame('item_category_'.$side->category_id, $itemGroup['key']);

        $this->actingAs($admin)->delete(route('admin.meal-deals.destroy', $deal))->assertRedirect();
        $this->assertSoftDeleted($deal);
    }

    public function test_unavailable_active_deal_reports_why_it_is_missing_from_customer_list(): void
    {
        $this->seed();
        $deal = MealDeal::query()->where('name', 'The Albena')->firstOrFail();
        $originalPrimaryId = $deal->primary_item_id;
        $unconfigured = Item::query()->create([
            'name' => 'Unavailable Wrap', 'category_id' => $deal->primaryItem->category_id,
            'cost' => 0, 'active' => false,
        ]);
        $deal->update(['primary_item_id' => $unconfigured->id]);

        $admin = User::query()->create([
            'firstname' => 'Admin', 'lastname' => 'User', 'username' => 'meal-availability-admin',
            'email' => 'meal-availability-admin@example.com', 'password' => Hash::make('password'), 'role_id' => 1,
        ]);
        $this->actingAs($admin)->get(route('admin.meal-deals.index'))->assertOk()
            ->assertSee('Unavailable')
            ->assertSee('The selected item is unavailable.');

        $this->getJson('/api/guest/meal-deals')->assertOk()
            ->assertJsonCount(0, 'meal_deals')
            ->assertJsonPath('unavailable_meal_deals.0.name', 'The Albena')
            ->assertJsonPath('unavailable_meal_deals.0.issues.0', 'The selected item is unavailable.');

        $deal->update(['primary_item_id' => $originalPrimaryId]);
        $this->getJson('/api/guest/meal-deals')->assertOk()
            ->assertJsonPath('meal_deals.0.name', 'The Albena')
            ->assertJsonCount(0, 'unavailable_meal_deals');
    }

    private function albenaSelection(): array
    {
        $deal = MealDeal::query()->where('name', 'The Albena')->firstOrFail();
        $groups = collect($deal->selection_groups)->keyBy('key');

        return [
            'meal_deal_id' => $deal->id,
            'quantity' => 1,
            'selections' => [
                'protein' => [$groups['protein']['allowed_ids'][0]],
                'sauces' => array_slice($groups['sauces']['allowed_ids'], 0, 2),
                'free_additions' => [],
                'extras' => [],
                'side' => [$groups['side']['allowed_ids'][0]],
                'drink' => [$groups['drink']['allowed_ids'][0]],
            ],
        ];
    }

    public function test_category_deal_accepts_eligible_items_and_rejects_items_outside_its_category(): void
    {
        $this->seed();
        $deal = MealDeal::query()->where('name', 'The Albena')->firstOrFail();
        $solo = $deal->primaryItem;
        $anotherWrap = Item::query()->create([
            'name' => 'Another Wrap', 'category_id' => $deal->primary_category_id,
            'cost' => 9, 'active' => true, 'menu_visible' => true,
        ]);
        foreach ($solo->itemOptions()->with('itemOptionValues')->get() as $sourceOption) {
            $copy = $anotherWrap->itemOptions()->create([
                'option_id' => $sourceOption->option_id, 'type' => $sourceOption->type,
                'required' => $sourceOption->required, 'min' => $sourceOption->min, 'max' => $sourceOption->max,
            ]);
            foreach ($sourceOption->itemOptionValues as $value) {
                $copy->itemOptionValues()->create([
                    'option_value_id' => $value->option_value_id, 'price' => $value->price,
                    'in_stock' => $value->in_stock,
                ]);
            }
        }
        $deal->update(['primary_item_id' => null]);

        $this->getJson('/api/guest/meal-deals')->assertOk()
            ->assertJsonPath('meal_deals.0.primary_category_id', $deal->primary_category_id)
            ->assertJsonPath('meal_deals.0.eligible_items.0.id', $anotherWrap->id);

        $selection = $this->albenaSelection();
        $selection['item_id'] = $anotherWrap->id;
        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertOk()->assertJsonPath('meal_deals.0.item_id', $anotherWrap->id)
            ->assertJsonPath('subtotal', 13);

        $selection['item_id'] = Item::query()->where('name', 'V8 Original — 100% Vegetable Juice')->firstOrFail()->id;
        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertUnprocessable()->assertJsonValidationErrors(['meal_deals.0.item_id']);
    }

    public function test_customer_choices_can_mix_unattached_option_values_and_menu_items(): void
    {
        $this->seed();
        $deal = MealDeal::query()->where('name', 'The Albena')->firstOrFail();
        $flatbread = Option::query()->create(['name' => 'Flat bread']);
        $flatbreadValue = $flatbread->optionValues()->create(['name' => 'Spinach', 'price' => 2]);
        $side = Item::query()->where('name', 'Seasoned Fries')->firstOrFail();
        $groups = $deal->selection_groups;
        $groups[] = ['key' => 'option_'.$flatbread->id, 'label' => 'Flat bread', 'kind' => 'option',
            'option_id' => $flatbread->id, 'allowed_ids' => [$flatbreadValue->id],
            'min' => 1, 'max' => 1, 'paid_extra' => false];
        $groups[] = ['key' => 'item_category_'.$side->category_id, 'label' => 'Sides', 'kind' => 'item',
            'category_id' => $side->category_id, 'allowed_ids' => [$side->id],
            'min' => 1, 'max' => 1, 'paid_extra' => true];
        $deal->update(['selection_groups' => $groups]);

        $definition = $this->getJson('/api/guest/meal-deals')->assertOk()->json('meal_deals.0');
        $this->assertSame('Spinach', $definition['eligible_items'][0]['item_options'][6]['item_option_values'][0]['option_value']['name']);
        $this->assertSame('option', $definition['selection_groups'][6]['kind']);
        $this->assertSame('item', $definition['selection_groups'][7]['kind']);

        $selection = $this->albenaSelection();
        $selection['selections']['option_'.$flatbread->id] = [$flatbreadValue->id];
        $selection['selections']['item_category_'.$side->category_id] = [$side->id];
        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertOk()
            ->assertJsonPath('meal_deals.0.selections.option_'.$flatbread->id.'.0.name', 'Spinach')
            ->assertJsonPath('meal_deals.0.selections.item_category_'.$side->category_id.'.0.name', 'Seasoned Fries')
            ->assertJsonPath('meal_deals.0.extras_price', (int) $side->cost);

        Status::query()->firstOrCreate(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Guest', 'source' => 'guest-web']);
        $order = $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id, 'subtotal' => 0, 'service_charge' => 0,
            'total' => 0, 'is_delivery' => false, 'meal_deals' => [$selection],
        ])->assertCreated()
            ->assertJsonPath('meal_deals.0.selections.item_category_'.$side->category_id.'.0.name', 'Seasoned Fries')
            ->assertJsonCount(1, 'order_items');
        $this->assertDatabaseHas('order_item_options', [
            'order_item_id' => $order->json('order_items.0.id'), 'option_value_id' => $flatbreadValue->id,
        ]);

        $selection['selections']['item_category_'.$side->category_id] = [$flatbreadValue->id];
        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertUnprocessable()->assertJsonValidationErrors(['meal_deals.0.selections.item_category_'.$side->category_id]);
    }

    public function test_albena_is_quoted_and_ordered_with_persistent_identity(): void
    {
        $this->seed();
        Status::query()->firstOrCreate(['name' => 'pending']);
        $selection = $this->albenaSelection();

        $this->getJson('/api/guest/meal-deals')->assertOk()
            ->assertJsonPath('meal_deals.0.name', 'The Albena')
            ->assertJsonStructure(['meal_deals' => [['selection_groups' => [['choices']]]]]);

        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertOk()
            ->assertJsonPath('subtotal', 13)
            ->assertJsonPath('service_charge', 1.95);

        $customer = Customer::query()->create(['name' => 'Guest', 'source' => 'guest-web']);
        $response = $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 0,
            'service_charge' => 0,
            'total' => 0,
            'is_delivery' => false,
            'meal_deals' => [$selection],
        ]);

        $response->assertCreated()
            ->assertJsonPath('subtotal', '13.00')
            ->assertJsonPath('meal_deal_service_charge', '1.95')
            ->assertJsonPath('total', '14.95')
            ->assertJsonPath('meal_deals.0.name', 'The Albena')
            ->assertJsonPath('meal_deals.0.selections.primary_item.name', 'Solo Wrap')
            ->assertJsonCount(1, 'order_items');

        $purchaseId = $response->json('meal_deals.0.id');
        $this->assertDatabaseHas('order_items', ['order_meal_deal_id' => $purchaseId]);
        $this->assertDatabaseHas('order_item_options', ['order_item_id' => $response->json('order_items.0.id'), 'option_value_id' => $selection['selections']['drink'][0]]);

        $admin = User::query()->create([
            'firstname' => 'Admin', 'lastname' => 'User', 'username' => 'meal-order-admin',
            'email' => 'meal-order-admin@example.com', 'password' => Hash::make('password'), 'role_id' => 1,
        ]);
        $this->actingAs($admin)->get(route('admin.orders.show', $response->json('id')))
            ->assertOk()->assertSee('Meal Deal: The Albena')->assertSee('Deal base:')->assertSee('Drink:');
    }

    public function test_albena_rejects_invalid_choices_and_solo_wrap_as_a_regular_item(): void
    {
        $this->seed();
        $selection = $this->albenaSelection();
        $selection['selections']['side'] = [$selection['selections']['drink'][0]];

        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meal_deals.0.selections.side']);

        $selection['selections']['side'] = [['unexpected' => 'array']];
        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meal_deals.0.selections.side.0']);

        $soloId = MealDeal::query()->where('name', 'The Albena')->value('primary_item_id');
        $customer = Customer::query()->create(['name' => 'Guest', 'source' => 'guest-web']);
        $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 0,
            'total' => 0,
            'is_delivery' => false,
            'items' => [['item_id' => $soloId, 'price' => 0, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.item_id']);
    }

    public function test_big_boy_requires_two_different_proteins_even_when_the_option_value_ids_differ(): void
    {
        $this->seed();
        $protein = Option::query()->where('name', 'Protein')->firstOrFail();
        $protein2 = Option::query()->where('name', 'Protein 2')->firstOrFail();
        $wrapCategory = Item::query()->where('name', 'Solo Wrap')->firstOrFail()->category_id;
        $wrap = Item::query()->create(['name' => 'Big Boy Wrap', 'category_id' => $wrapCategory, 'cost' => 0, 'active' => true, 'menu_visible' => false]);
        foreach ([$protein, $protein2] as $option) {
            $itemOption = $wrap->itemOptions()->create(['option_id' => $option->id, 'type' => 'single']);
            foreach ($option->optionValues as $value) {
                $itemOption->itemOptionValues()->create(['option_value_id' => $value->id, 'price' => 0, 'in_stock' => true]);
            }
        }
        $first = $protein->optionValues()->where('name', 'Chicken')->firstOrFail();
        $same = $protein2->optionValues()->where('name', 'Chicken')->firstOrFail();
        $different = $protein2->optionValues()->where('name', 'Steak')->firstOrFail();
        $groups = [
            ['key' => 'protein', 'label' => 'Protein', 'kind' => 'option', 'option_id' => $protein->id, 'allowed_ids' => [$first->id], 'min' => 1, 'max' => 1],
            ['key' => 'protein_2', 'label' => 'Protein 2', 'kind' => 'option', 'option_id' => $protein2->id, 'allowed_ids' => [$same->id, $different->id], 'min' => 1, 'max' => 1],
        ];
        $deal = MealDeal::query()->create(['name' => 'Big Boy', 'base_price' => 25, 'primary_category_id' => $wrapCategory, 'primary_item_id' => $wrap->id, 'selection_groups' => $groups, 'is_active' => true]);

        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [[
            'meal_deal_id' => $deal->id, 'quantity' => 1,
            'selections' => ['protein' => [$first->id], 'protein_2' => [$same->id]],
        ]]])->assertUnprocessable()->assertJsonValidationErrors(['meal_deals.0.selections.protein_2']);

        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [[
            'meal_deal_id' => $deal->id, 'quantity' => 1,
            'selections' => ['protein' => [$first->id], 'protein_2' => [$different->id]],
        ]]])->assertOk()->assertJsonPath('subtotal', 25);
    }

    public function test_duplicate_sides_are_allowed_only_when_the_deal_group_permits_them(): void
    {
        $this->seed();
        $selection = $this->albenaSelection();
        $deal = MealDeal::query()->findOrFail($selection['meal_deal_id']);
        $side = $selection['selections']['side'][0];
        $selection['selections']['side'] = [$side, $side];
        $groups = $deal->selection_groups;
        foreach ($groups as &$group) {
            if ($group['key'] === 'side') {
                $group['min'] = 2;
                $group['max'] = 2;
            }
        }
        unset($group);
        $deal->update(['selection_groups' => $groups]);

        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertUnprocessable()->assertJsonValidationErrors(['meal_deals.0.selections.side']);

        foreach ($groups as &$group) {
            if ($group['key'] === 'side') {
                $group['allow_duplicates'] = true;
            }
        }
        unset($group);
        $deal->update(['selection_groups' => $groups]);
        $this->postJson('/api/guest/meal-deals/quote', ['meal_deals' => [$selection]])
            ->assertOk()->assertJsonPath('subtotal', 13);
    }

    public function test_paid_extras_and_regular_items_are_priced_with_only_base_deal_service_charge(): void
    {
        $this->seed();
        Status::query()->firstOrCreate(['name' => 'pending']);
        $selection = $this->albenaSelection();
        $groups = collect(MealDeal::query()->findOrFail($selection['meal_deal_id'])->selection_groups)->keyBy('key');
        $selection['selections']['extras'] = [$groups['extras']['allowed_ids'][0]];
        $drink = Item::query()->where('name', 'V8 Original — 100% Vegetable Juice')->firstOrFail();
        $customer = Customer::query()->create(['name' => 'Guest', 'source' => 'guest-web']);
        $payload = [
            'customer_id' => $customer->id, 'subtotal' => 0, 'service_charge' => 0, 'total' => 0,
            'is_delivery' => false, 'meal_deals' => [$selection],
            'items' => [['item_id' => $drink->id, 'price' => 3, 'quantity' => 1]],
        ];

        $first = $this->postJson('/api/guest/orders', $payload, ['Idempotency-Key' => 'meal-mixed-1'])->assertCreated();
        $first->assertJsonPath('subtotal', '17.50')->assertJsonPath('meal_deal_service_charge', '1.95')->assertJsonPath('total', '19.45');
        $this->postJson('/api/guest/orders', $payload, ['Idempotency-Key' => 'meal-mixed-1'])->assertOk()->assertJsonPath('id', $first->json('id'));
        $this->assertDatabaseCount('order_meal_deals', 1);
    }

    public function test_authenticated_customer_can_order_meal_deal_without_regular_items(): void
    {
        $this->seed();
        Status::query()->firstOrCreate(['name' => 'pending']);
        $user = User::query()->create([
            'firstname' => 'Customer', 'lastname' => 'One', 'username' => 'meal-customer',
            'email' => 'meal-customer@example.com', 'password' => Hash::make('password'), 'role_id' => 3,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/me/orders', [
            'subtotal' => 0, 'service_charge' => 0, 'total' => 0, 'is_delivery' => false,
            'items' => [], 'meal_deals' => [$this->albenaSelection()],
        ])->assertCreated()->assertJsonPath('meal_deals.0.name', 'The Albena');
    }
}
