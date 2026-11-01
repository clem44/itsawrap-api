<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCashSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_index_exposes_open_and_close_workflows(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();
        $session = CashSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 100,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee('Open session')
            ->assertSee('name="user_id"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="opening_amount"', false)
            ->assertSee('Close session')
            ->assertSee('name="closing_amount"', false);
    }

    public function test_admin_can_open_a_cash_session(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();

        $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => '125.50',
        ])->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseHas('cash_sessions', [
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 125.50,
            'is_open' => true,
        ]);
    }

    public function test_admin_cannot_open_a_second_session_for_the_same_cashier(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();
        CashSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 100,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 50,
        ])->assertRedirect()
            ->assertSessionHasErrors(['user_id']);

        $this->assertDatabaseCount('cash_sessions', 1);
    }

    public function test_admin_can_close_an_open_cash_session(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();
        $session = CashSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 100,
            'expected_amount' => 245.75,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.sessions.close', $session), [
            'closing_amount' => '247.25',
        ])->assertRedirect(route('admin.sessions.show', $session));

        $session->refresh();
        $this->assertFalse($session->is_open);
        $this->assertSame('247.25', $session->closing_amount);
        $this->assertNotNull($session->closed_at);
    }

    public function test_admin_cannot_close_an_already_closed_session(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();
        $session = CashSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 100,
            'closing_amount' => 200,
            'is_open' => false,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
        ]);

        $this->actingAs($admin)->from(route('admin.sessions.show', $session))
            ->post(route('admin.sessions.close', $session), ['closing_amount' => 250])
            ->assertRedirect(route('admin.sessions.show', $session))
            ->assertSessionHasErrors(['closing_amount']);

        $this->assertSame('200.00', $session->fresh()->closing_amount);
    }

    public function test_open_session_show_page_exposes_close_modal_action(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();
        $session = CashSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 100,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.sessions.show', $session))
            ->assertOk()
            ->assertSee('Close session')
            ->assertSee(route('admin.sessions.close', $session))
            ->assertSee('name="closing_amount"', false);
    }

    public function test_closed_sessions_are_read_only(): void
    {
        [$admin, $cashier, $branch] = $this->scenario();
        $session = CashSession::query()->create([
            'user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_amount' => 100,
            'closing_amount' => 150,
            'is_open' => false,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertDontSee('Close session #'.$session->id)
            ->assertDontSee('Delete Session');

        $this->actingAs($admin)->from(route('admin.sessions.index'))
            ->delete(route('admin.sessions.destroy', $session))
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseHas('cash_sessions', ['id' => $session->id]);
    }

    /**
     * @return array{0: User, 1: User, 2: Branch}
     */
    private function scenario(): array
    {
        $admin = User::query()->create([
            'firstname' => 'Admin',
            'lastname' => 'Owner',
            'username' => 'session-admin',
            'email' => 'session-admin@example.com',
            'password' => 'password',
            'role_id' => Role::ADMIN_ID,
        ]);
        $cashier = User::query()->create([
            'firstname' => 'Taylor',
            'lastname' => 'Cashier',
            'username' => 'session-cashier',
            'email' => 'session-cashier@example.com',
            'password' => 'password',
            'role_id' => Role::STAFF_ID,
        ]);
        $branch = Branch::query()->create([
            'name' => 'Main',
            'active' => true,
        ]);

        return [$admin, $cashier, $branch];
    }
}
