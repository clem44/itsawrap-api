<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Status;
use App\Services\Rewards\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['customer', 'status'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'status', 'orderItems.item', 'payments']);
        $statuses = Status::query()->orderBy('id')->get();

        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function update(Request $request, Order $order, RewardService $rewards): RedirectResponse
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $oldStatusName = $order->status?->name;

        $order->update([
            'status_id' => $validated['status_id'],
        ]);

        $order->refresh()->load('status');

        $rewards->recordEligibleOrderRewards($order);

        if ($oldStatusName !== 'cancelled' && $order->status?->name === 'cancelled') {
            $rewards->reverseOrder($order, 'order_cancelled', $request->user());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    public function destroy(Request $request, Order $order, RewardService $rewards): RedirectResponse
    {
        $rewards->reverseOrder($order, 'order_deleted', $request->user());

        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', 'Order deleted successfully.');
    }
}
