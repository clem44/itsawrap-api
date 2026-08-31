@extends('admin.layouts.app')

@section('title', 'Deliveries')
@section('header', 'Deliveries')

@php
    $windowLabel = function (\App\Models\DeliveryWindow $window): string {
        $time = \Carbon\Carbon::parse($window->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($window->end_time)->format('g:i A');

        if ($window->schedule_type === \App\Models\DeliveryWindow::TYPE_SPECIFIC_DATE) {
            return $window->delivery_date?->format('M d, Y') . ' · ' . $time;
        }

        return $window->recurringDayLabel() . ' · ' . $time;
    };
@endphp

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Deliveries</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Review scheduled deliveries and driver assignments.</p>
            </div>
            
        </div>
    </div>
    <form method="GET" action="{{ route('admin.deliveries.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-3 mb-2">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-white/70 mb-1" for="delivery_window_id">Delivery Window</label>
            <select id="delivery_window_id" name="delivery_window_id" class="form-select min-w-64">
                <option value="">All delivery windows</option>
                @foreach($deliveryWindows as $deliveryWindow)
                    <option value="{{ $deliveryWindow->id }}" @selected((string) request('delivery_window_id') === (string) $deliveryWindow->id)>
                        {{ $windowLabel($deliveryWindow) }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sage justify-center">
            Filter
        </button>

        @if(request()->filled('delivery_window_id'))
            <a href="{{ route('admin.deliveries.index') }}" class="btn btn-secondary justify-center">
                Clear
            </a>
        @endif
    </form>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Delivery</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Window</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                    <tr>
                        <td>
                            <div class="user-name">#{{ $delivery->id }}</div>
                            <span class="user-meta">{{ $delivery->delivery_date->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.orders.show', $delivery->order) }}" class="user-meta hover:underline">
                                #{{ $delivery->order?->number ?? '—' }}
                            </a>
                        </td>
                        <td>
                            <span class="user-meta">{{ $delivery->order?->customer?->name ?: 'Guest / Walk-in' }}</span>
                        </td>
                        <td>
                            <div class="user-name">{{ $delivery->window_start_at->format('g:i A') }} - {{ $delivery->window_end_at->format('g:i A') }}</div>
                            <span class="user-meta">{{ $delivery->deliveryWindow ? $windowLabel($delivery->deliveryWindow) : 'Window removed' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $delivery->assignedDriver?->full_name ?? 'Unassigned' }}</span>
                        </td>
                        <td>
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: rgba(124, 154, 138, 0.16); color: var(--color-forest);">
                                {{ str_replace('_', ' ', ucfirst($delivery->status)) }}
                            </span>
                        </td>
                        <td>
                            <span class="user-meta">{{ \Illuminate\Support\Str::limit($delivery->address, 42) }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a
                                    href="{{ route('admin.deliveries.edit', $delivery) }}"
                                    class="action-btn edit"
                                    title="Edit Delivery"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('admin.deliveries.destroy', $delivery) }}" class="inline" onsubmit="return confirm('Trash this delivery? The order will remain, but it will no longer be marked as delivery.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Trash Delivery">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4v11h9zm0 0h2m-2 0h-2m4 0h4V9h-4v7z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No deliveries found</h3>
                                <p class="empty-state-text">Deliveries will appear here after delivery orders are placed.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($deliveries->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $deliveries->links('admin.partials.pagination') }}
        </div>
    @endif
</div>
@endsection
