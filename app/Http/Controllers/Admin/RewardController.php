<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\RewardProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function index(): View
    {
        $programs = RewardProgram::query()
            ->with(['earnCategory', 'rewardCategory'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.rewards.index', compact('programs', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProgram($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by_user_id'] = $request->user()?->id;

        $this->ensureNoActiveDuplicate($validated);

        RewardProgram::query()->create($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward program created successfully.');
    }

    public function update(Request $request, RewardProgram $reward): RedirectResponse
    {
        $validated = $this->validateProgram($request);
        $validated['is_active'] = $request->boolean('is_active');

        $this->ensureNoActiveDuplicate($validated, $reward);

        $reward->update($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward program updated successfully.');
    }

    public function activate(RewardProgram $reward): RedirectResponse
    {
        $this->ensureNoActiveDuplicate([
            'is_active' => true,
            'earn_category_id' => $reward->earn_category_id,
            'reward_category_id' => $reward->reward_category_id,
        ], $reward);

        $reward->update(['is_active' => true]);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward program activated successfully.');
    }

    public function deactivate(RewardProgram $reward): RedirectResponse
    {
        $reward->update(['is_active' => false]);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward program deactivated successfully.');
    }

    public function destroy(RewardProgram $reward): RedirectResponse
    {
        if ($reward->ledgerEntries()->exists() || $reward->customerRewardAccounts()->exists()) {
            return redirect()->route('admin.rewards.index')
                ->with('error', 'Cannot delete a reward program with customer activity. Deactivate it instead.');
        }

        $reward->delete();

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Reward program deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProgram(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'earn_category_id' => 'required|exists:categories,id',
            'qualifying_item_quantity_required' => 'required|integer|min:1',
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
    private function ensureNoActiveDuplicate(array $validated, ?RewardProgram $ignore = null): void
    {
        if (! ($validated['is_active'] ?? false)) {
            return;
        }

        $duplicate = RewardProgram::query()
            ->where('is_active', true)
            ->where('earn_category_id', $validated['earn_category_id'])
            ->where('reward_category_id', $validated['reward_category_id'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'reward_category_id' => 'An active program already exists for this earn and reward category pair.',
            ]);
        }
    }
}
