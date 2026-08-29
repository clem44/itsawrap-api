<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ReferralProgram;
use App\Models\UserReferral;
use App\Services\Referrals\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerReferralController extends Controller
{
    public function storeAdjustment(Request $request, Customer $customer, ReferralService $referrals): RedirectResponse
    {
        $validated = $request->validate([
            'referral_program_id' => 'required|exists:referral_programs,id',
            'progress_delta' => 'required|integer',
            'rewards_delta' => 'required|integer',
            'note' => 'required|string|max:500',
        ]);

        $customer->loadMissing('user');

        if ($customer->user === null) {
            return redirect()->route('admin.customers.show', $customer)
                ->with('error', 'Referral adjustments require a linked user account.');
        }

        $program = ReferralProgram::query()->findOrFail($validated['referral_program_id']);

        $referrals->adjustUser(
            $customer->user,
            $program,
            $validated['progress_delta'],
            $validated['rewards_delta'],
            $validated['note'],
            $request->user()
        );

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Referral adjustment recorded successfully.');
    }

    public function reverse(Request $request, Customer $customer, UserReferral $referral, ReferralService $referrals): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $customer->loadMissing('user');

        if ($customer->user === null || (int) $referral->referrer_user_id !== (int) $customer->user->id) {
            return redirect()->route('admin.customers.show', $customer)
                ->with('error', 'That referral does not belong to this customer.');
        }

        $referrals->reverseReferral($referral, $validated['reason'], $request->user());

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Referral reversed successfully.');
    }
}
