@extends('admin.layouts.app')

@section('title', 'Branches')
@section('header', 'Branches')

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('branchManager', () => ({
            createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
            editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            editBranch: {
                id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
                name: '{{ old('form_action') === 'edit' ? addslashes(old('name', '')) : '' }}',
                address: '{{ old('form_action') === 'edit' ? addslashes(old('address', '')) : '' }}',
                phone: '{{ old('form_action') === 'edit' ? addslashes(old('phone', '')) : '' }}',
                active: {{ old('form_action') === 'edit' ? (old('active') ? 'true' : 'false') : 'false' }}
            },
            editAction: '{{ route('admin.branches.update', ['branch' => '__ID__']) }}',

            openCreate() {
                this.createOpen = true;
                this.editOpen = false;
            },

            closeCreate() {
                this.createOpen = false;
            },

            openEdit(branch) {
                this.editBranch = {
                    id: branch.id,
                    name: branch.name || '',
                    address: branch.address || '',
                    phone: branch.phone || '',
                    active: branch.active || false
                };
                this.editAction = '{{ route('admin.branches.update', ['branch' => '__ID__']) }}'.replace('__ID__', branch.id);
                this.editOpen = true;
                this.createOpen = false;
            },

            closeEdit() {
                this.editOpen = false;
            },

            getEditAction() {
                return this.editAction.replace('__ID__', this.editBranch.id);
            }
        }));
    });
</script>
@endpush

@section('content')
<div x-data="branchManager">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Branch Management</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create, edit, and manage restaurant branches.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Branch
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Branch Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($branch->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $branch->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $branch->address ? \Illuminate\Support\Str::limit($branch->address, 50) : 'No address' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $branch->phone ?: 'No phone' }}</span>
                        </td>
                        <td>
                            <span class="role-badge user">{{ $branch->items_count }}</span>
                        </td>
                        <td>
                            @if($branch->active)
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
                                    title="Edit Branch"
                                    @click.stop="openEdit(@js($branch->only(['id', 'name', 'address', 'phone', 'active'])))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="inline" onsubmit="return confirm('Delete this branch? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Branch">
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
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No branches yet</h3>
                                <p class="empty-state-text">Create your first branch to get started.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($branches->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $branches->links() }}
        </div>
    @endif

    <!-- Create Modal -->
    <template x-teleport="body">
        <div
            x-show="createOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    x-show="createOpen"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeCreate()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    x-show="createOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" action="{{ route('admin.branches.store') }}">
                    @csrf
                    <input type="hidden" name="form_action" value="create">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Create Branch</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeCreate()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Branch Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                placeholder="e.g. Downtown, Mall, Airport"
                                value="{{ old('form_action') === 'create' ? old('name') : '' }}"
                                required
                            >
                            @if(old('form_action') === 'create')
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Address</label>
                            <textarea
                                name="address"
                                rows="3"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('address') border-red-500 @enderror"
                                placeholder="Enter the branch address"
                            >{{ old('form_action') === 'create' ? old('address') : '' }}</textarea>
                            @if(old('form_action') === 'create')
                                @error('address')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Phone</label>
                            <input
                                type="text"
                                name="phone"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('phone') border-red-500 @enderror"
                                placeholder="e.g. (555) 123-4567"
                                value="{{ old('form_action') === 'create' ? old('phone') : '' }}"
                            >
                            @if(old('form_action') === 'create')
                                @error('phone')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                id="create_active"
                                name="active"
                                class="h-4 w-4 rounded border-white/20 bg-white/5 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                {{ old('form_action') === 'create' && old('active') ? 'checked' : '' }}
                            >
                            <label for="create_active" class="text-sm font-medium text-white/80">Active Branch</label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                        <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeCreate()">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Create Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>

    <!-- Edit Modal -->
    <template x-teleport="body">
        <div
            x-show="editOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    x-show="editOpen"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeEdit()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    x-show="editOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" :action="getEditAction()">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="edit_id" :value="editBranch.id">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Edit Branch</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeEdit()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Branch Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                x-model="editBranch.name"
                                required
                            >
                            @if(old('form_action') === 'edit')
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Address</label>
                            <textarea
                                name="address"
                                rows="3"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('address') border-red-500 @enderror"
                                x-model="editBranch.address"
                            ></textarea>
                            @if(old('form_action') === 'edit')
                                @error('address')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Phone</label>
                            <input
                                type="text"
                                name="phone"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('phone') border-red-500 @enderror"
                                x-model="editBranch.phone"
                            >
                            @if(old('form_action') === 'edit')
                                @error('phone')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                id="edit_active"
                                name="active"
                                class="h-4 w-4 rounded border-white/20 bg-white/5 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                x-model="editBranch.active"
                            >
                            <label for="edit_active" class="text-sm font-medium text-white/80">Active Branch</label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                        <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeEdit()">
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
    </template>
</div>
@endsection
