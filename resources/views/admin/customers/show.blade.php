@extends('admin.layouts.app')

@section('title', 'Customer Details')
@section('header', 'Customer Details')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
@php
    $displayName = $customer->name ?: trim($customer->firstname . ' ' . $customer->lastname);
    $displayName = $displayName ?: 'Customer';
@endphp
<div class="profile-container">
    <a href="{{ route('admin.customers.index') }}" class="back-link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Customers
    </a>

    <div class="profile-header animate-in">
        <div class="profile-header-content">
            <div class="profile-header-top">
                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        {{ strtoupper(substr($displayName, 0, 2)) }}
                    </div>
                    <div class="profile-info">
                        <h1 class="heading-serif text-3xl font-semibold">{{ $displayName }}</h1>
                        <p class="profile-username">{{ $customer->email ?: 'No email on file' }}</p>
                    </div>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email
                    </div>
                    <div class="stat-value">{{ $customer->email ?: 'Not set' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.5 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.5a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Phone
                    </div>
                    <div class="stat-value">{{ $customer->phone ?: 'Not set' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Last Order
                    </div>
                    <div class="stat-value">{{ $lastOrderAt?->format('M d, Y') ?? 'None' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2v4c0 1.105 1.343 2 3 2s3-.895 3-2v-4c0-1.105-1.343-2-3-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h1m16 0h1M4 6h16M4 18h16"></path>
                        </svg>
                        Orders
                    </div>
                    <div class="stat-value">{{ $customer->orders_count }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2v4c0 1.105 1.343 2 3 2s3-.895 3-2v-4c0-1.105-1.343-2-3-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h12M6 20h12"></path>
                        </svg>
                        Total Spent
                    </div>
                    <div class="stat-value">${{ number_format($totalSpent, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card animate-in animate-delay-1">
        <div class="section-header">
            <h2 class="section-title">Customer Orders</h2>
        </div>

        @if($orders->count() > 0)
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
                        @foreach($orders as $order)
                            @php
                                $statusColor = match($order->status?->code ?? 'pending') {
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
                                    <span class="token-name">#{{ $order->number }}</span>
                                </td>
                                <td>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                                        {{ $order->status?->name ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="token-meta font-semibold">${{ number_format($order->total, 2) }}</span>
                                </td>
                                <td>
                                    <span class="token-meta">{{ $order->created_at->format('M d, Y H:i') }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-revoke" style="text-decoration: none;">View</a>
                                </td>
                            </tr>
                        @endforeach
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
                <h3 class="empty-state-title">No orders yet</h3>
                <p class="empty-state-text">This customer has not placed any orders.</p>
            </div>
        @endif
    </div>
</div>
@endsection
