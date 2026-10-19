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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerOrderHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_their_own_order_history_most_recent_first(): void
    {
        [$user, $customer] = $this->customerScenario();
        $status = Status::query()->create(['name' => 'pending']);
        $item = $this->wrapItem();

        $older = $this->placeOrder($customer, $status, $item);
        $older->forceFill(['created_at' => now()->subDay()])->save();
        $newer = $this->placeOrder($customer, $status, $item);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/orders')
            ->assertOk()
            ->assertJsonPath('data.0.number', $newer->number)
            ->assertJsonPath('data.1.number', $older->number)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.order_items.0.item.name', 'Spinach Wrap');
    }

    public function test_customer_order_history_never_shows_another_customers_orders(): void
    {
        [$user, $customer] = $this->customerScenario();
        $status = Status::query()->create(['name' => 'pending']);
        $item = $this->wrapItem();
        $this->placeOrder($customer, $status, $item);

        $otherCustomer = Customer::query()->create(['name' => 'Someone Else', 'source' => 'web-customer']);
        $this->placeOrder($otherCustomer, $status, $item);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customer_with_no_orders_yet_gets_an_empty_list_not_an_error(): void
    {
        $user = User::query()->create([
            'firstname' => 'Fresh',
            'lastname' => 'Signup',
            'username' => 'fresh-signup',
            'email' => 'fresh@example.com',
            'password' => 'password',
            'role_id' => 3,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/orders')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_customer_can_fetch_one_of_their_own_orders_by_number_with_no_token(): void
    {
        [$user, $customer] = $this->customerScenario();
        $pending = Status::query()->create(['name' => 'pending']);
        $active = Status::query()->create(['name' => 'active']);
        $item = $this->wrapItem();

        $order = $this->placeOrder($customer, $pending, $item);
        $order->update(['status_id' => $active->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/orders/'.$order->number)
            ->assertOk()
            ->assertJsonPath('number', $order->number)
            ->assertJsonPath('status.name', 'active')
            ->assertJsonPath('order_items.0.item.name', 'Spinach Wrap')
            ->assertJsonPath('order_items.0.item_id', $item->id);
    }

    public function test_customer_cannot_fetch_another_customers_order_by_number(): void
    {
        [$user, $customer] = $this->customerScenario();
        $status = Status::query()->create(['name' => 'pending']);
        $item = $this->wrapItem();
        $this->placeOrder($customer, $status, $item);

        $otherCustomer = Customer::query()->create(['name' => 'Someone Else', 'source' => 'web-customer']);
        $othersOrder = $this->placeOrder($otherCustomer, $status, $item);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/orders/'.$othersOrder->number)->assertNotFound();
    }

    public function test_customer_order_routes_require_authentication(): void
    {
        $this->getJson('/api/me/orders')->assertUnauthorized();
        $this->getJson('/api/me/orders/IAW-000123')->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: Customer}
     */
    private function customerScenario(): array
    {
        $user = User::query()->create([
            'firstname' => 'Claude',
            'lastname' => 'Gumbs',
            'username' => 'claude-history',
            'email' => 'claude-history@example.com',
            'password' => 'password',
            'role_id' => 3,
        ]);

        $customer = Customer::query()->create([
            'name' => 'Claude Gumbs',
            'email' => 'claude-history@example.com',
            'source' => 'web-customer',
            'user_id' => $user->id,
        ]);

        return [$user, $customer];
    }

    private function wrapItem(): Item
    {
        $category = Category::query()->firstOrCreate(['name' => 'Wraps'], ['sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);

        $option = Option::query()->create(['name' => 'Sauce']);
        OptionValue::query()->create(['option_id' => $option->id, 'name' => 'BBQ', 'price' => 1.00]);

        return $item;
    }

    private function placeOrder(Customer $customer, Status $status, Item $item): Order
    {
        $order = Order::query()->create([
            'number' => 'IAW-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'source' => 'web-customer',
            'placed_at' => now(),
        ]);

        $order->orderItems()->create([
            'item_id' => $item->id,
            'price' => 12.50,
            'quantity' => 1,
        ]);

        return $order;
    }
}
