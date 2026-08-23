<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RewardProgram;
use App\Services\Rewards\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerRewardController extends Controller
{
    public function storeAdjustment(Request $request, Customer $customer, RewardService $rewards): RedirectResponse
    {
        $validated = $request->validate([
            'reward_program_id' => 'required|exists:reward_programs,id',
            'progress_delta' => 'required|integer',
            'rewards_delta' => 'required|integer',
            'note' => 'required|string|max:500',
        ]);

        $program = RewardProgram::query()->findOrFail($validated['reward_program_id']);

        $rewards->adjustCustomer(
            $customer,
            $program,
            $validated['progress_delta'],
            $validated['rewards_delta'],
            $validated['note'],
            $request->user()
        );

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Reward adjustment recorded successfully.');
    }
}
