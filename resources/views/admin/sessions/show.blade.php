@extends('admin.layouts.app')

@section('title', 'Session #' . $session->id)
@section('header', 'Session #' . $session->id)

@section('content')
<div class="animate-in">
    <div class="page-header animate-in mb-6">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.sessions.index') }}" class="text-white/60 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Session #{{ $session->id }}</h1>
                    <p style="color: var(--color-sage-light); opacity: 0.9;">
                        Opened {{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>
            @php
                $statusColor = $session->is_open
                    ? ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50']
                    : ['bg' => 'rgba(96, 125, 139, 0.2)', 'text' => '#607D8B'];
            @endphp
            <div class="inline-block px-4 py-2 text-sm font-semibold rounded-lg" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                {{ $session->is_open ? 'Open' : 'Closed' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in animate-delay-1">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Cashier Information</h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                            {{ strtoupper(substr($session->user?->firstname ?? 'U', 0, 1) . substr($session->user?->lastname ?? 'S', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-gray-900 font-semibold">{{ $session->user?->full_name ?? 'Unknown Cashier' }}</p>
                            @if($session->user?->username)
                                <p class="text-sm text-gray-600">{{ $session->user->username }}</p>
                            @endif
                            @if($session->user?->email)
                                <p class="text-sm text-gray-600">{{ $session->user->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Session Activity</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Opened At</span>
                        <span class="text-gray-900 font-semibold">{{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Closed At</span>
                        <span class="text-gray-900 font-semibold">{{ $session->closed_at?->format('M d, Y H:i') ?? 'Still open' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Orders</span>
                        <span class="text-gray-900 font-semibold">{{ $session->orders->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Withdrawals</span>
                        <span class="text-gray-900 font-semibold">{{ $session->withdrawals->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Orders</h2>
                <div class="space-y-3">
                    @forelse($session->orders as $order)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <div>
                                <p class="text-gray-900 font-medium">Order #{{ $order->number }}</p>
                                <p class="text-sm text-gray-600">{{ $order->customer?->name ?: 'Walk-in' }}</p>
                                <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                @php
                                    $orderStatusColor = match($order->status?->code ?? 'pending') {
                                        'pending' => ['bg' => 'rgba(255, 193, 7, 0.2)', 'text' => '#FFC107'],
                                        'confirmed' => ['bg' => 'rgba(33, 150, 243, 0.2)', 'text' => '#2196F3'],
                                        'preparing' => ['bg' => 'rgba(156, 39, 176, 0.2)', 'text' => '#9C27B0'],
                                        'ready' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                                        'completed' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                                        'cancelled' => ['bg' => 'rgba(244, 67, 54, 0.2)', 'text' => '#F44336'],
                                        default => ['bg' => 'rgba(124, 154, 138, 0.2)', 'text' => 'var(--color-sage)']
                                    };
                                @endphp
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full mb-2" style="background: {{ $orderStatusColor['bg'] }}; color: {{ $orderStatusColor['text'] }};">
                                    {{ $order->status?->name ?? 'Unknown' }}
                                </span>
                                <p class="text-gray-900 font-semibold">${{ number_format($order->total, 2) }}</p>
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-xs text-slate-500 hover:text-slate-700">View order</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No orders linked to this session
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Withdrawals</h2>
                <div class="space-y-3">
                    @forelse($session->withdrawals as $withdrawal)
                        <div class="flex items-start justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <div>
                                <p class="text-gray-900 font-medium">{{ $withdrawal->description ?: 'Withdrawal' }}</p>
                                <p class="text-sm text-gray-600">{{ $withdrawal->user?->full_name ?? 'Unknown user' }}</p>
                                <p class="text-xs text-gray-500">{{ $withdrawal->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-gray-900 font-semibold">-${{ number_format($withdrawal->amount, 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No withdrawals for this session
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="rounded-2xl p-6 sticky top-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Session Summary</h2>

                <div class="space-y-4 pb-6 border-b border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Opening Amount</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->opening_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Sales</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->total_sales, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Tips</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->total_tips, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Service Charge</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->total_service_charge, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Withdrawals</span>
                        <span class="text-gray-900 font-semibold">-${{ number_format($session->total_withdrawals, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Expected Amount</span>
                        <span class="text-gray-900 font-semibold">{{ $session->expected_amount !== null ? '$' . number_format($session->expected_amount, 2) : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Closing Amount</span>
                        <span class="text-gray-900 font-semibold">{{ $session->closing_amount !== null ? '$' . number_format($session->closing_amount, 2) : '-' }}</span>
                    </div>
                </div>

                @php
                    $variance = ($session->closing_amount !== null && $session->expected_amount !== null)
                        ? $session->closing_amount - $session->expected_amount
                        : null;
                @endphp
                <div class="flex justify-between items-center pt-4 mb-6">
                    <span class="text-lg font-semibold text-gray-900">Variance</span>
                    <span class="text-2xl font-bold" style="color: {{ $variance === null ? 'var(--color-sage)' : ($variance >= 0 ? '#4CAF50' : '#F44336') }};">
                        {{ $variance === null ? '-' : '$' . number_format($variance, 2) }}
                    </span>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Opened Date:</span>
                        <span>{{ $session->opened_at?->format('M d, Y') ?? $session->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Opened Time:</span>
                        <span>{{ $session->opened_at?->format('H:i') ?? $session->created_at->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Closed Time:</span>
                        <span>{{ $session->closed_at?->format('H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Session #:</span>
                        <span class="font-mono">#{{ $session->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
