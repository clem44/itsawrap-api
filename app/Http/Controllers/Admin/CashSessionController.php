<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function index(): View
    {
        $sessions = CashSession::query()
            ->with(['user', 'branch'])
            ->orderByDesc('opened_at')
            ->paginate(15)
            ->withQueryString();
        $cashiers = User::query()
            ->whereIn('role_id', [Role::ADMIN_ID, Role::STAFF_ID])
            ->withExists([
                'sessions as has_open_session' => fn ($query) => $query->where('is_open', true),
            ])
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();
        $branches = Branch::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('admin.sessions.index', compact('sessions', 'cashiers', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role_id', [Role::ADMIN_ID, Role::STAFF_ID])),
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($validated): void {
            User::query()->lockForUpdate()->findOrFail($validated['user_id']);

            $hasOpenSession = CashSession::query()
                ->where('user_id', $validated['user_id'])
                ->where('is_open', true)
                ->exists();

            if ($hasOpenSession) {
                throw ValidationException::withMessages([
                    'user_id' => 'This cashier already has an open session.',
                ]);
            }

            CashSession::query()->create([
                'user_id' => $validated['user_id'],
                'branch_id' => $validated['branch_id'],
                'opening_amount' => $validated['opening_amount'],
                'is_open' => true,
                'opened_at' => now(),
            ]);
        });

        return to_route('admin.sessions.index')->with('success', 'Cash session opened.');
    }

    public function show(CashSession $session): View
    {
        $session->load([
            'user',
            'branch',
            'orders' => function ($query) {
                $query->with(['customer', 'status'])->orderByDesc('created_at');
            },
            'withdrawals' => function ($query) {
                $query->with('user')->orderByDesc('created_at');
            },
        ]);

        return view('admin.sessions.show', compact('session'));
    }

    public function close(Request $request, CashSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'return_to' => ['nullable', Rule::in(['index', 'show'])],
        ]);

        DB::transaction(function () use ($session, $validated): void {
            $lockedSession = CashSession::query()->lockForUpdate()->findOrFail($session->id);

            if (! $lockedSession->is_open) {
                throw ValidationException::withMessages([
                    'closing_amount' => 'This cash session is already closed.',
                ]);
            }

            $lockedSession->update([
                'closing_amount' => $validated['closing_amount'],
                'is_open' => false,
                'closed_at' => now(),
            ]);
        });

        if (($validated['return_to'] ?? 'show') === 'index') {
            return to_route('admin.sessions.index')->with('success', 'Cash session closed.');
        }

        return to_route('admin.sessions.show', $session)->with('success', 'Cash session closed.');
    }

    public function destroy(CashSession $session): RedirectResponse
    {
        if (! $session->is_open) {
            return back()->with('error', 'Closed cash sessions are read-only and cannot be deleted.');
        }

        $session->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Session deleted successfully.');
    }
}
