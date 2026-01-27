@extends('admin.layouts.app')

@section('title', 'Payment Details')
@section('header', 'Payment Details')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="profile-container">
    <a href="{{ route('admin.payments.index') }}" class="back-link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Payments
    </a>

    <div class="profile-header animate-in">
        <div class="profile-header-content">
            <div class="profile-header-top">
                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        {{ strtoupper(substr((string) $payment->id, 0, 2)) }}
                    </div>
                    <div class="profile-info">
                        <h1 class="heading-serif text-3xl font-semibold">Payment #{{ $payment->id }}</h1>
                        <p class="profile-username">Order #{{ $payment->order?->number ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2v4c0 1.105 1.343 2 3 2s3-.895 3-2v-4c0-1.105-1.343-2-3-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h12M6 20h12"></path>
                        </svg>
                        Amount
                    </div>
                    <div class="stat-value">${{ number_format($payment->amount, 2) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-9a2 2 0 00-2-2h-2z"></path>
                        </svg>
                        Method
                    </div>
                    <div class="stat-value">{{ ucfirst($payment->method) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Created
                    </div>
                    <div class="stat-value">{{ $payment->created_at->format('M d, Y H:i') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Status
                    </div>
                    <div class="stat-value">{{ ucfirst($payment->status) }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.5 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.5a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Customer
                    </div>
                    <div class="stat-value">{{ $payment->order?->customer?->name ?: 'Walk-in' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card animate-in animate-delay-1">
        <div class="section-header">
            <h2 class="section-title">Order Summary</h2>
        </div>
        @if($payment->order)
            <div class="overflow-x-auto">
                <table class="tokens-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $statusColor = match($payment->order->status?->code ?? 'pending') {
                                'pending' => ['bg' => 'rgba(255, 193, 7, 0.2)', 'text' => '#FFC107'],
                                'confirmed' => ['bg' => 'rgba(33, 150, 243, 0.2)', 'text' => '#2196F3'],
                                'preparing' => ['bg' => 'rgba(156, 39, 176, 0.2)', 'text' => '#9C27B0'],
                                'ready' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                                'completed' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                                'cancelled' => ['bg' => 'rgba(244, 67, 54, 0.2)', 'text' => '#F44336'],
                                default => ['bg' => 'rgba(124, 154, 138, 0.2)', 'text' => 'var(--color-sage)']
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="token-name">#{{ $payment->order->number }}</span>
                            </td>
                            <td>
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                                    {{ $payment->order->status?->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td>
                                <span class="token-meta font-semibold">${{ number_format($payment->order->total, 2) }}</span>
                            </td>
                            <td>
                                <span class="token-meta">{{ $payment->order->created_at->format('M d, Y H:i') }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $payment->order) }}" class="btn-revoke" style="text-decoration: none;">View</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8h5a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v7"></path>
                    </svg>
                </div>
                <h3 class="empty-state-title">No order found</h3>
                <p class="empty-state-text">This payment is not linked to an order.</p>
            </div>
        @endif
    </div>
</div>
@endsection
