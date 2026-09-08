<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Order;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderItemsDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_each_items_options_and_prices_them_into_the_line_total(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeGroupOrder();

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();

        // The burger's $15.00 base plus $4.50 of extras.
        $response->assertSee('$19.50');
        // The wrap is priced entirely through its options.
        $response->assertSee('$16.50');
        $response->assertDontSee('$0.00 each');

        // The options themselves are what the kitchen builds.
        $response->assertSee('Bacon');
        $response->assertSee('Chicken');
        $response->assertSee('Avocado');
        $response->assertSee('Extras:', false);
    }

    public function test_it_groups_a_group_orders_items_by_participant(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeGroupOrder();

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Claude Gumbs');
        $response->assertSee('Jennica');
        $response->assertSeeInOrder(['Claude Gumbs', 'Cheese Burger', 'Jennica', 'Spinach Wrap']);
    }

    public function test_it_shows_an_item_note(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeGroupOrder();
        $order->orderItems()->first()->update(['comment' => 'No pickles please']);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('No pickles please');
    }

    public function test_a_solo_order_renders_without_participant_headings(): void
    {
        $admin = $this->makeAdmin();
        $status = Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Cheese Burger', 'category_id' => $category->id, 'cost' => 15, 'active' => true]);

        $order = Order::query()->create([
            'number' => 'ORD-SOLO',
            'customer_id' => Customer::query()->create(['name' => 'Walk In', 'source' => 'pos'])->id,
            'status_id' => $status->id,
            'subtotal' => 15,
            'service_charge' => 0,
            'total' => 15,
        ]);
        $order->orderItems()->create(['item_id' => $item->id, 'price' => 15, 'quantity' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Cheese Burger')
            ->assertSee('$15.00')
            ->assertDontSee('placed the order');
    }

    private function makeGroupOrder(): Order
    {
        $status = Status::query()->create(['name' => 'completed']);
        $customer = Customer::query()->create(['name' => 'Claude Gumbs', 'source' => 'web-customer']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $burger = Item::query()->create(['name' => 'Cheese Burger', 'category_id' => $category->id, 'cost' => 15, 'active' => true]);
        $wrap = Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $category->id, 'cost' => 0, 'active' => true]);

        $extras = Option::query()->create(['name' => 'Extras']);
        $protein = Option::query()->create(['name' => 'Protein']);
        $bacon = OptionValue::query()->create(['option_id' => $extras->id, 'name' => 'Bacon', 'price' => 4.5]);
        $chicken = OptionValue::query()->create(['option_id' => $protein->id, 'name' => 'Chicken', 'price' => 12]);
        $avocado = OptionValue::query()->create(['option_id' => $extras->id, 'name' => 'Avocado', 'price' => 4.5]);

        $order = Order::query()->create([
            'number' => '6450',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subtotal' => 36,
            'service_charge' => 0,
            'total' => 36,
        ]);

        $claude = $order->participants()->create(['client_id' => 'person-0', 'name' => 'Claude Gumbs', 'is_primary' => true, 'sort_order' => 0]);
        $jennica = $order->participants()->create(['client_id' => 'person-1', 'name' => 'Jennica', 'is_primary' => false, 'sort_order' => 1]);

        $burgerLine = $order->orderItems()->create([
            'order_participant_id' => $claude->id,
            'item_id' => $burger->id,
            'price' => 15,
            'quantity' => 1,
        ]);
        $burgerLine->orderItemOptions()->create(['option_value_id' => $bacon->id, 'price' => 4.5, 'qty' => 1]);

        $wrapLine = $order->orderItems()->create([
            'order_participant_id' => $jennica->id,
            'item_id' => $wrap->id,
            'price' => 0,
            'quantity' => 1,
        ]);
        $wrapLine->orderItemOptions()->create(['option_value_id' => $chicken->id, 'price' => 12, 'qty' => 1]);
        $wrapLine->orderItemOptions()->create(['option_value_id' => $avocado->id, 'price' => 4.5, 'qty' => 1]);

        return $order->fresh();
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
