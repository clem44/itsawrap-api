<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class DashboardController extends Controller
{
    public function index(): View
    {
        $deviceWindowMinutes = (int) config('sanctum.device_activity_window', 15);
        $activeDeviceCutoff = now()->subMinutes($deviceWindowMinutes);

        $stats = [
            'total_users' => User::count(),
            'admin_users' => User::where('role_id', 1)->count(),
            'total_orders' => Order::count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'active_devices' => PersonalAccessToken::where('tokenable_type', User::class)
                ->whereNotNull('last_used_at')
                ->where('last_used_at', '>=', $activeDeviceCutoff)
                ->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $activeDevices = PersonalAccessToken::with('tokenable')
            ->where('tokenable_type', User::class)
            ->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', $activeDeviceCutoff)
            ->latest('last_used_at')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'activeDevices', 'deviceWindowMinutes'));
    }
}
