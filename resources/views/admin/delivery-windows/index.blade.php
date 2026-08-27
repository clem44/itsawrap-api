@extends('admin.layouts.app')

@section('title', 'Delivery')
@section('header', 'Delivery')

@php
    $weekdays = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];
@endphp

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Delivery Windows</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Manage recurring and date-specific delivery slots.</p>
            </div>
        </div>
    </div>

    <div class="form-card with-accent animate-in animate-delay-1 mb-6">
        <div class="form-header">
            <h2 class="heading-serif text-2xl font-semibold">Create Delivery Window</h2>
            <p>Specific-date windows replace recurring weekday windows for that date.</p>
        </div>

        <form method="POST" action="{{ route('admin.delivery-windows.store') }}">
            @csrf

            <div class="form-section">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="schedule_type">Schedule Type</label>
                        <select id="schedule_type" name="schedule_type" class="form-select @error('schedule_type') error @enderror" required>
                            <option value="weekly" @selected(old('schedule_type', 'weekly') === 'weekly')>Weekly recurring</option>
                            <option value="specific_date" @selected(old('schedule_type') === 'specific_date')>Specific date</option>
                        </select>
                        @error('schedule_type')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="day_of_week">Weekly Day</label>
                        <select id="day_of_week" name="day_of_week" class="form-select @error('day_of_week') error @enderror">
                            <option value="">Choose day</option>
                            @foreach($weekdays as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('day_of_week') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('day_of_week')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="delivery_date">Specific Date</label>
                        <input id="delivery_date" type="date" name="delivery_date" value="{{ old('delivery_date') }}" class="form-input @error('delivery_date') error @enderror">
                        @error('delivery_date')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="start_time">Start Time</label>
                        <input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" class="form-input @error('start_time') error @enderror" required>
                        @error('start_time')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_time">End Time</label>
                        <input id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" class="form-input @error('end_time') error @enderror" required>
                        @error('end_time')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="capacity">Capacity</label>
                        <input id="capacity" type="number" name="capacity" min="1" max="999" value="{{ old('capacity', 8) }}" class="form-input @error('capacity') error @enderror" required>
                        @error('capacity')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="branch_id">Branch <span class="optional">(Optional)</span></label>
                        <select id="branch_id" name="branch_id" class="form-select @error('branch_id') error @enderror">
                            <option value="">All branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-group mt-4">
                    <label class="form-label" for="driver_ids">Drivers</label>
                    <select id="driver_ids" name="driver_ids[]" class="form-select @error('driver_ids') error @enderror" multiple size="4">
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected(in_array((string) $driver->id, old('driver_ids', []), true))>{{ $driver->full_name }}</option>
                        @endforeach
                    </select>
                    @error('driver_ids')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                    @error('driver_ids.*')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group mt-4">
                    <label class="form-label" for="notes">Notes <span class="optional">(Optional)</span></label>
                    <textarea id="notes" name="notes" rows="3" class="form-input @error('notes') error @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <input id="is_active" type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', true))>
                    <label class="form-label mb-0" for="is_active">Active</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-forest">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Window
                </button>
            </div>
        </form>
    </div>

    <div class="users-table-container animate-in animate-delay-2">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Window</th>
                    <th>Schedule</th>
                    <th>Capacity</th>
                    <th>Drivers</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if($deliveryWindows->isEmpty())
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No delivery windows yet</h3>
                                <p class="empty-state-text">Create the first delivery window to make delivery available.</p>
                            </div>
                        </td>
                    </tr>
                @else
                    @php
                        $currentGroup = null;
                    @endphp
                    @foreach($deliveryWindows as $deliveryWindow)
                        @php
                            $groupLabel = $deliveryWindow->schedule_type === 'specific_date'
                                ? 'Specific Date: '.$deliveryWindow->delivery_date?->format('M d, Y')
                                : 'Recurring: '.($weekdays[$deliveryWindow->day_of_week] ?? 'Weekly');
                        @endphp
                        @if($groupLabel !== $currentGroup)
                            @php
                                $currentGroup = $groupLabel;
                            @endphp
                            <tr>
                                <td colspan="6" class="text-left text-xs font-semibold uppercase tracking-wide" style="background: rgba(44, 74, 58, 0.06); color: var(--color-forest);">
                                    {{ $groupLabel }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                        <td>
                            <div class="user-name">{{ \Carbon\Carbon::parse($deliveryWindow->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($deliveryWindow->end_time)->format('g:i A') }}</div>
                            <span class="user-meta">{{ $deliveryWindow->branch?->name ?? 'All branches' }}</span>
                        </td>
                        <td>
                            @if($deliveryWindow->schedule_type === 'specific_date')
                                <span class="role-badge admin">{{ $deliveryWindow->delivery_date?->format('M d, Y') }}</span>
                            @else
                                <span class="role-badge user">{{ $weekdays[$deliveryWindow->day_of_week] ?? 'Weekly' }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="user-meta">{{ $deliveryWindow->today_deliveries_count }} / {{ $deliveryWindow->capacity }} today</span>
                        </td>
                        <td>
                            <span class="user-meta">
                                {{ $deliveryWindow->drivers->pluck('full_name')->join(', ') ?: 'No drivers assigned' }}
                            </span>
                        </td>
                        <td>
                            @if($deliveryWindow->is_active)
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: rgba(76, 175, 80, 0.2); color: #4CAF50;">
                                    Active
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: rgba(244, 67, 54, 0.2); color: #F44336;">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td>
                            <details>
                                <summary class="btn-revoke" style="cursor: pointer;">Edit</summary>
                                <form method="POST" action="{{ route('admin.delivery-windows.update', $deliveryWindow) }}" class="mt-4 space-y-3 min-w-72">
                                    @csrf
                                    @method('PUT')

                                    <select name="schedule_type" class="form-select" required>
                                        <option value="weekly" @selected($deliveryWindow->schedule_type === 'weekly')>Weekly recurring</option>
                                        <option value="specific_date" @selected($deliveryWindow->schedule_type === 'specific_date')>Specific date</option>
                                    </select>

                                    <select name="day_of_week" class="form-select">
                                        <option value="">Choose day</option>
                                        @foreach($weekdays as $value => $label)
                                            <option value="{{ $value }}" @selected((int) $deliveryWindow->day_of_week === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>

                                    <input type="date" name="delivery_date" value="{{ $deliveryWindow->delivery_date?->format('Y-m-d') }}" class="form-input">

                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="time" name="start_time" value="{{ substr((string) $deliveryWindow->start_time, 0, 5) }}" class="form-input" required>
                                        <input type="time" name="end_time" value="{{ substr((string) $deliveryWindow->end_time, 0, 5) }}" class="form-input" required>
                                    </div>

                                    <input type="number" name="capacity" min="1" max="999" value="{{ $deliveryWindow->capacity }}" class="form-input" required>

                                    <select name="branch_id" class="form-select">
                                        <option value="">All branches</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected($deliveryWindow->branch_id === $branch->id)>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>

                                    <select name="driver_ids[]" class="form-select" multiple size="4">
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}" @selected($deliveryWindow->drivers->contains($driver))>{{ $driver->full_name }}</option>
                                        @endforeach
                                    </select>

                                    <textarea name="notes" rows="2" class="form-input">{{ $deliveryWindow->notes }}</textarea>

                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" @checked($deliveryWindow->is_active)>
                                        Active
                                    </label>

                                    <div class="flex gap-2">
                                        <button type="submit" class="btn-revoke">Save</button>
                                    </div>
                                </form>
                            </details>
                        </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
