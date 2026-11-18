<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function store(StoreOrderPaymentRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $order->payments()->create([
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'card_last_four' => $validated['card_last_four'] ?? null,
            'status' => 'completed',
            'type' => 'admin',
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Payment added successfully.');
    }

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['order.customer', 'order.status'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.customer', 'order.status']);

        return view('admin.payments.show', compact('payment'));
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}
