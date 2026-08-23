<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerRewardAccount;
use App\Models\Item;
use App\Models\RewardLedgerEntry;
use App\Models\RewardProgram;
use App\Models\Setting;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RewardsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_order_with_six_paid_wraps_earns_one_reward(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer, $completed] = $this->rewardFixture();

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $completed->id,
            'subtotal' => 75,
            'service_charge' => 0,
            'total' => 75,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 6],
            ],
        ])->assertCreated();

        $this->assertRewardAccount($customer, $program, progress: 0, available: 1, lifetimeQuantity: 6, lifetimeEarned: 1);
    }

    public function test_completed_order_with_thirteen_paid_wraps_earns_two_rewards_and_carries_progress(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer, $completed] = $this->rewardFixture();

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $completed->id,
            'subtotal' => 162.50,
            'service_charge' => 0,
            'total' => 162.50,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 13],
            ],
        ])->assertCreated();

        $this->assertRewardAccount($customer, $program, progress: 1, available: 2, lifetimeQuantity: 13, lifetimeEarned: 2);
    }

    public function test_reward_wrap_does_not_count_toward_next_reward(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer, $completed] = $this->rewardFixture();

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $completed->id,
            'subtotal' => 75,
            'service_charge' => 0,
            'total' => 75,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 6],
            ],
        ])->assertCreated();

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $completed->id,
            'subtotal' => 0,
            'service_charge' => 0,
            'total' => 0,
            'items' => [
                [
                    'item_id' => $wrap->id,
                    'price' => 12.50,
                    'quantity' => 1,
                    'is_reward_item' => true,
                    'reward_program_id' => $program->id,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('order_items.0.is_reward_item', true)
            ->assertJsonPath('order_items.0.price', '0.00');

        $this->assertRewardAccount($customer, $program, progress: 0, available: 0, lifetimeQuantity: 6, lifetimeEarned: 1, lifetimeRedeemed: 1);
        $this->assertDatabaseCount('reward_ledger_entries', 2);
    }

    public function test_completing_the_same_order_twice_does_not_duplicate_rewards(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer, $completed] = $this->rewardFixture();
        $pending = Status::query()->create(['name' => 'pending']);

        $orderId = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $pending->id,
            'subtotal' => 75,
            'service_charge' => 0,
            'total' => 75,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 6],
            ],
        ])->assertCreated()->json('id');

        $this->putJson("/api/orders/{$orderId}", ['status_id' => $completed->id])->assertOk();
        $this->putJson("/api/orders/{$orderId}", ['status_id' => $completed->id])->assertOk();

        $this->assertRewardAccount($customer, $program, progress: 0, available: 1, lifetimeQuantity: 6, lifetimeEarned: 1);
        $this->assertDatabaseCount('reward_ledger_entries', 1);
    }

    public function test_activating_order_records_reward_progress(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer] = $this->rewardFixture();
        $pending = Status::query()->create(['name' => 'pending']);
        $active = Status::query()->create(['name' => 'active']);

        $orderId = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $pending->id,
            'subtotal' => 75,
            'service_charge' => 0,
            'total' => 75,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 6],
            ],
        ])->assertCreated()->json('id');

        $this->putJson("/api/orders/{$orderId}", ['status_id' => $active->id])->assertOk();

        $this->assertRewardAccount($customer, $program, progress: 0, available: 1, lifetimeQuantity: 6, lifetimeEarned: 1);
        $this->assertDatabaseHas('reward_ledger_entries', [
            'order_id' => $orderId,
            'type' => RewardLedgerEntry::TYPE_EARNED_PROGRESS,
            'progress_delta' => 6,
            'reason' => 'order_completed',
        ]);
    }

    public function test_cancelled_order_reverses_reward_progress(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer, $completed] = $this->rewardFixture();
        $cancelled = Status::query()->create(['name' => 'cancelled']);

        $orderId = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $completed->id,
            'subtotal' => 75,
            'service_charge' => 0,
            'total' => 75,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 6],
            ],
        ])->assertCreated()->json('id');

        $this->putJson("/api/orders/{$orderId}", ['status_id' => $cancelled->id])->assertOk();

        $this->assertRewardAccount($customer, $program, progress: 0, available: 0, lifetimeQuantity: 0, lifetimeEarned: 0);
        $this->assertDatabaseHas('reward_ledger_entries', [
            'order_id' => $orderId,
            'type' => RewardLedgerEntry::TYPE_REVERSED,
            'progress_delta' => -6,
            'reason' => 'order_cancelled',
        ]);
    }

    public function test_customer_reward_summary_endpoint_returns_current_balance(): void
    {
        Sanctum::actingAs($this->makeUser());
        [$program, $wrap, $customer, $completed] = $this->rewardFixture();

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'status_id' => $completed->id,
            'subtotal' => 50,
            'service_charge' => 0,
            'total' => 50,
            'items' => [
                ['item_id' => $wrap->id, 'price' => 12.50, 'quantity' => 4],
            ],
        ])->assertCreated();

        $this->getJson("/api/customers/{$customer->id}/rewards")
            ->assertOk()
            ->assertJsonPath('rewards_enabled', true)
            ->assertJsonPath('programs.0.program_id', $program->id)
            ->assertJsonPath('programs.0.progress_quantity', 4)
            ->assertJsonPath('programs.0.rewards_available', 0);
    }

    /**
     * @return array{0: RewardProgram, 1: Item, 2: Customer, 3: Status}
     */
    private function rewardFixture(): array
    {
        Setting::query()->create(['key' => 'rewards_enabled', 'value' => 'true']);

        $category = Category::query()->create(['name' => 'Wraps']);
        $wrap = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50]);
        $customer = Customer::query()->create(['name' => 'Rewards Customer']);
        $completed = Status::query()->create(['name' => 'completed']);
        $program = RewardProgram::query()->create([
            'name' => 'Buy 6 Wraps, Get 1 Free Wrap',
            'earn_category_id' => $category->id,
            'qualifying_item_quantity_required' => 6,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
            'is_active' => true,
        ]);

        return [$program, $wrap, $customer, $completed];
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'firstname' => 'API',
            'lastname' => 'User',
            'username' => 'reward-user',
            'email' => 'reward-user@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }

    private function assertRewardAccount(
        Customer $customer,
        RewardProgram $program,
        int $progress,
        int $available,
        int $lifetimeQuantity,
        int $lifetimeEarned,
        int $lifetimeRedeemed = 0
    ): void {
        $account = CustomerRewardAccount::query()
            ->where('customer_id', $customer->id)
            ->where('reward_program_id', $program->id)
            ->firstOrFail();

        $this->assertSame($progress, $account->progress_quantity);
        $this->assertSame($available, $account->rewards_available);
        $this->assertSame($lifetimeQuantity, $account->lifetime_qualifying_quantity);
        $this->assertSame($lifetimeEarned, $account->lifetime_rewards_earned);
        $this->assertSame($lifetimeRedeemed, $account->lifetime_rewards_redeemed);
    }
}
