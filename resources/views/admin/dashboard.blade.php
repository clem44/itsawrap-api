@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="dashboard-container">
    <!-- Page Header -->
    <div class="page-header animate-in">
        <div class="page-header-content">
            <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Dashboard</h1>
            <p style="color: var(--color-sage-light); opacity: 0.9;">Welcome back, {{ auth()->user()->firstname }}. Here's an overview of your system.</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid animate-in animate-delay-1">
        <div class="stat-card-dashboard">
            <div class="stat-icon users">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Users</p>
                <p class="stat-value-dashboard">{{ $stats['total_users'] }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon admins">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Admin Users</p>
                <p class="stat-value-dashboard">{{ $stats['admin_users'] }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon orders">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Total Orders</p>
                <p class="stat-value-dashboard">{{ $stats['total_orders'] }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon today">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Today's Orders</p>
                <p class="stat-value-dashboard">{{ $stats['today_orders'] }}</p>
            </div>
        </div>

        <div class="stat-card-dashboard">
            <div class="stat-icon devices">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p class="stat-label-dashboard">Active Devices</p>
                <p class="stat-value-dashboard">{{ $stats['active_devices'] }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent Users -->
    <div class="dashboard-grid animate-in animate-delay-2">
        <!-- Quick Actions -->
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title">Quick Actions</h3>
            </div>
            <div class="section-content">
                <div class="quick-actions-grid">
                    <a href="{{ route('admin.users.create') }}" class="quick-action-card">
                        <div class="quick-action-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <span class="quick-action-label">Add User</span>
                    </a>
                    <a href="{{ route('admin.api-docs') }}" class="quick-action-card">
                        <div class="quick-action-icon docs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <span class="quick-action-label">API Docs</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title">Recent Users</h3>
                <a href="{{ route('admin.users.index') }}" class="view-all-link">View all</a>
            </div>
            <div class="section-content">
                <div class="recent-users-list">
                    @forelse($recentUsers as $user)
                        <div class="recent-user-item">
                            <div class="flex items-center gap-3">
                                <div class="user-avatar">
                                    {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                                </div>
                                <div>
                                    <p class="user-name">{{ $user->full_name }}</p>
                                    <p class="user-meta">{{ $user->username }}</p>
                                </div>
                            </div>
                            <span class="role-badge {{ $user->role_id === 1 ? 'admin' : 'user' }}">
                                {{ $user->role_id === 1 ? 'Admin' : 'User' }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state-small">
                            <p>No users found</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Connected Devices -->
    <div class="section-card animate-in animate-delay-3">
        <div class="section-header">
            <h3 class="section-title">Connected Devices</h3>
            <span class="section-meta">Active in last {{ $deviceWindowMinutes }} minutes</span>
        </div>
        <div class="section-content" style="padding: 0;">
            @if($activeDevices->count() > 0)
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>User</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeDevices as $device)
                            <tr>
                                <td>
                                    <div class="device-name">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ $device->name }}
                                    </div>
                                </td>
                                <td>
                                    <span class="user-meta">{{ $device->tokenable?->full_name ?? 'Unknown' }}</span>
                                    <span class="text-xs" style="color: var(--color-ink-light);">({{ $device->tokenable?->username ?? 'n/a' }})</span>
                                </td>
                                <td>
                                    <span class="user-meta">{{ $device->last_used_at?->format('M d, Y H:i') ?? 'Never' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state-small" style="padding: 2rem;">
                    <p>No devices connected recently.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
