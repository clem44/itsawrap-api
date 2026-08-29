<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ReferralProgram;
use App\Models\RewardLedgerEntry;
use App\Models\RewardProgram;
use App\Models\UserReferralLedgerEntry;
use App\Services\Referrals\ReferralService;
use App\Services\Rewards\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->withCount('orders');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        Customer::create($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer, RewardService $rewards, ReferralService $referrals): View
    {
        $customer->load('user.referralCode')->loadCount('orders');
        $orders = $customer->orders()
            ->with('status')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSpent = $orders->sum('total');
        $lastOrderAt = $orders->first()?->created_at;
        $rewardSummary = $rewards->summaryForCustomer($customer);
        $rewardPrograms = RewardProgram::query()
            ->currentlyActive()
            ->orderBy('name')
            ->get();
        $rewardLedgerEntries = RewardLedgerEntry::query()
            ->where('customer_id', $customer->id)
            ->with('rewardProgram')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();
        $referralSummary = $customer->user ? $referrals->summaryForUser($customer->user) : null;
        $referralPrograms = ReferralProgram::query()
            ->currentlyActive()
            ->orderBy('name')
            ->get();
        $referralLedgerEntries = $customer->user
            ? UserReferralLedgerEntry::query()
                ->where('user_id', $customer->user->id)
                ->with('referralProgram')
                ->orderByDesc('created_at')
                ->limit(25)
                ->get()
            : collect();
        $referralsMade = $customer->user
            ? $customer->user->referralsMade()
                ->with(['referralProgram', 'referred.customer'])
                ->orderByDesc('created_at')
                ->limit(25)
                ->get()
            : collect();

        return view('admin.customers.show', compact(
            'customer',
            'orders',
            'totalSpent',
            'lastOrderAt',
            'rewardSummary',
            'rewardPrograms',
            'rewardLedgerEntries',
            'referralSummary',
            'referralPrograms',
            'referralLedgerEntries',
            'referralsMade'
        ));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
