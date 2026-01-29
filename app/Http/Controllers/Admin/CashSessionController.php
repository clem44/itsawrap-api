<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function index(): View
    {
        $sessions = CashSession::query()
            ->with('user')
            ->orderByDesc('opened_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sessions.index', compact('sessions'));
    }

    public function show(CashSession $session): View
    {
        $session->load([
            'user',
            'orders' => function ($query) {
                $query->with(['customer', 'status'])->orderByDesc('created_at');
            },
            'withdrawals' => function ($query) {
                $query->with('user')->orderByDesc('created_at');
            },
        ]);

        return view('admin.sessions.show', compact('session'));
    }

    public function destroy(CashSession $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Session deleted successfully.');
    }
}
