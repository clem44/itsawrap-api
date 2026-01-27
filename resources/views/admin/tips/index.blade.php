@extends('admin.layouts.app')

@section('title', 'Tips')
@section('header', 'Tips')

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Tips</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Review tips from customer orders.</p>
            </div>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Tip ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tips as $tip)
                    <tr>
                        <td>
                            <span class="user-meta">#{{ $tip->id }}</span>
                        </td>
                        <td>
                            <span class="user-meta">#{{ $tip->order?->number ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $tip->order?->customer?->name ?: 'Walk-in' }}</span>
                        </td>
                        <td>
                            <span class="user-meta font-semibold">${{ number_format($tip->amount, 2) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $tip->created_at->format('M d, Y H:i') }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a
                                    href="{{ route('admin.tips.show', $tip) }}"
                                    class="action-btn view"
                                    title="View Tip"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2v4c0 1.105 1.343 2 3 2s3-.895 3-2v-4c0-1.105-1.343-2-3-2z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h1m16 0h1M4 6h16M4 18h16"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No tips yet</h3>
                                <p class="empty-state-text">Tips will appear here once orders are completed.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tips->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $tips->links() }}
        </div>
    @endif
</div>
@endsection
