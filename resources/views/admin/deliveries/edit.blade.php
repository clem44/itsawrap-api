@extends('admin.layouts.app')

@section('title', 'Edit Delivery #' . $delivery->id)
@section('header', 'Edit Delivery')

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
<div class="animate-in">
    <div class="page-header animate-in mb-6">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.deliveries.index') }}" class="text-white/60 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Delivery #{{ $delivery->id }}</h1>
                    <p style="color: var(--color-sage-light); opacity: 0.9;">Order #{{ $delivery->order?->number ?? '—' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.deliveries.destroy', $delivery) }}" onsubmit="return confirm('Trash this delivery? The order will remain, but it will no longer be marked as delivery.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white rounded-lg bg-red-600/80 hover:bg-red-600 transition-colors">
                    Trash Delivery
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in animate-delay-1">
        <div class="lg:col-span-2">
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Delivery Details</h2>

                <form method="POST" action="{{ route('admin.deliveries.update', $delivery) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <div class="form-group md:col-span-1">
                            <label class="form-label" for="delivery_window_id">Delivery Window</label>
                            <select id="delivery_window_id" name="delivery_window_id" class="form-select @error('delivery_window_id') error @enderror" required>
                                @foreach($deliveryWindows as $deliveryWindow)
                                    <option value="{{ $deliveryWindow->id }}" @selected((int) old('delivery_window_id', $delivery->delivery_window_id) === $deliveryWindow->id)>
                                        {{ $windowLabel($deliveryWindow) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('delivery_window_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="delivery_date">Delivery Date</label>
                            <input id="delivery_date" name="delivery_date" type="date" value="{{ old('delivery_date', $delivery->delivery_date->toDateString()) }}" class="form-input @error('delivery_date') error @enderror" required>
                            @error('delivery_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="assigned_driver_id">Driver</label>
                            <select id="assigned_driver_id" name="assigned_driver_id" class="form-select @error('assigned_driver_id') error @enderror">
                                <option value="">Unassigned</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" @selected((int) old('assigned_driver_id', $delivery->assigned_driver_id) === $driver->id)>
                                        {{ $driver->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_driver_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select @error('status') error @enderror" required>
                                @foreach(['pending' => 'Pending', 'assigned' => 'Assigned', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $delivery->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="latitude">Latitude</label>
                            <input id="latitude" name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $delivery->latitude) }}" class="form-input @error('latitude') error @enderror" required>
                            @error('latitude')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="longitude">Longitude</label>
                            <input id="longitude" name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $delivery->longitude) }}" class="form-input @error('longitude') error @enderror" required>
                            @error('longitude')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label class="form-label" for="address">Address</label>
                            <textarea id="address" name="address" rows="3" class="form-textarea @error('address') error @enderror" required>{{ old('address', $delivery->address) }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group md:col-span-2">
                            <label class="form-label" for="delivery_instructions">Instructions</label>
                            <textarea id="delivery_instructions" name="delivery_instructions" rows="3" class="form-textarea @error('delivery_instructions') error @enderror">{{ old('delivery_instructions', $delivery->delivery_instructions) }}</textarea>
                            @error('delivery_instructions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4">
                        <a href="{{ route('admin.deliveries.index') }}" class="btn btn-secondary justify-center">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-forest justify-center">Save Delivery</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order</h2>
                <div class="space-y-3 text-sm text-gray-700">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600">Order</span>
                        <a href="{{ route('admin.orders.show', $delivery->order) }}" class="font-semibold text-gray-900 hover:underline">#{{ $delivery->order?->number ?? '—' }}</a>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600">Customer</span>
                        <span class="font-semibold text-gray-900 text-right">{{ $delivery->order?->customer?->name ?: 'Guest / Walk-in' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600">Total</span>
                        <span class="font-semibold text-gray-900">${{ number_format($delivery->order?->total ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600">Current Window</span>
                        <span class="font-semibold text-gray-900 text-right">{{ $delivery->window_start_at->format('M d, Y g:i A') }} - {{ $delivery->window_end_at->format('g:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
