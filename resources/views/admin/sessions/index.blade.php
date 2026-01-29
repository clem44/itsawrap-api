@extends('admin.layouts.app')

@section('title', 'Sessions')
@section('header', 'Sessions')

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Cash Sessions</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Review cash register sessions and totals.</p>
            </div>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Cashier</th>
                    <th>Opening</th>
                    <th>Total Sales</th>
                    <th>Expected</th>
                    <th>Status</th>
                    <th>Opened</th>
                    <th>Closed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($session->user?->firstname ?? 'U', 0, 1) . substr($session->user?->lastname ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">Session #{{ $session->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->user?->full_name ?? 'Unknown' }}</span>
                        </td>
                        <td>
                            <span class="user-meta font-semibold">${{ number_format($session->opening_amount, 2) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">${{ number_format($session->total_sales, 2) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->expected_amount !== null ? '$' . number_format($session->expected_amount, 2) : '-' }}</span>
                        </td>
                        <td>
                            @php
                                $statusColor = $session->is_open
                                    ? ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50']
                                    : ['bg' => 'rgba(96, 125, 139, 0.2)', 'text' => '#607D8B'];
                            @endphp
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                                {{ $session->is_open ? 'Open' : 'Closed' }}
                            </span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->closed_at?->format('M d, Y H:i') ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a
                                    href="{{ route('admin.sessions.show', $session) }}"
                                    class="action-btn view"
                                    title="View Session"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                @if(auth()->user()->role_id === 1)
                                    <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}" class="inline" onsubmit="return confirm('Delete this session? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn delete" title="Delete Session">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7V3m6 4V3m-9 8h12M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No sessions yet</h3>
                                <p class="empty-state-text">Cash sessions will appear here once opened.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $sessions->links() }}
        </div>
    @endif
</div>
@endsection
