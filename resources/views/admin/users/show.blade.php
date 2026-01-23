@extends('admin.layouts.app')

@section('title', 'User Details')
@section('header', 'User Details')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="profile-container">
    <a href="{{ route('admin.users.index') }}" class="back-link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Users
    </a>

    <!-- Profile Header -->
    <div class="profile-header animate-in">
        <div class="profile-header-content">
            <div class="profile-header-top">
                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                    </div>
                    <div class="profile-info">
                        <h1 class="heading-serif text-3xl font-semibold">{{ $user->full_name }}</h1>
                        <p class="profile-username">{{ $user->username }}</p>
                        <span class="role-badge {{ $user->role_id === 1 ? 'admin' : 'user' }}">
                            @if($user->role_id === 1)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            @endif
                            {{ $user->role_id === 1 ? 'Administrator' : 'User' }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn-edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Profile
                </a>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email
                    </div>
                    <div class="stat-value">{{ $user->email ?? 'Not set' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Last Login
                    </div>
                    <div class="stat-value">{{ $user->last_login?->format('M d, Y H:i') ?? 'Never' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Created
                    </div>
                    <div class="stat-value">{{ $user->created_at->format('M d, Y') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Updated
                    </div>
                    <div class="stat-value">{{ $user->updated_at->format('M d, Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Tokens Section -->
    <div class="section-card animate-in animate-delay-1">
        <div class="section-header">
            <h2 class="section-title">API Tokens</h2>
            @if($tokens->count() > 0)
                <form method="POST" action="{{ route('admin.users.revoke-all-tokens', $user) }}" onsubmit="return confirm('Are you sure you want to revoke all tokens? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-revoke-all">Revoke All Tokens</button>
                </form>
            @endif
        </div>

        @if($tokens->count() > 0)
            <div class="overflow-x-auto">
                <table class="tokens-table">
                    <thead>
                        <tr>
                            <th>Token Name</th>
                            <th>Created</th>
                            <th>Last Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tokens as $token)
                            <tr>
                                <td>
                                    <div class="token-name">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                        {{ $token->name }}
                                    </div>
                                </td>
                                <td>
                                    <span class="token-meta">{{ $token->created_at->format('M d, Y H:i') }}</span>
                                </td>
                                <td>
                                    <span class="token-meta">{{ $token->last_used_at?->format('M d, Y H:i') ?? 'Never used' }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.revoke-token', [$user, $token->id]) }}" class="inline" onsubmit="return confirm('Are you sure you want to revoke this token?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-revoke">Revoke</button>
                                    </form>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <h3 class="empty-state-title">No active API tokens</h3>
                <p class="empty-state-text">This user hasn't created any API tokens yet</p>
            </div>
        @endif
    </div>

    <!-- Recent Activity Section -->
    <div class="section-card animate-in animate-delay-2">
        <div class="section-header">
            <h2 class="section-title">Account Timeline</h2>
        </div>
        <div class="section-content">
            <div class="activity-list">
                @if($user->last_login)
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Last login</div>
                            <div class="activity-time">{{ $user->last_login->format('F d, Y \a\t H:i') }}</div>
                        </div>
                    </div>
                @endif
                <div class="activity-item">
                    <div class="activity-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Profile updated</div>
                        <div class="activity-time">{{ $user->updated_at->format('F d, Y \a\t H:i') }}</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon highlight">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Account created</div>
                        <div class="activity-time">{{ $user->created_at->format('F d, Y \a\t H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection