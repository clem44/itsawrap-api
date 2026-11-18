<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_page_exposes_the_payment_modal_with_the_order_total_prefilled(): void
    {
        $order = $this->makeOrder();
        Payment::query()->create([
            'order_id' => $order->id,
            'amount' => 10,
            'method' => 'card',
            'card_last_four' => '4242',
            'status' => 'completed',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Add Payment')
            ->assertSee(route('admin.orders.payments.store', $order), false)
            ->assertSee("amount: '24.50'", false)
            ->assertSee('ending in 4242');
    }

    public function test_admin_can_add_a_cash_payment_to_an_order(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.orders.payments.store', $order), [
                'amount' => '24.50',
                'method' => 'cash',
                'card_last_four' => '9999',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success', 'Payment added successfully.');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 24.50,
            'method' => 'cash',
            'card_last_four' => null,
            'status' => 'completed',
            'type' => 'admin',
        ]);
    }

    public function test_admin_can_add_a_card_payment_with_the_last_four_digits(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.orders.payments.store', $order), [
                'amount' => '24.50',
                'method' => 'card',
                'card_last_four' => '4242',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'card',
            'card_last_four' => '4242',
            'status' => 'completed',
        ]);
    }

    public function test_card_payments_require_exactly_four_digits(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->makeAdmin())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.payments.store', $order), [
                'amount' => '24.50',
                'method' => 'card',
                'card_last_four' => '42A',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors(['card_last_four'], errorBag: 'payment');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_amount_must_be_positive_and_have_at_most_two_decimal_places(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->makeAdmin())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.payments.store', $order), [
                'amount' => '0.001',
                'method' => 'cash',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors(['amount'], errorBag: 'payment');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_guests_cannot_add_payments(): void
    {
        $order = $this->makeOrder();

        $this->post(route('admin.orders.payments.store', $order), [
            'amount' => '24.50',
            'method' => 'cash',
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('payments', 0);
    }

    private function makeAdmin(): User
    {
        return User::query()->create([
            'firstname' => 'Payment',
            'lastname' => 'Admin',
            'username' => 'payment-admin',
            'email' => 'payment-admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }

    private function makeOrder(): Order
    {
        $status = Status::query()->create(['name' => 'pending']);
        $customer = Customer::query()->create(['name' => 'Payment Customer']);

        return Order::query()->create([
            'number' => 'ORD-PAYMENT',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subtotal' => 24.50,
            'total' => 24.50,
        ]);
    }
}
