@extends('admin.layouts.app')

@section('title', "Order #" . $order->number)
@section('header', "Order #" . $order->number)

@section('content')
<div class="animate-in">
    <!-- Header with back button -->
    <div class="page-header animate-in mb-6">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.orders.index') }}" class="text-white/60 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Order #{{ $order->number }}</h1>
                    <p style="color: var(--color-sage-light); opacity: 0.9;">{{ $order->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
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
            <div class="inline-block px-4 py-2 text-sm font-semibold rounded-lg" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                {{ $order->status?->name ?? 'Unknown' }}
            </div>
        </div>
    </div>

    <!-- Main content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in animate-delay-1">
        <!-- Left column: Order details and items -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Information -->
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                            {{ strtoupper(substr($order->customer?->name ?? 'W', 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-gray-900 font-semibold">{{ $order->customer?->name ?: 'Walk-in Customer' }}</p>
                            @if($order->customer?->phone)
                                <p class="text-sm text-gray-600">{{ $order->customer->phone }}</p>
                            @endif
                            @if($order->customer?->email)
                                <p class="text-sm text-gray-600">{{ $order->customer->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h2>
                <div class="space-y-3">
                    @forelse($order->orderItems as $item)
                        <div class="flex items-start justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">{{ $item->item?->name ?: 'Item' }}</p>
                                <p class="text-sm text-gray-600">Qty: {{ $item->quantity }}</p>
                                @if($item->notes)
                                    <p class="text-sm text-gray-500 mt-1">Notes: {{ $item->notes }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-gray-900 font-semibold">${{ number_format($item->price * $item->quantity, 2) }}</p>
                                <p class="text-xs text-gray-600">${{ number_format($item->price, 2) }} each</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No items in this order
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Payments -->
            @if($order->payments->count() > 0)
                <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Payments</h2>
                    <div class="space-y-3">
                        @foreach($order->payments as $payment)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                                <div>
                                    <p class="text-gray-900 font-medium">{{ $payment->method ?? 'Payment' }}</p>
                                    <p class="text-sm text-gray-600">{{ $payment->created_at->format('M d, Y H:i') }}</p>
                                </div>
                                <p class="text-gray-900 font-semibold">${{ number_format($payment->amount, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right column: Order Summary -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl p-6 sticky top-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h2>

                <div class="space-y-4 pb-6 border-b border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($order->subtotal, 2) }}</span>
                    </div>

                    @if($order->discount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Discount</span>
                            <span class="text-red-600">-${{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif

                    @if($order->discount_percent > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Discount %</span>
                            <span class="text-red-600">-{{ number_format($order->discount_percent, 2) }}%</span>
                        </div>
                    @endif

                    @if($order->service_charge > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Service Charge</span>
                            <span class="text-gray-900 font-semibold">${{ number_format($order->service_charge, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center pt-4 mb-6">
                    <span class="text-lg font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold" style="color: var(--color-sage);">${{ number_format($order->total, 2) }}</span>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Order Date:</span>
                        <span>{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Order Time:</span>
                        <span>{{ $order->created_at->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Order #:</span>
                        <span class="font-mono">#{{ $order->number }}</span>
                    </div>
                </div>

                @if(auth()->user()->role_id === 1)
                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="mt-6" onsubmit="return confirm('Delete this order? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white rounded-lg bg-red-600/80 hover:bg-red-600 transition-colors">
                            Delete Order
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
