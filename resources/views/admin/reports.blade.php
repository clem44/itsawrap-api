@extends('admin.layouts.app')

@section('title', 'Reports')
@section('header', 'Reports')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="page-header animate-in">
        <div class="page-header-content">
            <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Session Reports</h1>
            <p style="color: var(--color-sage-light); opacity: 0.9;">Select a cash session to review performance and totals.</p>
        </div>
    </div>

    <div class="section-card animate-in animate-delay-1">
        <div class="section-header">
            <h3 class="section-title">Filter</h3>
        </div>
        <div class="section-content">
            <form method="GET" action="{{ route('admin.reports') }}" class="form-grid" style="gap: 1rem;">
                <div class="form-group full-width">
                    <label class="form-label" for="session_id">Cash Session</label>
                    <select name="session_id" id="session_id" class="form-select" onchange="this.form.submit()">
                        @forelse($sessions as $session)
                            <option value="{{ $session->id }}" @selected($selectedSession && $selectedSession->id === $session->id)>
                                Session #{{ $session->id }} — {{ $session->user?->full_name ?? 'Unknown' }} — {{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}
                            </option>
                        @empty
                            <option value="">No sessions available</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-grid animate-in animate-delay-2">
        <div class="stat-card-dashboard">
            <div class="stat-icon orders">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Revenue for Session</p>
                <p class="stat-value-dashboard">${{ number_format($stats['revenue'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon today">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Orders This Session</p>
                <p class="stat-value-dashboard">{{ $stats['total_orders'] }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon users">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2v4c0 1.105 1.343 2 3 2s3-.895 3-2v-4c0-1.105-1.343-2-3-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h12M6 20h12"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Tips</p>
                <p class="stat-value-dashboard">${{ number_format($stats['total_tips'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon admins">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Withdrawals</p>
                <p class="stat-value-dashboard">${{ number_format($stats['total_withdrawals'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon devices">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Sales from Cash (inc. service charge)</p>
                <p class="stat-value-dashboard">${{ number_format($stats['sales_cash'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon orders">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7M9 11h6"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Sales from Credit Card (inc. service charge)</p>
                <p class="stat-value-dashboard">${{ number_format($stats['sales_card'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon users">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V20a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Service Charge from Cash</p>
                <p class="stat-value-dashboard">${{ number_format($stats['service_charge_cash'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon admins">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Service Charge from Credit Card</p>
                <p class="stat-value-dashboard">${{ number_format($stats['service_charge_card'], 2) }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon today">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m0 0l4-4m-4 4l4 4"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Service Charge from Delivery</p>
                <p class="stat-value-dashboard">${{ number_format($stats['service_charge_delivery'], 2) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
