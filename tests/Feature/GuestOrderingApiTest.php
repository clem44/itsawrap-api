<?php

namespace Tests\Feature;

use App\Jobs\SendFirebasePushNotification;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryWindow;
use App\Models\Item;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\PushSubscription;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GuestOrderingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_menu_only_returns_active_items(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        Item::query()->create(['name' => 'Active Wrap', 'category_id' => $category->id, 'cost' => 12, 'active' => true]);
        Item::query()->create(['name' => 'Hidden Wrap', 'category_id' => $category->id, 'cost' => 12, 'active' => false]);

        $response = $this->getJson('/api/guest/menu');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'categories')
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.name', 'Active Wrap');
    }

    public function test_guest_customer_creation_tags_source(): void
    {
        $response = $this->postJson('/api/guest/customers', [
            'name' => 'Guest Customer',
            'phone' => '555-0100',
            'email' => 'guest@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('source', 'guest-web');

        $this->assertDatabaseHas('customers', [
            'name' => 'Guest Customer',
            'source' => 'guest-web',
        ]);
    }

    public function test_guest_order_creation_uses_pending_status_without_session_mutation(): void
    {
        Queue::fake();

        $status = Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $option = Option::query()->create(['name' => 'Sauce']);
        $optionValue = OptionValue::query()->create(['option_id' => $option->id, 'name' => 'BBQ', 'price' => 1.00]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $staff = User::query()->create([
            'firstname' => 'Staff',
            'lastname' => 'User',
            'username' => 'guest-order-staff',
            'email' => 'guest-order-staff@example.com',
            'password' => 'password',
            'role_id' => 2,
        ]);
        PushSubscription::query()->create([
            'user_id' => $staff->id,
            'provider' => 'firebase',
            'token' => 'guest-order-staff-token',
            'token_hash' => hash('sha256', 'guest-order-staff-token'),
            'platform' => 'ios',
            'app_context' => 'pos',
            'last_seen_at' => now(),
        ]);

        $response = $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 13.50,
            'service_charge' => 0,
            'total' => 13.50,
            'is_delivery' => false,
            'items' => [
                [
                    'item_id' => $item->id,
                    'price' => 12.50,
                    'quantity' => 1,
                    'options' => [
                        [
                            'option_value_id' => $optionValue->id,
                            'price' => 1.00,
                            'qty' => 1,
                        ],
                    ],
                ],
            ],
        ], [
            'Idempotency-Key' => 'guest-order-1',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('source', 'guest-web')
            ->assertJsonPath('status_id', $status->id)
            ->assertJsonPath('session_id', null)
            ->assertJsonPath('order_items.0.item_id', $item->id);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'source' => 'guest-web',
            'idempotency_key' => 'guest-order-1',
            'session_id' => null,
            'is_reward' => false,
        ]);

        Queue::assertPushed(SendFirebasePushNotification::class, function (SendFirebasePushNotification $job): bool {
            return $job->data['type'] === 'web_order_created'
                && $job->data['source'] === 'guest-web';
        });
    }

    public function test_guest_order_replays_existing_order_for_matching_idempotency_key(): void
    {
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);

        $payload = [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                [
                    'item_id' => $item->id,
                    'price' => 12.50,
                    'quantity' => 1,
                ],
            ],
        ];

        $firstResponse = $this->postJson('/api/guest/orders', $payload, [
            'Idempotency-Key' => 'guest-order-2',
        ]);

        $secondResponse = $this->postJson('/api/guest/orders', $payload, [
            'Idempotency-Key' => 'guest-order-2',
        ]);

        $firstResponse->assertCreated();
        $secondResponse
            ->assertOk()
            ->assertJsonPath('id', $firstResponse->json('id'));

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_guest_order_requires_guest_customer_source(): void
    {
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $customer = Customer::query()->create(['name' => 'Staff Customer', 'source' => 'admin']);

        $response = $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                [
                    'item_id' => $item->id,
                    'price' => 12.50,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_guest_delivery_order_requires_delivery_window_and_address(): void
    {
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);

        $response = $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => true,
            'items' => [
                [
                    'item_id' => $item->id,
                    'price' => 12.50,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'delivery_window_id',
                'delivery_address',
                'delivery_latitude',
                'delivery_longitude',
            ]);
    }

    public function test_guest_order_route_is_throttled(): void
    {
        Cache::flush();
        Status::query()->create(['name' => 'pending']);
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);

        $payload = [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                [
                    'item_id' => $item->id,
                    'price' => 12.50,
                    'quantity' => 1,
                ],
            ],
        ];

        foreach (range(1, 10) as $index) {
            $this->postJson('/api/guest/orders', $payload, [
                'Idempotency-Key' => 'throttle-'.$index,
            ])->assertCreated();
        }

        $this->postJson('/api/guest/orders', $payload, [
            'Idempotency-Key' => 'throttle-11',
        ])->assertStatus(429);
    }

    public function test_guest_delivery_windows_route_is_throttled(): void
    {
        Cache::flush();

        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => now()->dayOfWeekIso,
            'start_time' => '23:00',
            'end_time' => '23:30',
            'capacity' => 8,
            'is_active' => true,
        ]);

        foreach (range(1, 120) as $index) {
            $this->getJson('/api/guest/delivery-windows/today')->assertOk();
        }

        $this->getJson('/api/guest/delivery-windows/today')->assertStatus(429);
    }
}
