@extends('admin.layouts.app')

@section('title', 'Payments')
@section('header', 'Payments')

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Payments</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Review payments from customer orders.</p>
            </div>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>
                            <span class="user-meta">#{{ $payment->id }}</span>
                        </td>
                        <td>
                            <span class="user-meta">#{{ $payment->order?->number ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $payment->order?->customer?->name ?: 'Walk-in' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ ucfirst($payment->method) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ ucfirst($payment->status) }}</span>
                        </td>
                        <td>
                            <span class="user-meta font-semibold">${{ number_format($payment->amount, 2) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $payment->created_at->format('M d, Y H:i') }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a
                                    href="{{ route('admin.payments.show', $payment) }}"
                                    class="action-btn view"
                                    title="View Payment"
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
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a5 5 0 00-10 0v2H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No payments yet</h3>
                                <p class="empty-state-text">Payments will appear here once orders are paid.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
