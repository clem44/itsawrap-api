<?php

namespace Tests\Feature;

use App\Jobs\SendFirebasePushNotification;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\PushSubscription;
use App\Models\Status;
use App\Models\User;
use App\Services\Push\InvalidPushTokenException;
use App\Services\Push\PushNotificationSender;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_user_can_register_an_ios_pos_push_subscription(): void
    {
        $user = $this->makeUser(roleId: 2);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/push-subscriptions', [
            'token' => 'fcm-token-1',
            'platform' => 'ios',
            'app_context' => 'pos',
            'device_name' => 'Kitchen iPad',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('provider', 'firebase')
            ->assertJsonPath('platform', 'ios')
            ->assertJsonPath('app_context', 'pos')
            ->assertJsonMissingPath('token')
            ->assertJsonMissingPath('token_hash');

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'token' => 'fcm-token-1',
            'token_hash' => hash('sha256', 'fcm-token-1'),
            'platform' => 'ios',
            'app_context' => 'pos',
            'device_name' => 'Kitchen iPad',
            'revoked_at' => null,
        ]);
    }

    public function test_admin_user_can_register_an_android_pos_push_subscription(): void
    {
        $user = $this->makeUser(roleId: 1);
        Sanctum::actingAs($user);

        $this->postJson('/api/push-subscriptions', [
            'token' => 'fcm-token-2',
            'platform' => 'android',
            'app_context' => 'pos',
        ])->assertCreated()
            ->assertJsonPath('platform', 'android');
    }

    public function test_registering_the_same_fcm_token_updates_the_existing_subscription(): void
    {
        $firstUser = $this->makeUser(roleId: 2, username: 'staff-one', email: 'staff-one@example.com');
        $secondUser = $this->makeUser(roleId: 1, username: 'admin-one', email: 'admin-one@example.com');

        Sanctum::actingAs($firstUser);

        $this->postJson('/api/push-subscriptions', [
            'token' => 'shared-fcm-token',
            'platform' => 'ios',
            'app_context' => 'pos',
        ])->assertCreated();

        $subscription = PushSubscription::query()->firstOrFail();
        $subscription->markRevoked();

        Sanctum::actingAs($secondUser);

        $this->postJson('/api/push-subscriptions', [
            'token' => 'shared-fcm-token',
            'platform' => 'android',
            'app_context' => 'pos',
            'device_name' => 'Front Counter',
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $secondUser->id,
            'token_hash' => hash('sha256', 'shared-fcm-token'),
            'platform' => 'android',
            'device_name' => 'Front Counter',
            'revoked_at' => null,
        ]);
    }

    public function test_revoking_a_subscription_sets_revoked_at(): void
    {
        $user = $this->makeUser(roleId: 2);
        Sanctum::actingAs($user);

        $subscriptionId = $this->postJson('/api/push-subscriptions', [
            'token' => 'revokable-token',
            'platform' => 'ios',
            'app_context' => 'pos',
        ])->assertCreated()->json('id');

        $this->deleteJson("/api/push-subscriptions/{$subscriptionId}")
            ->assertNoContent();

        $this->assertNotNull(PushSubscription::query()->findOrFail($subscriptionId)->revoked_at);
    }

    public function test_new_guest_web_order_dispatches_jobs_to_active_admin_and_staff_pos_subscriptions_only(): void
    {
        Queue::fake();

        $admin = $this->makeUser(roleId: 1, username: 'admin-target', email: 'admin-target@example.com');
        $staff = $this->makeUser(roleId: 2, username: 'staff-target', email: 'staff-target@example.com');
        $customerUser = $this->makeUser(roleId: 3, username: 'customer-user', email: 'customer@example.com');
        $adminWeb = $this->makeUser(roleId: 1, username: 'admin-web', email: 'admin-web@example.com');
        $revokedStaff = $this->makeUser(roleId: 2, username: 'revoked-staff', email: 'revoked-staff@example.com');

        $adminSubscription = $this->subscriptionFor($admin, 'admin-pos-token', 'ios', 'pos');
        $staffSubscription = $this->subscriptionFor($staff, 'staff-pos-token', 'android', 'pos');
        $this->subscriptionFor($customerUser, 'customer-pos-token', 'ios', 'pos');
        $this->subscriptionFor($adminWeb, 'admin-web-token', 'web', 'admin');
        $this->subscriptionFor($revokedStaff, 'revoked-token', 'ios', 'pos', revoked: true);

        $orderId = $this->createGuestWebOrder();

        Queue::assertPushed(SendFirebasePushNotification::class, 2);
        Queue::assertPushed(SendFirebasePushNotification::class, function (SendFirebasePushNotification $job) use ($adminSubscription, $orderId): bool {
            return $job->pushSubscriptionId === $adminSubscription->id
                && $job->orderId === $orderId
                && $job->title === 'New web order'
                && $job->data['type'] === 'web_order_created'
                && $job->data['source'] === 'guest-web';
        });
        Queue::assertPushed(SendFirebasePushNotification::class, function (SendFirebasePushNotification $job) use ($staffSubscription): bool {
            return $job->pushSubscriptionId === $staffSubscription->id;
        });
    }

    public function test_customer_token_can_register_but_is_not_targeted_for_pos_order_confirmation(): void
    {
        Queue::fake();

        $customerUser = $this->makeUser(roleId: 3);
        Sanctum::actingAs($customerUser);

        $this->postJson('/api/push-subscriptions', [
            'token' => 'customer-fcm-token',
            'platform' => 'ios',
            'app_context' => 'customer',
        ])->assertCreated();

        $this->createGuestWebOrder();

        Queue::assertNotPushed(SendFirebasePushNotification::class);
    }

    public function test_pos_created_order_does_not_dispatch_web_order_push_notifications(): void
    {
        Queue::fake();

        $staff = $this->makeUser(roleId: 2);
        $this->subscriptionFor($staff, 'staff-token', 'ios', 'pos');
        Sanctum::actingAs($staff);

        $status = Status::query()->create(['name' => 'completed']);
        $category = Category::query()->create(['name' => 'Wraps']);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50]);

        $this->postJson('/api/orders', [
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ])->assertCreated();

        Queue::assertNotPushed(SendFirebasePushNotification::class);
    }

    public function test_order_status_update_dispatches_jobs_to_the_customer_ios_and_android_app_subscriptions_only(): void
    {
        Queue::fake();

        $staff = $this->makeUser(roleId: 2, username: 'status-staff', email: 'status-staff@example.com');
        $customerUser = $this->makeUser(roleId: 3, username: 'status-customer', email: 'status-customer@example.com');
        $otherCustomer = $this->makeUser(roleId: 3, username: 'other-customer', email: 'other-customer@example.com');
        $customer = Customer::query()->create([
            'user_id' => $customerUser->id,
            'name' => 'Status Customer',
            'source' => 'web-customer',
        ]);

        $pending = Status::query()->create(['name' => 'pending']);
        $confirmed = Status::query()->create(['name' => 'confirmed']);
        $order = Order::query()->create([
            'number' => '123',
            'customer_id' => $customer->id,
            'status_id' => $pending->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'source' => 'web-customer',
        ]);

        $iosSubscription = $this->subscriptionFor($customerUser, 'customer-ios-token', 'ios', 'customer');
        $androidSubscription = $this->subscriptionFor($customerUser, 'customer-android-token', 'android', 'customer');
        $this->subscriptionFor($customerUser, 'customer-web-token', 'web', 'customer');
        $this->subscriptionFor($customerUser, 'customer-pos-token', 'ios', 'pos');
        $this->subscriptionFor($otherCustomer, 'other-customer-token', 'ios', 'customer');

        Sanctum::actingAs($staff);

        $this->putJson("/api/orders/{$order->id}", [
            'status_id' => $confirmed->id,
        ])->assertOk();

        Queue::assertPushed(SendFirebasePushNotification::class, 2);
        Queue::assertPushed(SendFirebasePushNotification::class, function (SendFirebasePushNotification $job) use ($iosSubscription, $order, $confirmed): bool {
            return $job->pushSubscriptionId === $iosSubscription->id
                && $job->orderId === $order->id
                && $job->title === 'Order status updated'
                && $job->data['type'] === 'order_status_updated'
                && $job->data['status_id'] === (string) $confirmed->id
                && $job->data['status'] === 'confirmed';
        });
        Queue::assertPushed(SendFirebasePushNotification::class, function (SendFirebasePushNotification $job) use ($androidSubscription): bool {
            return $job->pushSubscriptionId === $androidSubscription->id;
        });
    }

    public function test_admin_order_status_update_dispatches_customer_app_status_notifications(): void
    {
        Queue::fake();

        $admin = $this->makeUser(roleId: 1, username: 'admin-status', email: 'admin-status@example.com');
        $customerUser = $this->makeUser(roleId: 3, username: 'admin-customer', email: 'admin-customer@example.com');
        $customer = Customer::query()->create([
            'user_id' => $customerUser->id,
            'name' => 'Admin Status Customer',
            'source' => 'web-customer',
        ]);

        $pending = Status::query()->create(['name' => 'pending']);
        $ready = Status::query()->create(['name' => 'ready']);
        $order = Order::query()->create([
            'number' => '456',
            'customer_id' => $customer->id,
            'status_id' => $pending->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'source' => 'web-customer',
        ]);

        $subscription = $this->subscriptionFor($customerUser, 'admin-path-customer-token', 'ios', 'customer');

        $this->actingAs($admin)
            ->put("/admin/orders/{$order->id}", ['status_id' => $ready->id])
            ->assertRedirect(route('admin.orders.show', $order));

        Queue::assertPushed(SendFirebasePushNotification::class, function (SendFirebasePushNotification $job) use ($subscription, $ready): bool {
            return $job->pushSubscriptionId === $subscription->id
                && $job->data['type'] === 'order_status_updated'
                && $job->data['status_id'] === (string) $ready->id;
        });
    }

    public function test_unchanged_order_status_does_not_dispatch_customer_status_notification(): void
    {
        Queue::fake();

        $staff = $this->makeUser(roleId: 2, username: 'unchanged-staff', email: 'unchanged-staff@example.com');
        $customerUser = $this->makeUser(roleId: 3, username: 'unchanged-customer', email: 'unchanged-customer@example.com');
        $customer = Customer::query()->create(['user_id' => $customerUser->id, 'name' => 'Unchanged Customer']);
        $pending = Status::query()->create(['name' => 'pending']);
        $order = Order::query()->create([
            'number' => '789',
            'customer_id' => $customer->id,
            'status_id' => $pending->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'source' => 'web-customer',
        ]);
        $this->subscriptionFor($customerUser, 'unchanged-customer-token', 'ios', 'customer');

        Sanctum::actingAs($staff);

        $this->putJson("/api/orders/{$order->id}", [
            'status_id' => $pending->id,
        ])->assertOk();

        Queue::assertNotPushed(SendFirebasePushNotification::class);
    }

    public function test_firebase_sender_revokes_invalid_tokens(): void
    {
        $subscription = $this->subscriptionFor($this->makeUser(roleId: 2), 'invalid-token', 'ios', 'pos');

        $sender = new class implements PushNotificationSender
        {
            public function sendToSubscription(PushSubscription $subscription, string $title, string $body, array $data): array
            {
                throw new InvalidPushTokenException('invalid token');
            }
        };

        (new SendFirebasePushNotification(
            $subscription->id,
            123,
            'New web order',
            'Order #123 is waiting for confirmation',
            ['type' => 'web_order_created']
        ))->handle($sender);

        $this->assertNotNull($subscription->refresh()->revoked_at);
    }

    public function test_firebase_failure_does_not_prevent_guest_order_creation(): void
    {
        $this->subscriptionFor($this->makeUser(roleId: 2), 'failing-token', 'ios', 'pos');

        $this->app->bind(PushNotificationSender::class, function () {
            return new class implements PushNotificationSender
            {
                public function sendToSubscription(PushSubscription $subscription, string $title, string $body, array $data): array
                {
                    throw new Exception('firebase unavailable');
                }
            };
        });

        $this->postGuestWebOrder()->assertCreated();
    }

    private function createGuestWebOrder(): int
    {
        return (int) $this->postGuestWebOrder()->assertCreated()->json('id');
    }

    private function postGuestWebOrder()
    {
        Status::query()->firstOrCreate(['name' => 'pending']);
        $category = Category::query()->firstOrCreate(['name' => 'Wraps'], ['sort_order' => 1]);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50, 'active' => true]);
        $customer = Customer::query()->create(['name' => 'Guest Customer', 'source' => 'guest-web']);

        return $this->postJson('/api/guest/orders', [
            'customer_id' => $customer->id,
            'subtotal' => 12.50,
            'service_charge' => 0,
            'total' => 12.50,
            'is_delivery' => false,
            'items' => [
                ['item_id' => $item->id, 'price' => 12.50, 'quantity' => 1],
            ],
        ], [
            'Idempotency-Key' => 'guest-order-'.uniqid(),
        ]);
    }

    private function subscriptionFor(User $user, string $token, string $platform, string $appContext, bool $revoked = false): PushSubscription
    {
        return PushSubscription::query()->create([
            'user_id' => $user->id,
            'provider' => 'firebase',
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'platform' => $platform,
            'app_context' => $appContext,
            'last_seen_at' => now(),
            'revoked_at' => $revoked ? now() : null,
        ]);
    }

    private function makeUser(int $roleId, string $username = 'test-user', string $email = 'test-user@example.com'): User
    {
        return User::query()->create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'username' => $username.'-'.$roleId.'-'.uniqid(),
            'email' => $email.'-'.$roleId.'-'.uniqid(),
            'password' => 'password',
            'role_id' => $roleId,
        ]);
    }
}
