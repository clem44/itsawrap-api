<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionValue;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Order;
use App\Models\RewardLedgerEntry;
use App\Models\RewardProgram;
use App\Models\Status;
use App\Models\User;
use App\Services\Rewards\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerOrderRewardRedemptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A wrap's cost lives in its options, so a redeemed wrap has to take the
     * whole line off the bill — not the 0.00 base price.
     */
    public function test_redeeming_on_a_new_order_discounts_the_whole_line(): void
    {
        [$user, $customer, $program, $wrap, $chicken] = $this->scenario();
        $this->grantReward($customer, $program);

        Sanctum::actingAs($user);

        $this->postJson('/api/me/orders', $this->orderPayload($wrap, $chicken, $program))
            ->assertCreated();

        $orderItem = Order::query()->latest('id')->first()->orderItems()->first();

        $this->assertTrue((bool) $orderItem->is_reward_item);
        $this->assertSame(12.0, (float) $orderItem->reward_discount_amount);
        $this->assertSame(0.0, $orderItem->lineTotal());
        $this->assertSame(12.0, $orderItem->undiscountedLineTotal());
    }

    public function test_redeeming_spends_the_customers_balance(): void
    {
        [$user, $customer, $program, $wrap, $chicken] = $this->scenario();
        $this->grantReward($customer, $program);

        Sanctum::actingAs($user);

        $this->postJson('/api/me/orders', $this->orderPayload($wrap, $chicken, $program))
            ->assertCreated();

        $this->assertDatabaseHas('reward_ledger_entries', [
            'customer_id' => $customer->id,
            'reward_program_id' => $program->id,
            'type' => RewardLedgerEntry::TYPE_REDEEMED,
            'rewards_delta' => -1,
        ]);

        $this->assertSame(0, (int) app(RewardService::class)
            ->rebuildAccount($customer->id, $program)
            ->rewards_available);
    }

    public function test_it_refuses_a_redemption_the_customer_has_not_earned(): void
    {
        [$user, , $program, $wrap, $chicken] = $this->scenario();

        Sanctum::actingAs($user);

        $this->postJson('/api/me/orders', $this->orderPayload($wrap, $chicken, $program))
            ->assertStatus(422);

        // The whole order rolls back rather than letting a free wrap through.
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_refuses_a_reward_on_an_item_outside_the_reward_category(): void
    {
        [$user, $customer, $program] = $this->scenario();
        $this->grantReward($customer, $program);

        $sides = Category::query()->create(['name' => 'Sides', 'sort_order' => 2]);
        $fries = Item::query()->create(['name' => 'Fries', 'category_id' => $sides->id, 'cost' => 4, 'active' => true]);

        Sanctum::actingAs($user);

        $this->postJson('/api/me/orders', [
            'subtotal' => 0,
            'total' => 0,
            'is_delivery' => false,
            'items' => [[
                'item_id' => $fries->id,
                'price' => 4,
                'quantity' => 1,
                'is_reward_item' => true,
                'reward_program_id' => $program->id,
            ]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_ordinary_line_is_untouched(): void
    {
        [$user, , , $wrap, $chicken] = $this->scenario();

        Sanctum::actingAs($user);

        $this->postJson('/api/me/orders', [
            'subtotal' => 12,
            'total' => 12,
            'is_delivery' => false,
            'items' => [[
                'item_id' => $wrap->id,
                'price' => 0,
                'quantity' => 1,
                'options' => [['option_value_id' => $chicken->option_value_id, 'price' => 12, 'qty' => 1]],
            ]],
        ])->assertCreated();

        $orderItem = Order::query()->latest('id')->first()->orderItems()->first();

        $this->assertFalse((bool) $orderItem->is_reward_item);
        $this->assertSame(12.0, $orderItem->lineTotal());
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Item $wrap, ItemOptionValue $chicken, RewardProgram $program): array
    {
        return [
            'subtotal' => 0,
            'total' => 0,
            'is_delivery' => false,
            'items' => [[
                'item_id' => $wrap->id,
                'price' => 0,
                'quantity' => 1,
                'is_reward_item' => true,
                'reward_program_id' => $program->id,
                'options' => [['option_value_id' => $chicken->option_value_id, 'price' => 12, 'qty' => 1]],
            ]],
        ];
    }

    private function grantReward(Customer $customer, RewardProgram $program): void
    {
        // Six qualifying wraps is one free wrap.
        for ($i = 0; $i < 6; $i++) {
            RewardLedgerEntry::query()->create([
                'customer_id' => $customer->id,
                'reward_program_id' => $program->id,
                'type' => RewardLedgerEntry::TYPE_EARNED_PROGRESS,
                'progress_delta' => 1,
                'rewards_delta' => 0,
                'reason' => 'order_completed',
            ]);
        }

        app(RewardService::class)->rebuildAccount($customer->id, $program);
    }

    /**
     * @return array{0: User, 1: Customer, 2: RewardProgram, 3: Item, 4: ItemOptionValue}
     */
    private function scenario(): array
    {
        Status::query()->create(['name' => 'pending']);

        $user = User::query()->create([
            'firstname' => 'Claude',
            'lastname' => 'Gumbs',
            'username' => 'claude',
            'email' => 'claude@example.com',
            'password' => 'password',
            'role_id' => 3,
        ]);

        $customer = Customer::query()->create([
            'name' => 'Claude Gumbs',
            'email' => 'claude@example.com',
            'source' => 'web-customer',
            'user_id' => $user->id,
        ]);

        $wraps = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $wrap = Item::query()->create(['name' => 'Spinach Wrap', 'category_id' => $wraps->id, 'cost' => 0, 'active' => true]);

        $protein = Option::query()->create(['name' => 'Protein']);
        $chickenValue = OptionValue::query()->create(['option_id' => $protein->id, 'name' => 'Chicken', 'price' => 12]);
        $itemOption = ItemOption::query()->create(['item_id' => $wrap->id, 'option_id' => $protein->id, 'required' => true]);
        $chicken = ItemOptionValue::query()->create([
            'item_option_id' => $itemOption->id,
            'option_value_id' => $chickenValue->id,
            'price' => 12,
        ]);

        $program = RewardProgram::query()->create([
            'name' => 'Buy 6 Wraps, Get 1 Free Wrap',
            'is_active' => true,
            'earn_category_id' => $wraps->id,
            'reward_category_id' => $wraps->id,
            'qualifying_item_quantity_required' => 6,
            'reward_quantity' => 1,
        ]);

        return [$user, $customer, $program, $wrap, $chicken];
    }
}
