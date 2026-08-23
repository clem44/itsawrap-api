@extends('admin.layouts.app')

@section('title', 'Rewards')
@section('header', 'Rewards')

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        createOpen: {{ $errors->any() && in_array(old('form_action'), ['create', 'edit'], true) ? 'true' : 'false' }},
        modalMode: '{{ old('form_action') === 'edit' ? 'edit' : 'create' }}',
        createAction: '{{ route('admin.rewards.store') }}',
        updateAction: '{{ route('admin.rewards.update', ['reward' => '__ID__']) }}',
        modalAction: '{{ old('form_action') === 'edit' && old('edit_id') ? route('admin.rewards.update', ['reward' => old('edit_id')]) : route('admin.rewards.store') }}',
        modalProgram: {
            id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
            name: '{{ old('form_action') ? addslashes(old('name', 'Buy 6 Wraps, Get 1 Free Wrap')) : 'Buy 6 Wraps, Get 1 Free Wrap' }}',
            earn_category_id: '{{ old('form_action') ? old('earn_category_id', '') : '' }}',
            qualifying_item_quantity_required: '{{ old('form_action') ? old('qualifying_item_quantity_required', 6) : 6 }}',
            reward_category_id: '{{ old('form_action') ? old('reward_category_id', '') : '' }}',
            reward_quantity: '{{ old('form_action') ? old('reward_quantity', 1) : 1 }}',
            starts_at: '{{ old('form_action') ? old('starts_at', '') : '' }}',
            ends_at: '{{ old('form_action') ? old('ends_at', '') : '' }}',
            is_active: {{ old('form_action') ? (old('is_active') ? 'true' : 'false') : 'true' }},
        },

        openCreate() {
            this.modalMode = 'create';
            this.modalAction = this.createAction;
            this.modalProgram = {
                id: null,
                name: 'Buy 6 Wraps, Get 1 Free Wrap',
                earn_category_id: '',
                qualifying_item_quantity_required: 6,
                reward_category_id: '',
                reward_quantity: 1,
                starts_at: '',
                ends_at: '',
                is_active: true,
            };
            this.createOpen = true;
        },

        openEdit(program) {
            this.modalMode = 'edit';
            this.modalAction = this.updateAction.replace('__ID__', program.id);
            this.modalProgram = {
                id: program.id,
                name: program.name || '',
                earn_category_id: program.earn_category_id ? String(program.earn_category_id) : '',
                qualifying_item_quantity_required: program.qualifying_item_quantity_required || 1,
                reward_category_id: program.reward_category_id ? String(program.reward_category_id) : '',
                reward_quantity: program.reward_quantity || 1,
                starts_at: program.starts_at || '',
                ends_at: program.ends_at || '',
                is_active: program.is_active === true,
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
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Reward Programs</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Configure category-based rewards and redemption rules.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Program
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Earn</th>
                    <th>Reward</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td>
                            <div class="user-name">{{ $program->name }}</div>
                            <div class="user-meta">
                                {{ $program->starts_at?->format('M d, Y') ?? 'No start date' }}
                                -
                                {{ $program->ends_at?->format('M d, Y') ?? 'No end date' }}
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $program->qualifying_item_quantity_required }} paid {{ $program->earnCategory?->name ?? 'items' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $program->reward_quantity }} free {{ $program->rewardCategory?->name ?? 'item' }}</span>
                        </td>
                        <td>
                            <span class="role-badge {{ $program->is_active ? 'admin' : 'user' }}">{{ $program->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Program"
                                    @click.stop="openEdit(@js([
                                        'id' => $program->id,
                                        'name' => $program->name,
                                        'earn_category_id' => $program->earn_category_id,
                                        'qualifying_item_quantity_required' => $program->qualifying_item_quantity_required,
                                        'reward_category_id' => $program->reward_category_id,
                                        'reward_quantity' => $program->reward_quantity,
                                        'starts_at' => $program->starts_at?->format('Y-m-d\TH:i'),
                                        'ends_at' => $program->ends_at?->format('Y-m-d\TH:i'),
                                        'is_active' => $program->is_active,
                                    ]))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.rewards.destroy', $program) }}" class="inline" onsubmit="return confirm('Delete this reward program? Programs with customer activity cannot be deleted.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Program">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v8m-4-4h8m-9 8h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No reward programs yet</h3>
                                <p class="empty-state-text">Create the first program to start tracking customer rewards.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($programs->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $programs->links() }}
        </div>
    @endif

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
                        <input type="hidden" name="edit_id" :value="modalProgram.id">

                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-xl font-semibold text-gray-900" v-text="modalMode === 'edit' ? 'Edit Program' : 'Create Program'"></h2>
                            <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeCreate()">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6 px-6 py-5 max-h-96 overflow-y-auto">
                            @if(old('form_action') === 'create' && $errors->any())
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <p class="font-semibold mb-1">Please fix the following:</p>
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900" for="create_name">Name</label>
                                <input
                                    id="create_name"
                                    name="name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    v-model="modalProgram.name"
                                    required
                                >
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="create_earn_category_id">Earn Category</label>
                                    <select
                                        id="create_earn_category_id"
                                        name="earn_category_id"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('earn_category_id') border-red-500 @enderror"
                                        v-model="modalProgram.earn_category_id"
                                        required
                                    >
                                        <option value="">Select category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="create_qualifying_item_quantity_required">Paid Items Required</label>
                                    <input
                                        id="create_qualifying_item_quantity_required"
                                        name="qualifying_item_quantity_required"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('qualifying_item_quantity_required') border-red-500 @enderror"
                                        v-model="modalProgram.qualifying_item_quantity_required"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="create_reward_category_id">Reward Category</label>
                                    <select
                                        id="create_reward_category_id"
                                        name="reward_category_id"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('reward_category_id') border-red-500 @enderror"
                                        v-model="modalProgram.reward_category_id"
                                        required
                                    >
                                        <option value="">Select category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="create_reward_quantity">Reward Quantity</label>
                                    <input
                                        id="create_reward_quantity"
                                        name="reward_quantity"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('reward_quantity') border-red-500 @enderror"
                                        v-model="modalProgram.reward_quantity"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="create_starts_at">Starts At <span class="text-gray-500 font-normal">optional</span></label>
                                    <input
                                        id="create_starts_at"
                                        name="starts_at"
                                        type="datetime-local"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('starts_at') border-red-500 @enderror"
                                        v-model="modalProgram.starts_at"
                                    >
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900" for="create_ends_at">Ends At <span class="text-gray-500 font-normal">optional</span></label>
                                    <input
                                        id="create_ends_at"
                                        name="ends_at"
                                        type="datetime-local"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('ends_at') border-red-500 @enderror"
                                        v-model="modalProgram.ends_at"
                                    >
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="create_is_active"
                                    name="is_active"
                                    type="checkbox"
                                    value="1"
                                    class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                    v-model="modalProgram.is_active"
                                >
                                <label for="create_is_active" class="text-sm font-medium text-gray-900">Active Program</label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeCreate()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors" v-text="modalMode === 'edit' ? 'Save Changes' : 'Create Program'">
                                Create Program
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
