<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryWindow;
use App\Models\Item;
use App\Models\Order;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'America/Anguilla']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guest_can_fetch_todays_recurring_delivery_windows(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00', 'America/Anguilla'));

        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/delivery-windows/today')
            ->assertOk()
            ->assertJsonCount(1, 'delivery_windows')
            ->assertJsonPath('delivery_windows.0.label', '10:00 AM - 11:30 AM')
            ->assertJsonPath('delivery_windows.0.delivery_date', '2026-08-31')
            ->assertJsonPath('delivery_windows.0.remaining_capacity', 8)
            ->assertJsonPath('delivery_windows.0.driver_count', 0)
            ->assertJsonPath('delivery_windows.0.is_available', true);
    }

    public function test_authenticated_customer_can_fetch_todays_delivery_windows(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00', 'America/Anguilla'));
        Sanctum::actingAs($this->makeUser(Role::CUSTOMER_ID, 'customer-windows'));

        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '13:45',
            'capacity' => 4,
            'is_active' => true,
        ]);

        $this->getJson('/api/me/delivery-windows/today')
            ->assertOk()
            ->assertJsonPath('delivery_windows.0.label', '12:00 PM - 1:45 PM');
    }

    public function test_specific_date_windows_for_today_replace_recurring_weekday_windows(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00', 'America/Anguilla'));

        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_SPECIFIC_DATE,
            'delivery_date' => '2026-08-31',
            'start_time' => '14:00',
            'end_time' => '15:00',
            'capacity' => 3,
            'is_active' => true,
        ]);
        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_SPECIFIC_DATE,
            'delivery_date' => '2026-09-07',
            'start_time' => '16:00',
            'end_time' => '17:00',
            'capacity' => 3,
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/delivery-windows/today')
            ->assertOk()
            ->assertJsonCount(1, 'delivery_windows')
            ->assertJsonPath('delivery_windows.0.label', '2:00 PM - 3:00 PM');
    }

    public function test_weekly_window_without_day_is_available_everyday(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 09:00:00', 'America/Anguilla'));

        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/delivery-windows/today')
            ->assertOk()
            ->assertJsonCount(1, 'delivery_windows')
            ->assertJsonPath('delivery_windows.0.delivery_window_id', $window->id)
            ->assertJsonPath('delivery_windows.0.delivery_date', '2026-09-01')
            ->assertJsonPath('delivery_windows.0.label', '10:00 AM - 11:30 AM');
    }

    public function test_specific_date_windows_replace_everyday_recurring_windows(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 09:00:00', 'America/Anguilla'));

        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $specificDateWindow = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_SPECIFIC_DATE,
            'delivery_date' => '2026-09-01',
            'start_time' => '12:00',
            'end_time' => '13:45',
            'capacity' => 3,
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/delivery-windows/today')
            ->assertOk()
            ->assertJsonCount(1, 'delivery_windows')
            ->assertJsonPath('delivery_windows.0.delivery_window_id', $specificDateWindow->id)
            ->assertJsonPath('delivery_windows.0.label', '12:00 PM - 1:45 PM');
    }

    public function test_weekday_recurring_windows_replace_only_conflicting_everyday_recurring_windows(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 09:00:00', 'America/Anguilla'));

        $nonConflictingEverydayWindow = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '13:00',
            'end_time' => '14:00',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $tuesdayWindow = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 2,
            'start_time' => '12:00',
            'end_time' => '14:30',
            'capacity' => 3,
            'is_active' => true,
        ]);

        $this->getJson('/api/guest/delivery-windows/today')
            ->assertOk()
            ->assertJsonCount(2, 'delivery_windows')
            ->assertJsonPath('delivery_windows.0.delivery_window_id', $nonConflictingEverydayWindow->id)
            ->assertJsonPath('delivery_windows.0.label', '10:00 AM - 11:30 AM')
            ->assertJsonPath('delivery_windows.1.delivery_window_id', $tuesdayWindow->id)
            ->assertJsonPath('delivery_windows.1.label', '12:00 PM - 2:30 PM');
    }

    public function test_passed_and_full_windows_are_returned_as_unavailable(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 10:30:00', 'America/Anguilla'));

        $passedWindow = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $fullWindow = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'capacity' => 1,
            'is_active' => true,
        ]);
        $this->createExistingDelivery($fullWindow);

        $response = $this->getJson('/api/guest/delivery-windows/today')->assertOk();

        $response
            ->assertJsonPath('delivery_windows.0.delivery_window_id', $passedWindow->id)
            ->assertJsonPath('delivery_windows.0.is_available', false)
            ->assertJsonPath('delivery_windows.0.unavailable_reason', 'time_passed')
            ->assertJsonPath('delivery_windows.1.delivery_window_id', $fullWindow->id)
            ->assertJsonPath('delivery_windows.1.is_available', false)
            ->assertJsonPath('delivery_windows.1.unavailable_reason', 'full');
    }

    public function test_delivery_order_creates_delivery_record_with_window_snapshot(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00', 'America/Anguilla'));

        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $item = $this->makeItem();
        Status::query()->create(['name' => 'pending']);

        $response = $this->postJson('/api/guest/orders', $this->deliveryOrderPayload($customer, $item, $window), [
            'Idempotency-Key' => 'delivery-order-1',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('delivery.delivery_window_id', $window->id)
            ->assertJsonPath('delivery.address', '123 Main Road, The Valley');

        $delivery = Delivery::query()->where('order_id', $response->json('id'))->firstOrFail();

        $this->assertSame($window->id, $delivery->delivery_window_id);
        $this->assertSame('2026-08-31', $delivery->delivery_date->format('Y-m-d'));
        $this->assertSame('10:00', $delivery->window_start_at->format('H:i'));
        $this->assertSame('11:30', $delivery->window_end_at->format('H:i'));
        $this->assertSame('123 Main Road, The Valley', $delivery->address);
    }

    public function test_delivery_order_requires_delivery_fields(): void
    {
        Status::query()->create(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $item = $this->makeItem();

        $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => true,
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'delivery_window_id',
                'delivery_address',
                'delivery_latitude',
                'delivery_longitude',
            ]);
    }

    public function test_delivery_order_cannot_reserve_full_window(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00', 'America/Anguilla'));

        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 1,
            'is_active' => true,
        ]);
        $this->createExistingDelivery($window);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $item = $this->makeItem();
        Status::query()->create(['name' => 'pending']);

        $this->postJson('/api/guest/orders', $this->deliveryOrderPayload($customer, $item, $window))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['delivery_window_id']);
    }

    public function test_pickup_order_does_not_create_delivery_record(): void
    {
        Queue::fake();
        Status::query()->create(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $item = $this->makeItem();

        $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ])->assertCreated();

        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_idempotent_guest_delivery_order_replay_returns_existing_delivery(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00', 'America/Anguilla'));

        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);
        $item = $this->makeItem();
        Status::query()->create(['name' => 'pending']);
        $payload = $this->deliveryOrderPayload($customer, $item, $window);

        $firstResponse = $this->postJson('/api/guest/orders', $payload, ['Idempotency-Key' => 'delivery-order-2']);
        $secondResponse = $this->postJson('/api/guest/orders', $payload, ['Idempotency-Key' => 'delivery-order-2']);

        $firstResponse->assertCreated();
        $secondResponse
            ->assertOk()
            ->assertJsonPath('id', $firstResponse->json('id'))
            ->assertJsonPath('delivery.delivery_window_id', $window->id);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('deliveries', 1);
    }

    public function test_admin_can_create_delivery_window_and_assign_driver_only(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-admin');
        $driver = $this->makeUser(Role::DRIVER_ID, 'delivery-driver');
        $staff = $this->makeUser(Role::STAFF_ID, 'delivery-staff');

        $this->actingAs($admin)
            ->post(route('admin.delivery-windows.store'), [
                'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
                'day_of_week' => 1,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'capacity' => 8,
                'is_active' => '1',
                'driver_ids' => [$staff->id],
            ])
            ->assertSessionHasErrors(['driver_ids.0']);

        $this->actingAs($admin)
            ->post(route('admin.delivery-windows.store'), [
                'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
                'day_of_week' => 1,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'capacity' => 8,
                'is_active' => '1',
                'driver_ids' => [$driver->id],
            ])
            ->assertRedirect(route('admin.delivery-windows.index'));

        $this->assertDatabaseHas('delivery_window_driver', [
            'user_id' => $driver->id,
        ]);
    }

    public function test_admin_can_create_everyday_delivery_window(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-everyday-admin');

        $this->actingAs($admin)
            ->post(route('admin.delivery-windows.store'), [
                'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'capacity' => 8,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.delivery-windows.index'));

        $this->assertDatabaseHas('delivery_windows', [
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '11:30',
        ]);
    }

    public function test_admin_can_update_delivery_window_and_driver_assignments(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-update-admin');
        $driver = $this->makeUser(Role::DRIVER_ID, 'delivery-update-driver');
        $previousDriver = $this->makeUser(Role::DRIVER_ID, 'delivery-previous-driver');
        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $window->drivers()->attach($previousDriver);

        $this->actingAs($admin)
            ->put(route('admin.delivery-windows.update', $window), [
                'form_action' => 'edit',
                'edit_id' => $window->id,
                'schedule_type' => DeliveryWindow::TYPE_SPECIFIC_DATE,
                'delivery_date' => '2026-08-31',
                'start_time' => '12:00',
                'end_time' => '13:45',
                'capacity' => 5,
                'driver_ids' => [$driver->id],
                'notes' => 'Lunch deliveries',
            ])
            ->assertRedirect(route('admin.delivery-windows.index'));

        $window->refresh();

        $this->assertSame(DeliveryWindow::TYPE_SPECIFIC_DATE, $window->schedule_type);
        $this->assertNull($window->day_of_week);
        $this->assertSame('2026-08-31', $window->delivery_date->format('Y-m-d'));
        $this->assertSame('12:00', substr((string) $window->start_time, 0, 5));
        $this->assertSame('13:45', substr((string) $window->end_time, 0, 5));
        $this->assertSame(5, $window->capacity);
        $this->assertFalse($window->is_active);
        $this->assertSame('Lunch deliveries', $window->notes);
        $this->assertTrue($window->drivers()->whereKey($driver->id)->exists());
        $this->assertFalse($window->drivers()->whereKey($previousDriver->id)->exists());
    }

    public function test_admin_can_duplicate_delivery_window_with_driver_assignments(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-duplicate-admin');
        $driver = $this->makeUser(Role::DRIVER_ID, 'delivery-duplicate-driver');
        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
            'notes' => 'Morning deliveries',
        ]);
        $window->drivers()->attach($driver);

        $this->actingAs($admin)
            ->post(route('admin.delivery-windows.duplicate', $window))
            ->assertRedirect(route('admin.delivery-windows.index'));

        $copy = DeliveryWindow::query()->whereKeyNot($window->id)->firstOrFail();

        $this->assertSame($window->schedule_type, $copy->schedule_type);
        $this->assertSame($window->day_of_week, $copy->day_of_week);
        $this->assertSame((string) $window->start_time, (string) $copy->start_time);
        $this->assertSame((string) $window->end_time, (string) $copy->end_time);
        $this->assertSame($window->capacity, $copy->capacity);
        $this->assertTrue($copy->is_active);
        $this->assertSame('Morning deliveries', $copy->notes);
        $this->assertTrue($copy->drivers()->whereKey($driver->id)->exists());
        $this->assertDatabaseCount('delivery_windows', 2);
    }

    public function test_admin_can_delete_unused_delivery_window(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-delete-admin');
        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.delivery-windows.destroy', $window))
            ->assertRedirect(route('admin.delivery-windows.index'));

        $this->assertDatabaseMissing('delivery_windows', [
            'id' => $window->id,
        ]);
    }

    public function test_admin_cannot_delete_delivery_window_with_deliveries(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-protected-delete-admin');
        $window = DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);
        $this->createExistingDelivery($window);

        $this->actingAs($admin)
            ->delete(route('admin.delivery-windows.destroy', $window))
            ->assertRedirect(route('admin.delivery-windows.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('delivery_windows', [
            'id' => $window->id,
        ]);
    }

    public function test_admin_delivery_window_index_renders_existing_windows(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-page-admin');
        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.delivery-windows.index'))
            ->assertOk()
            ->assertSee('Delivery Windows')
            ->assertSee('Monday')
            ->assertSee('10:00 AM - 11:30 AM')
            ->assertSee('Duplicate Delivery Window')
            ->assertSee('Delete Delivery Window');
    }

    public function test_admin_delivery_window_index_labels_null_weekday_as_everyday(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'delivery-everyday-page-admin');
        DeliveryWindow::query()->create([
            'schedule_type' => DeliveryWindow::TYPE_WEEKLY,
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.delivery-windows.index'))
            ->assertOk()
            ->assertSee('Recurring: Everyday')
            ->assertSee('Everyday');
    }

    public function test_admin_can_create_driver_user(): void
    {
        $admin = $this->makeUser(Role::ADMIN_ID, 'driver-admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'firstname' => 'Dana',
                'lastname' => 'Driver',
                'username' => 'dana-driver',
                'email' => 'dana-driver@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role_id' => Role::DRIVER_ID,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'dana-driver',
            'role_id' => Role::DRIVER_ID,
        ]);
    }

    private function makeItem(): Item
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);

        return Item::query()->create([
            'name' => 'Chicken Wrap',
            'category_id' => $category->id,
            'cost' => 12.50,
            'active' => true,
        ]);
    }

    private function makeUser(int $roleId, string $username): User
    {
        return User::query()->create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => 'password',
            'role_id' => $roleId,
        ]);
    }

    private function createExistingDelivery(DeliveryWindow $window): Delivery
    {
        $customer = Customer::query()->create(['name' => 'Existing Customer']);
        $status = Status::query()->firstOrCreate(['name' => 'pending']);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => true,
            'is_reward' => false,
        ]);

        return Delivery::query()->create([
            'order_id' => $order->id,
            'delivery_window_id' => $window->id,
            'delivery_date' => '2026-08-31',
            'window_start_at' => '2026-08-31 12:00:00',
            'window_end_at' => '2026-08-31 13:00:00',
            'address' => 'Existing Address',
            'latitude' => 18.2,
            'longitude' => -63.0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function deliveryOrderPayload(Customer $customer, Item $item, DeliveryWindow $window): array
    {
        return [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => true,
            'delivery_window_id' => $window->id,
            'delivery_address' => '123 Main Road, The Valley',
            'delivery_latitude' => 18.2208000,
            'delivery_longitude' => -63.0686000,
            'delivery_instructions' => 'Call on arrival',
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ];
    }
}
