<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuestStoreStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('open_time', '11:00:00');
        Setting::setValue('close_time', '21:30:00');

        $this->travelTo(Carbon::now('America/Anguilla')->setTimeFromTimeString('14:00:00'));
    }

    protected function branch(string $name = 'Main'): Branch
    {
        return Branch::query()->create(['name' => $name, 'active' => true]);
    }

    protected function cashier(): User
    {
        return User::query()->create([
            'firstname' => 'Taylor',
            'lastname' => 'Cashier',
            'username' => 'store-status-cashier-'.uniqid(),
            'email' => 'store-status-cashier-'.uniqid().'@example.com',
            'password' => 'password',
            'role_id' => Role::STAFF_ID,
        ]);
    }

    public function test_it_requires_no_authentication(): void
    {
        $branch = $this->branch();
        CashSession::query()->create([
            'user_id' => $this->cashier()->id,
            'branch_id' => $branch->id,
            'opening_amount' => 0,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/guest/store-status')
            ->assertOk()
            ->assertJson(['is_open' => true, 'open_time' => '11:00:00', 'close_time' => '21:30:00']);
    }

    public function test_it_is_closed_without_an_open_main_branch_session(): void
    {
        $this->getJson('/api/guest/store-status')
            ->assertOk()
            ->assertJson(['is_open' => false]);
    }

    public function test_it_is_closed_outside_hours_even_with_an_open_session(): void
    {
        $branch = $this->branch();
        CashSession::query()->create([
            'user_id' => $this->cashier()->id,
            'branch_id' => $branch->id,
            'opening_amount' => 0,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->travelTo(Carbon::now('America/Anguilla')->setTimeFromTimeString('22:00:00'));

        $this->getJson('/api/guest/store-status')
            ->assertOk()
            ->assertJson(['is_open' => false]);
    }

    public function test_it_ignores_a_session_open_for_a_different_branch(): void
    {
        $branch = $this->branch('Airport');
        CashSession::query()->create([
            'user_id' => $this->cashier()->id,
            'branch_id' => $branch->id,
            'opening_amount' => 0,
            'is_open' => true,
            'opened_at' => now(),
        ]);

        $this->getJson('/api/guest/store-status')
            ->assertOk()
            ->assertJson(['is_open' => false]);
    }
}
