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

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        createOpen: {{ $errors->any() && in_array(old('form_action'), ['create', 'edit'], true) ? 'true' : 'false' }},
        modalMode: '{{ old('form_action') === 'edit' ? 'edit' : 'create' }}',
        createAction: '{{ route('admin.delivery-windows.store') }}',
        updateAction: '{{ route('admin.delivery-windows.update', ['delivery_window' => '__ID__']) }}',
        modalAction: '{{ old('form_action') === 'edit' && old('edit_id') ? route('admin.delivery-windows.update', ['delivery_window' => old('edit_id')]) : route('admin.delivery-windows.store') }}',
        modalWindow: {
            id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
            schedule_type: @js(old('form_action') ? old('schedule_type', 'weekly') : 'weekly'),
            day_of_week: @js(old('form_action') ? old('day_of_week', '') : ''),
            delivery_date: @js(old('form_action') ? old('delivery_date', '') : ''),
            start_time: @js(old('form_action') ? old('start_time', '') : ''),
            end_time: @js(old('form_action') ? old('end_time', '') : ''),
            capacity: @js(old('form_action') ? old('capacity', 8) : 8),
            branch_id: @js(old('form_action') ? old('branch_id', '') : ''),
            driver_ids: @js(old('form_action') ? array_map('strval', old('driver_ids', [])) : []),
            notes: @js(old('form_action') ? old('notes', '') : ''),
            is_active: {{ old('form_action') ? (old('is_active') ? 'true' : 'false') : 'true' }},
        },

        openCreate() {
            this.modalMode = 'create';
            this.modalAction = this.createAction;
            this.modalWindow = {
                id: null,
                schedule_type: 'weekly',
                day_of_week: '',
                delivery_date: '',
                start_time: '',
                end_time: '',
                capacity: 8,
                branch_id: '',
                driver_ids: [],
                notes: '',
                is_active: true,
            };
            this.createOpen = true;
        },

        openEdit(deliveryWindow) {
            this.modalMode = 'edit';
            this.modalAction = this.updateAction.replace('__ID__', deliveryWindow.id);
            this.modalWindow = {
                id: deliveryWindow.id,
                schedule_type: deliveryWindow.schedule_type || 'weekly',
                day_of_week: deliveryWindow.day_of_week ? String(deliveryWindow.day_of_week) : '',
                delivery_date: deliveryWindow.delivery_date || '',
                start_time: deliveryWindow.start_time || '',
                end_time: deliveryWindow.end_time || '',
                capacity: deliveryWindow.capacity || 1,
                branch_id: deliveryWindow.branch_id ? String(deliveryWindow.branch_id) : '',
                driver_ids: deliveryWindow.driver_ids || [],
                notes: deliveryWindow.notes || '',
                is_active: deliveryWindow.is_active === true,
            };
            this.createOpen = true;
        },

        closeCreate() {
            this.createOpen = false;
        },
    });
</script>
@endpush

@section('content')
<div class="users-container">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Delivery Windows</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Manage recurring and date-specific delivery slots.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Window
            </button>
        </div>
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
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Delivery Window"
                                    @click.stop="openEdit(@js([
                                        'id' => $deliveryWindow->id,
                                        'schedule_type' => $deliveryWindow->schedule_type,
                                        'day_of_week' => $deliveryWindow->day_of_week,
                                        'delivery_date' => $deliveryWindow->delivery_date?->format('Y-m-d'),
                                        'start_time' => substr((string) $deliveryWindow->start_time, 0, 5),
                                        'end_time' => substr((string) $deliveryWindow->end_time, 0, 5),
                                        'capacity' => $deliveryWindow->capacity,
                                        'branch_id' => $deliveryWindow->branch_id,
                                        'driver_ids' => $deliveryWindow->drivers->pluck('id')->map(fn ($id) => (string) $id)->values(),
                                        'notes' => $deliveryWindow->notes,
                                        'is_active' => $deliveryWindow->is_active,
                                    ]))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <teleport to="body">
        <div
            v-show="createOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div
                    v-show="createOpen"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeCreate()"
                ></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    v-show="createOpen"
                    class="relative inline-block w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <form method="POST" :action="modalAction">
                        @csrf
                        <input v-if="modalMode === 'edit'" type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" :value="modalMode">
                        <input type="hidden" name="edit_id" :value="modalWindow.id">

                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <div>
                                <h2 id="modal-title" class="text-xl font-semibold text-gray-900" v-text="modalMode === 'edit' ? 'Edit Delivery Window' : 'Create Delivery Window'"></h2>
                                <p class="mt-1 text-sm text-gray-500">Specific-date windows replace recurring weekday windows for that date.</p>
                            </div>
                            <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeCreate()">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6 px-6 py-5 max-h-96 overflow-y-auto">
                            @if($errors->any() && in_array(old('form_action'), ['create', 'edit'], true))
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <p class="font-semibold mb-1">Please fix the following:</p>
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_schedule_type">Schedule Type</label>
                                    <select
                                        id="delivery_schedule_type"
                                        name="schedule_type"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('schedule_type') border-red-500 @enderror"
                                        v-model="modalWindow.schedule_type"
                                        required
                                    >
                                        <option value="weekly">Weekly recurring</option>
                                        <option value="specific_date">Specific date</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_capacity">Capacity</label>
                                    <input
                                        id="delivery_capacity"
                                        name="capacity"
                                        type="number"
                                        min="1"
                                        max="999"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('capacity') border-red-500 @enderror"
                                        v-model="modalWindow.capacity"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_day_of_week">Weekly Day</label>
                                    <select
                                        id="delivery_day_of_week"
                                        name="day_of_week"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('day_of_week') border-red-500 @enderror"
                                        v-model="modalWindow.day_of_week"
                                        :disabled="modalWindow.schedule_type !== 'weekly'"
                                    >
                                        <option value="">Choose day</option>
                                        @foreach($weekdays as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_date">Specific Date</label>
                                    <input
                                        id="delivery_date"
                                        name="delivery_date"
                                        type="date"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] disabled:bg-gray-100 disabled:text-gray-500 @error('delivery_date') border-red-500 @enderror"
                                        v-model="modalWindow.delivery_date"
                                        :disabled="modalWindow.schedule_type !== 'specific_date'"
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_start_time">Start Time</label>
                                    <input
                                        id="delivery_start_time"
                                        name="start_time"
                                        type="time"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('start_time') border-red-500 @enderror"
                                        v-model="modalWindow.start_time"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_end_time">End Time</label>
                                    <input
                                        id="delivery_end_time"
                                        name="end_time"
                                        type="time"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('end_time') border-red-500 @enderror"
                                        v-model="modalWindow.end_time"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_branch_id">Branch <span class="text-gray-500 font-normal">optional</span></label>
                                    <select
                                        id="delivery_branch_id"
                                        name="branch_id"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('branch_id') border-red-500 @enderror"
                                        v-model="modalWindow.branch_id"
                                    >
                                        <option value="">All branches</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_driver_ids">Drivers</label>
                                    <select
                                        id="delivery_driver_ids"
                                        name="driver_ids[]"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('driver_ids') border-red-500 @enderror @error('driver_ids.*') border-red-500 @enderror"
                                        multiple
                                        size="4"
                                        v-model="modalWindow.driver_ids"
                                    >
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900" for="delivery_notes">Notes <span class="text-gray-500 font-normal">optional</span></label>
                                <textarea
                                    id="delivery_notes"
                                    name="notes"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('notes') border-red-500 @enderror"
                                    v-model="modalWindow.notes"
                                ></textarea>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="delivery_is_active"
                                    name="is_active"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                    v-model="modalWindow.is_active"
                                >
                                <label for="delivery_is_active" class="text-sm font-medium text-gray-900">Active Window</label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeCreate()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                                <span v-text="modalMode === 'edit' ? 'Save Changes' : 'Create Window'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
