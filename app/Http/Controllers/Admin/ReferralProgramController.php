<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReferralProgramController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProgram($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by_user_id'] = $request->user()?->id;

        $this->ensureNoActiveDuplicate($validated);

        ReferralProgram::query()->create($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Referral program created successfully.');
    }

    public function update(Request $request, ReferralProgram $referralReward): RedirectResponse
    {
        $validated = $this->validateProgram($request);
        $validated['is_active'] = $request->boolean('is_active');

        $this->ensureNoActiveDuplicate($validated, $referralReward);

        $referralReward->update($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Referral program updated successfully.');
    }

    public function activate(ReferralProgram $referralReward): RedirectResponse
    {
        $this->ensureNoActiveDuplicate(['is_active' => true], $referralReward);

        $referralReward->update(['is_active' => true]);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Referral program activated successfully.');
    }

    public function deactivate(ReferralProgram $referralReward): RedirectResponse
    {
        $referralReward->update(['is_active' => false]);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Referral program deactivated successfully.');
    }

    public function destroy(ReferralProgram $referralReward): RedirectResponse
    {
        if ($referralReward->ledgerEntries()->exists() || $referralReward->rewardAccounts()->exists() || $referralReward->referrals()->exists()) {
            return redirect()->route('admin.rewards.index')
                ->with('error', 'Cannot delete a referral program with customer activity. Deactivate it instead.');
        }

        $referralReward->delete();

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Referral program deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProgram(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'required_referrals' => 'required|integer|min:1',
            'reward_category_id' => 'required|exists:categories,id',
            'reward_quantity' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => [
                'nullable',
                'date',
                Rule::when($request->filled('starts_at'), 'after:starts_at'),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureNoActiveDuplicate(array $validated, ?ReferralProgram $ignore = null): void
    {
        if (! ($validated['is_active'] ?? false)) {
            return;
        }

        $duplicate = ReferralProgram::query()
            ->where('is_active', true)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'reward_category_id' => 'Only one active referral program is supported in this first version.',
            ]);
        }
    }
}
