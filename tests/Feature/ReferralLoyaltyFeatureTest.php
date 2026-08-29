<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReferralProgram;
use App\Models\RewardLedgerEntry;
use App\Models\RewardProgram;
use App\Models\Status;
use App\Models\User;
use App\Models\UserReferral;
use App\Models\UserReferralLedgerEntry;
use App\Models\UserReferralRewardAccount;
use App\Services\Referrals\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferralLoyaltyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_without_referral_code_succeeds_and_assigns_new_user_code(): void
    {
        $this->createReferralProgram();

        $response = $this->postJson('/api/guest/register', $this->registrationPayload([
            'email' => 'new-customer@example.com',
        ]));

        $response->assertCreated()
            ->assertJsonPath('referral.accepted', false);

        $user = User::query()->where('email', 'new-customer@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_referral_codes', [
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('user_referrals', 0);
    }

    public function test_registration_with_valid_referral_code_credits_referrer_immediately(): void
    {
        $program = $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('referrer@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);

        $response = $this->postJson('/api/guest/register', $this->registrationPayload([
            'email' => 'referred@example.com',
            'referral_code' => strtolower($code->code),
        ]));

        $response->assertCreated()
            ->assertJsonPath('referral.accepted', true)
            ->assertJsonPath('referral.code', $code->code);

        $referred = User::query()->where('email', 'referred@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_referrals', [
            'referral_program_id' => $program->id,
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'status' => UserReferral::STATUS_QUALIFIED,
        ]);
        $this->assertReferralAccount($referrer, $program, progress: 1, available: 0, lifetimeReferrals: 1, lifetimeEarned: 0);
    }

    public function test_five_registration_referrals_earns_one_referral_reward(): void
    {
        $program = $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('five-referrer@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);

        foreach (range(1, 5) as $index) {
            $this->postJson('/api/guest/register', $this->registrationPayload([
                'email' => "referred-{$index}@example.com",
                'referral_code' => $code->code,
            ]))->assertCreated();
        }

        $this->assertReferralAccount($referrer, $program, progress: 0, available: 1, lifetimeReferrals: 5, lifetimeEarned: 1);
    }

    public function test_invalid_referral_code_returns_validation_error_without_creating_user(): void
    {
        $this->createReferralProgram();

        $this->postJson('/api/guest/register', $this->registrationPayload([
            'email' => 'invalid-code@example.com',
            'referral_code' => 'missing',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('referral_code');

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-code@example.com',
        ]);
    }

    public function test_inactive_referral_code_returns_validation_error_without_creating_user(): void
    {
        $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('inactive-referrer@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);
        $code->update(['is_active' => false]);

        $this->postJson('/api/guest/register', $this->registrationPayload([
            'email' => 'inactive-code@example.com',
            'referral_code' => $code->code,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('referral_code');

        $this->assertDatabaseMissing('users', [
            'email' => 'inactive-code@example.com',
        ]);
    }

    public function test_self_referral_is_rejected(): void
    {
        $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('self-referrer@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);

        $this->expectException(ValidationException::class);

        app(ReferralService::class)->recordRegistrationReferral($referrer, $code->code);
    }

    public function test_duplicate_referral_attribution_is_rejected(): void
    {
        $program = $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('duplicate-referrer@example.com');
        $referred = $this->makeCustomerUser('duplicate-referred@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);

        try {
            app(ReferralService::class)->recordRegistrationReferral($referred, $code->code);
            app(ReferralService::class)->recordRegistrationReferral($referred, $code->code);
        } catch (ValidationException) {
            $this->assertDatabaseCount('user_referrals', 1);
            $this->assertReferralAccount($referrer, $program, progress: 1, available: 0, lifetimeReferrals: 1, lifetimeEarned: 0);

            return;
        }

        $this->fail('Duplicate referral validation exception was not thrown.');
    }

    public function test_six_registration_referrals_earns_one_reward_and_carries_progress(): void
    {
        $program = $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('six-referrer@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);

        foreach (range(1, 6) as $index) {
            $this->postJson('/api/guest/register', $this->registrationPayload([
                'email' => "six-referred-{$index}@example.com",
                'referral_code' => $code->code,
            ]))->assertCreated();
        }

        $this->assertReferralAccount($referrer, $program, progress: 1, available: 1, lifetimeReferrals: 6, lifetimeEarned: 1);
    }

    public function test_reward_summary_contains_independent_purchase_and_referral_loyalty(): void
    {
        $category = Category::query()->create(['name' => 'Wraps', 'sort_order' => 1]);
        $purchaseProgram = RewardProgram::query()->create([
            'name' => 'Buy 6 Get 1 Free',
            'is_active' => true,
            'earn_category_id' => $category->id,
            'qualifying_item_quantity_required' => 6,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
        ]);
        $referralProgram = ReferralProgram::query()->create([
            'name' => 'Referral Loyalty',
            'is_active' => true,
            'required_referrals' => 5,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
        ]);
        $user = $this->makeCustomerUser('summary@example.com');
        RewardLedgerEntry::query()->create([
            'customer_id' => $user->customer->id,
            'reward_program_id' => $purchaseProgram->id,
            'type' => RewardLedgerEntry::TYPE_ADJUSTED,
            'progress_delta' => 4,
            'rewards_delta' => 1,
            'reason' => 'test_adjustment',
        ]);
        $this->createReferralProgress($user, $referralProgram, 3);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/rewards')
            ->assertOk()
            ->assertJsonPath('programs.0.name', 'Buy 6 Get 1 Free')
            ->assertJsonPath('purchase_loyalty.programs.0.name', 'Buy 6 Get 1 Free')
            ->assertJsonPath('referral_loyalty.name', 'Referral Loyalty')
            ->assertJsonPath('referral_loyalty.required_referrals', 5)
            ->assertJsonPath('referral_loyalty.qualified_referrals', 3)
            ->assertJsonPath('referral_loyalty.rewards_available', 0)
            ->assertJsonPath('total_rewards_available', 1);
    }

    public function test_customer_can_fetch_referral_code(): void
    {
        $user = $this->makeCustomerUser('referral-code@example.com');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/referral-code')
            ->assertOk()
            ->assertJsonStructure(['code', 'share_url']);

        $this->assertDatabaseHas('user_referral_codes', [
            'user_id' => $user->id,
            'code' => $response->json('code'),
            'is_active' => true,
        ]);
    }

    public function test_referral_reward_redemption_decrements_only_referral_balance(): void
    {
        $category = Category::query()->create(['name' => 'Wraps']);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50]);
        $program = ReferralProgram::query()->create([
            'name' => 'Referral Loyalty',
            'is_active' => true,
            'required_referrals' => 5,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
        ]);
        $user = $this->makeCustomerUser('redeem-referral@example.com');
        $status = Status::query()->create(['name' => 'pending']);
        $order = Order::query()->create([
            'customer_id' => $user->customer->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'total' => 12.50,
        ]);
        $this->createReferralProgress($user, $program, 5);

        Sanctum::actingAs($this->makeStaffUser('staff-referral-redeem@example.com'));

        $this->postJson("/api/orders/{$order->id}/referral-rewards/redeem", [
            'referral_program_id' => $program->id,
            'item_id' => $item->id,
        ])
            ->assertCreated()
            ->assertJsonPath('referral_program_id', $program->id);

        $orderItem = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('0.00', (string) $orderItem->price);
        $this->assertSame($program->id, $orderItem->referral_program_id);
        $this->assertReferralAccount($user, $program, progress: 0, available: 0, lifetimeReferrals: 5, lifetimeEarned: 1, lifetimeRedeemed: 1);
        $this->assertDatabaseCount('customer_reward_accounts', 0);
    }

    public function test_referral_reward_redemption_respects_program_reward_quantity(): void
    {
        $category = Category::query()->create(['name' => 'Wraps']);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50]);
        $program = ReferralProgram::query()->create([
            'name' => 'Referral Loyalty',
            'is_active' => true,
            'required_referrals' => 5,
            'reward_category_id' => $category->id,
            'reward_quantity' => 2,
        ]);
        $user = $this->makeCustomerUser('redeem-referral-quantity@example.com');
        $status = Status::query()->create(['name' => 'pending']);
        $order = Order::query()->create([
            'customer_id' => $user->customer->id,
            'status_id' => $status->id,
            'subtotal' => 25,
            'total' => 25,
        ]);
        $this->createReferralProgress($user, $program, 5);

        Sanctum::actingAs($this->makeStaffUser('staff-referral-quantity@example.com'));

        $this->postJson("/api/orders/{$order->id}/referral-rewards/redeem", [
            'referral_program_id' => $program->id,
            'item_id' => $item->id,
            'quantity' => 3,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        $this->postJson("/api/orders/{$order->id}/referral-rewards/redeem", [
            'referral_program_id' => $program->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ])->assertCreated();

        $orderItem = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame(2, $orderItem->quantity);
        $this->assertSame('25.00', (string) $orderItem->reward_discount_amount);
        $this->assertReferralAccount($user, $program, progress: 0, available: 0, lifetimeReferrals: 5, lifetimeEarned: 2, lifetimeRedeemed: 2);
    }

    public function test_admin_can_configure_referral_program(): void
    {
        $category = Category::query()->create(['name' => 'Wraps']);
        $admin = $this->makeStaffUser('admin-referral-config@example.com');

        $this->actingAs($admin)
            ->post(route('admin.referral-rewards.store'), [
                'name' => 'Referral Loyalty',
                'required_referrals' => 5,
                'reward_category_id' => $category->id,
                'reward_quantity' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.rewards.index'));

        $program = ReferralProgram::query()->firstOrFail();

        $this->assertSame('Referral Loyalty', $program->name);
        $this->assertTrue($program->is_active);

        $this->actingAs($admin)
            ->put(route('admin.referral-rewards.update', $program), [
                'name' => 'Referral Loyalty Updated',
                'required_referrals' => 6,
                'reward_category_id' => $category->id,
                'reward_quantity' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.rewards.index'));

        $this->assertDatabaseHas('referral_programs', [
            'id' => $program->id,
            'name' => 'Referral Loyalty Updated',
            'required_referrals' => 6,
        ]);
    }

    public function test_admin_can_view_customer_referral_summary_and_adjust_balance(): void
    {
        $program = $this->createReferralProgram();
        $customerUser = $this->makeCustomerUser('admin-referral-summary@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($customerUser);
        $admin = $this->makeStaffUser('admin-referral-adjust@example.com');

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customerUser->customer))
            ->assertOk()
            ->assertSee('Referral Loyalty')
            ->assertSee($code->code);

        $this->actingAs($admin)
            ->post(route('admin.customers.referral-adjustments.store', $customerUser->customer), [
                'referral_program_id' => $program->id,
                'progress_delta' => 2,
                'rewards_delta' => 1,
                'note' => 'service recovery',
            ])
            ->assertRedirect(route('admin.customers.show', $customerUser->customer));

        $this->assertDatabaseHas('user_referral_ledger_entries', [
            'user_id' => $customerUser->id,
            'referral_program_id' => $program->id,
            'type' => UserReferralLedgerEntry::TYPE_ADJUSTED,
            'progress_delta' => 2,
            'rewards_delta' => 1,
            'reason' => 'admin_adjustment',
        ]);
        $this->assertReferralAccount($customerUser, $program, progress: 2, available: 1, lifetimeReferrals: 2, lifetimeEarned: 0);
    }

    public function test_admin_can_reverse_referral_with_reason(): void
    {
        $program = $this->createReferralProgram();
        $referrer = $this->makeCustomerUser('admin-reverse-referrer@example.com');
        $code = app(ReferralService::class)->ensureCodeForUser($referrer);
        $admin = $this->makeStaffUser('admin-referral-reverse@example.com');

        $this->postJson('/api/guest/register', $this->registrationPayload([
            'email' => 'admin-reverse-referred@example.com',
            'referral_code' => $code->code,
        ]))->assertCreated();

        $referral = UserReferral::query()->where('referral_program_id', $program->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.customers.referrals.reverse', [$referrer->customer, $referral]), [
                'reason' => 'duplicate account',
            ])
            ->assertRedirect(route('admin.customers.show', $referrer->customer));

        $this->assertDatabaseHas('user_referrals', [
            'id' => $referral->id,
            'status' => UserReferral::STATUS_REVERSED,
            'reversal_reason' => 'duplicate account',
        ]);
        $this->assertDatabaseHas('user_referral_ledger_entries', [
            'user_id' => $referrer->id,
            'referral_program_id' => $program->id,
            'type' => UserReferralLedgerEntry::TYPE_REVERSED,
            'progress_delta' => -1,
            'reason' => 'referral_reversed',
        ]);
        $this->assertReferralAccount($referrer, $program, progress: 0, available: 0, lifetimeReferrals: 0, lifetimeEarned: 0);
    }

    public function test_reversing_referral_after_redemption_records_clawback_shortfall(): void
    {
        $category = Category::query()->create(['name' => 'Wraps']);
        $item = Item::query()->create(['name' => 'Chicken Wrap', 'category_id' => $category->id, 'cost' => 12.50]);
        $program = ReferralProgram::query()->create([
            'name' => 'Referral Loyalty',
            'is_active' => true,
            'required_referrals' => 5,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
        ]);
        $referrer = $this->makeCustomerUser('shortfall-referrer@example.com');
        $status = Status::query()->create(['name' => 'pending']);
        $order = Order::query()->create([
            'customer_id' => $referrer->customer->id,
            'status_id' => $status->id,
            'subtotal' => 12.50,
            'total' => 12.50,
        ]);
        $this->createReferralProgress($referrer, $program, 5);

        $staff = $this->makeStaffUser('staff-shortfall@example.com');
        Sanctum::actingAs($staff);

        $this->postJson("/api/orders/{$order->id}/referral-rewards/redeem", [
            'referral_program_id' => $program->id,
            'item_id' => $item->id,
        ])->assertCreated();

        $referral = UserReferral::query()
            ->where('referrer_user_id', $referrer->id)
            ->where('referral_program_id', $program->id)
            ->firstOrFail();

        app(ReferralService::class)->reverseReferral($referral, 'duplicate account', $staff);

        $reversal = UserReferralLedgerEntry::query()
            ->where('user_referral_id', $referral->id)
            ->where('type', UserReferralLedgerEntry::TYPE_REVERSED)
            ->firstOrFail();

        $this->assertSame(1, $reversal->metadata['reward_clawback_shortfall']);
        $this->assertReferralAccount($referrer, $program, progress: 4, available: 0, lifetimeReferrals: 4, lifetimeEarned: 0, lifetimeRedeemed: 1);
    }

    private function registrationPayload(array $overrides = []): array
    {
        $email = $overrides['email'] ?? 'customer@example.com';

        return array_merge([
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'email' => $email,
            'phone' => '555-0100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'itsawrapweb',
        ], $overrides);
    }

    private function createReferralProgram(): ReferralProgram
    {
        $category = Category::query()->create(['name' => 'Wraps']);

        return ReferralProgram::query()->create([
            'name' => 'Referral Loyalty',
            'is_active' => true,
            'required_referrals' => 5,
            'reward_category_id' => $category->id,
            'reward_quantity' => 1,
        ]);
    }

    private function makeCustomerUser(string $email): User
    {
        $user = User::query()->create([
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'username' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => 3,
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => 'Alex Carter',
            'firstname' => 'Alex',
            'lastname' => 'Carter',
            'email' => $email,
            'source' => 'web-customer',
        ]);

        return $user->refresh();
    }

    private function makeStaffUser(string $email): User
    {
        return User::query()->create([
            'firstname' => 'Staff',
            'lastname' => 'User',
            'username' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => 1,
        ]);
    }

    private function assertReferralAccount(
        User $user,
        ReferralProgram $program,
        int $progress,
        int $available,
        int $lifetimeReferrals,
        int $lifetimeEarned,
        int $lifetimeRedeemed = 0
    ): void {
        $account = UserReferralRewardAccount::query()
            ->where('user_id', $user->id)
            ->where('referral_program_id', $program->id)
            ->firstOrFail();

        $this->assertSame($progress, $account->progress_quantity);
        $this->assertSame($available, $account->rewards_available);
        $this->assertSame($lifetimeReferrals, $account->lifetime_qualified_referrals);
        $this->assertSame($lifetimeEarned, $account->lifetime_rewards_earned);
        $this->assertSame($lifetimeRedeemed, $account->lifetime_rewards_redeemed);
    }

    private function createReferralProgress(User $user, ReferralProgram $program, int $count): void
    {
        foreach (range(1, $count) as $index) {
            $referredUser = $this->makeCustomerUser("ledger-referred-{$user->id}-{$index}@example.com");

            $referral = UserReferral::query()->create([
                'referral_program_id' => $program->id,
                'referrer_user_id' => $user->id,
                'referred_user_id' => $referredUser->id,
                'status' => UserReferral::STATUS_QUALIFIED,
                'qualified_at' => now(),
            ]);

            UserReferralLedgerEntry::query()->create([
                'user_id' => $user->id,
                'referral_program_id' => $program->id,
                'user_referral_id' => $referral->id,
                'type' => UserReferralLedgerEntry::TYPE_QUALIFIED_REFERRAL,
                'progress_delta' => 1,
                'rewards_delta' => 0,
                'reason' => 'referred_user_registered',
            ]);
        }

        app(ReferralService::class)->rebuildAccount($user->id, $program);
    }
}
