<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestBundleOrderingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_order_can_expand_bundle_selection_into_order_items(): void
    {
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Combos', 'sort_order' => 1]);
        $wrap = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 10.00, 'active' => true]);
        $drink = Item::query()->create(['name' => 'Drink', 'category_id' => $category->id, 'cost' => 3.00, 'active' => true]);
        $option = Option::query()->create(['name' => 'Sauce']);
        $optionValue = OptionValue::query()->create(['option_id' => $option->id, 'name' => 'BBQ', 'price' => 1.00]);
        $itemOption = $wrap->itemOptions()->create([
            'option_id' => $option->id,
            'required' => false,
            'type' => 'single',
        ]);
        $itemOption->itemOptionValues()->create([
            'option_value_id' => $optionValue->id,
            'price' => 1.00,
            'in_stock' => true,
        ]);
        $bundle = Bundle::query()->create(['name' => 'Wrap + Drink', 'is_active' => true]);
        $bundleWrap = $bundle->bundleItems()->create(['item_id' => $wrap->id, 'quantity' => 1, 'sort_order' => 0]);
        $bundleWrap->optionValues()->create([
            'item_option_id' => $itemOption->id,
            'option_value_id' => $optionValue->id,
            'quantity' => 1,
        ]);
        $bundle->bundleItems()->create(['item_id' => $drink->id, 'quantity' => 1, 'sort_order' => 1]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);

        $response = $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 14.00,
            'service_charge' => 0,
            'total' => 14.00,
            'is_delivery' => false,
            'bundles' => [
                [
                    'bundle_id' => $bundle->id,
                    'quantity' => 2,
                    'comment' => 'No ice',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonCount(2, 'order_items')
            ->assertJsonPath('order_items.0.item_id', $wrap->id)
            ->assertJsonPath('order_items.0.quantity', 2)
            ->assertJsonPath('order_items.0.options.0.option_value_id', $optionValue->id)
            ->assertJsonPath('order_items.1.item_id', $drink->id)
            ->assertJsonPath('order_items.1.quantity', 2);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $response->json('id'),
            'item_id' => $wrap->id,
            'quantity' => 2,
            'comment' => 'No ice',
        ]);
        $this->assertDatabaseHas('order_item_options', [
            'option_value_id' => $optionValue->id,
            'price' => '1.00',
            'qty' => 1,
        ]);
    }

    public function test_guest_order_rejects_unavailable_bundle_selection(): void
    {
        Status::query()->create(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $bundle = Bundle::query()->create(['name' => 'Inactive Bundle', 'is_active' => false]);

        $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 14.00,
            'service_charge' => 0,
            'total' => 14.00,
            'is_delivery' => false,
            'bundles' => [
                [
                    'bundle_id' => $bundle->id,
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bundles.0.bundle_id']);
    }
}
