@extends('admin.layouts.app')

@section('title', 'Statuses')
@section('header', 'Statuses')

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
        editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
        editAction: '{{ route('admin.statuses.update', ['status' => '__ID__']) }}',
        editStatus: {
            id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
            name: '{{ old('form_action') === 'edit' ? addslashes(old('name', '')) : '' }}',
            description: '{{ old('form_action') === 'edit' ? addslashes(old('description', '')) : '' }}',
        },

        openCreate() {
            this.createOpen = true;
            this.editOpen = false;
        },

        closeCreate() {
            this.createOpen = false;
        },

        openEdit(status) {
            this.editStatus = {
                id: status.id,
                name: status.name || '',
                description: status.description || '',
            };
            this.editAction = '{{ route('admin.statuses.update', ['status' => '__ID__']) }}'.replace('__ID__', status.id);
            this.editOpen = true;
            this.createOpen = false;
        },

        closeEdit() {
            this.editOpen = false;
        },

        getEditAction() {
            return this.editAction.replace('__ID__', this.editStatus.id);
        },
    });
</script>
@endpush

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Status Management</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create and maintain the order statuses used across the POS and customer order flow.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Status
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Description</th>
                    <th>Orders</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statuses as $status)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($status->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ ucfirst($status->name) }}</div>
                                    <div class="user-meta">ID {{ $status->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $status->description ? \Illuminate\Support\Str::limit($status->description, 90) : 'No description' }}</span>
                        </td>
                        <td>
                            <span class="role-badge user">{{ $status->orders_count }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $status->updated_at?->format('M d, Y') ?? 'Not updated' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Status"
                                    @click.stop="openEdit(@js($status->only(['id', 'name', 'description'])))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.statuses.destroy', $status) }}" class="inline" onsubmit="return confirm('Delete this status? Statuses assigned to orders cannot be deleted.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Status">
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
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6M8 4h8a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No statuses yet</h3>
                                <p class="empty-state-text">Create the first order status to start organizing order progress.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($statuses->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $statuses->links('admin.partials.pagination') }}
        </div>
    @endif

    <teleport to="body">
        <div
            v-show="createOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="create-status-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div v-show="createOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeCreate()"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                <div
                    v-show="createOpen"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <form method="POST" action="{{ route('admin.statuses.store') }}">
                        @csrf
                        <input type="hidden" name="form_action" value="create">

                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 id="create-status-title" class="text-xl font-semibold text-gray-900">Create Status</h2>
                            <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeCreate()">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-5 px-6 py-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900" for="create_name">Name</label>
                                <input
                                    id="create_name"
                                    name="name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    value="{{ old('form_action') === 'create' ? old('name') : '' }}"
                                    required
                                >
                                @if(old('form_action') === 'create')
                                    @error('name')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900" for="create_description">Description</label>
                                <textarea
                                    id="create_description"
                                    name="description"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror"
                                >{{ old('form_action') === 'create' ? old('description') : '' }}</textarea>
                                @if(old('form_action') === 'create')
                                    @error('description')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeCreate()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                                Create Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>

    <teleport to="body">
        <div
            v-show="editOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="edit-status-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div v-show="editOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeEdit()"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                <div
                    v-show="editOpen"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <form method="POST" :action="getEditAction()">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="edit_id" :value="editStatus.id">

                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 id="edit-status-title" class="text-xl font-semibold text-gray-900">Edit Status</h2>
                            <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeEdit()">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-5 px-6 py-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900" for="edit_name">Name</label>
                                <input
                                    id="edit_name"
                                    name="name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    v-model="editStatus.name"
                                    required
                                >
                                @if(old('form_action') === 'edit')
                                    @error('name')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900" for="edit_description">Description</label>
                                <textarea
                                    id="edit_description"
                                    name="description"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror"
                                    v-model="editStatus.description"
                                ></textarea>
                                @if(old('form_action') === 'edit')
                                    @error('description')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeEdit()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
