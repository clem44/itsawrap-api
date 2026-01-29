<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tip;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = CashSession::query()
            ->with('user')
            ->orderByDesc('opened_at')
            ->take(50)
            ->get();

        $selectedSession = null;
        if ($request->filled('session_id')) {
            $selectedSession = CashSession::with('user')->find($request->input('session_id'));
        }

        if (!$selectedSession) {
            $selectedSession = $sessions->first();
        }

        $stats = [
            'service_charge_cash' => 0,
            'service_charge_card' => 0,
            'service_charge_delivery' => 0,
            'revenue' => 0,
            'total_tips' => 0,
            'total_orders' => 0,
            'total_withdrawals' => 0,
            'sales_card' => 0,
            'sales_cash' => 0,
        ];

        if ($selectedSession) {
            $sessionId = $selectedSession->id;

            $ordersQuery = Order::query()->where('session_id', $sessionId);
            $stats['revenue'] = $ordersQuery->sum('total');
            $stats['total_orders'] = $ordersQuery->count();
            $stats['service_charge_delivery'] = (clone $ordersQuery)
                ->where('is_delivery', true)
                ->sum('service_charge');

            $stats['total_tips'] = Tip::query()
                ->whereHas('order', function ($query) use ($sessionId) {
                    $query->where('session_id', $sessionId);
                })
                ->sum('amount');

            $stats['total_withdrawals'] = Withdrawal::query()
                ->where('session_id', $sessionId)
                ->sum('amount');

            $paymentsQuery = Payment::query()
                ->whereHas('order', function ($query) use ($sessionId) {
                    $query->where('session_id', $sessionId);
                });

            $cashPayments = (clone $paymentsQuery)->where(function ($query) {
                $query->where('method', 'cash')
                    ->orWhere('method', 'like', '%cash%');
            });

            $cardPayments = (clone $paymentsQuery)->where(function ($query) {
                $query->where('method', 'like', '%card%')
                    ->orWhere('method', 'like', '%credit%');
            });

            $stats['sales_cash'] = $cashPayments->sum('amount');
            $stats['sales_card'] = $cardPayments->sum('amount');

            $cashOrderIds = $cashPayments->distinct()->pluck('order_id');
            $cardOrderIds = $cardPayments->distinct()->pluck('order_id');

            $stats['service_charge_cash'] = Order::query()
                ->whereIn('id', $cashOrderIds)
                ->sum('service_charge');

            $stats['service_charge_card'] = Order::query()
                ->whereIn('id', $cardOrderIds)
                ->sum('service_charge');
        }

        return view('admin.reports', compact('sessions', 'selectedSession', 'stats'));
    }
}
