@extends('admin.layouts.app')

@section('title', 'Categories')
@section('header', 'Categories')

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('categoryManager', () => ({
            createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
            editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            createColor: '{{ old('form_action') === 'create' ? old('color', '') : '' }}',
            editCategory: {
                id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
                name: '{{ old('form_action') === 'edit' ? addslashes(old('name', '')) : '' }}',
                description: '{{ old('form_action') === 'edit' ? addslashes(old('description', '')) : '' }}',
                icon: '{{ old('form_action') === 'edit' ? addslashes(old('icon', '')) : '' }}',
                color: '{{ old('form_action') === 'edit' ? addslashes(old('color', '')) : '' }}',
                sort_order: '{{ old('form_action') === 'edit' ? old('sort_order', '') : '' }}'
            },
            editAction: '{{ route('admin.categories.update', ['category' => '__ID__']) }}',

            openCreate() {
                this.createOpen = true;
                this.editOpen = false;
                this.createColor = '';
            },

            closeCreate() {
                this.createOpen = false;
            },

            openEdit(category) {
                this.editCategory = {
                    id: category.id,
                    name: category.name || '',
                    description: category.description || '',
                    icon: category.icon || '',
                    color: category.color || '',
                    sort_order: category.sort_order ?? ''
                };
                this.editAction = '{{ route('admin.categories.update', ['category' => '__ID__']) }}'.replace('__ID__', category.id);
                this.editOpen = true;
                this.createOpen = false;
            },

            closeEdit() {
                this.editOpen = false;
            },

            getEditAction() {
                return this.editAction.replace('__ID__', this.editCategory.id);
            }
        }));
    });
</script>
@endpush

@section('content')
<div x-data="categoryManager">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Category Management</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create, edit, and organize menu categories in one place.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Category
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Color</th>
                    <th>Sort</th>
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($category->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $category->name }}</div>
                                    <div class="user-meta">{{ $category->icon ?: 'No icon set' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $category->description ? \Illuminate\Support\Str::limit($category->description, 80) : 'No description' }}</span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="color-preview" style="background: {{ $category->color ?: 'var(--color-cream)' }};"></span>
                                <span class="user-meta">{{ $category->color ?: 'None' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $category->sort_order ?? '--' }}</span>
                        </td>
                        <td>
                            <span class="role-badge user">{{ $category->items_count }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Category"
                                    @click.stop="openEdit(@js($category->only(['id', 'name', 'description', 'icon', 'color', 'sort_order'])))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Category">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V7a2 2 0 00-2-2h-4l-2-2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No categories yet</h3>
                                <p class="empty-state-text">Create your first category to get started.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($categories->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $categories->links() }}
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
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    <input type="hidden" name="form_action" value="create">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Create Category</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeCreate()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
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
                            <label class="mb-2 block text-sm font-medium text-white/80">Description</label>
                            <textarea
                                name="description"
                                rows="3"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror"
                            >{{ old('form_action') === 'create' ? old('description') : '' }}</textarea>
                            @if(old('form_action') === 'create')
                                @error('description')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Icon</label>
                                <input
                                    type="text"
                                    name="icon"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('icon') border-red-500 @enderror"
                                    value="{{ old('form_action') === 'create' ? old('icon') : '' }}"
                                    placeholder="e.g. wrap-icon"
                                >
                                @if(old('form_action') === 'create')
                                    @error('icon')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Sort Order</label>
                                <input
                                    type="number"
                                    name="sort_order"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('sort_order') border-red-500 @enderror"
                                    value="{{ old('form_action') === 'create' ? old('sort_order') : '' }}"
                                    min="0"
                                >
                                @if(old('form_action') === 'create')
                                    @error('sort_order')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Color</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="text"
                                    name="color"
                                    class="flex-1 rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('color') border-red-500 @enderror"
                                    placeholder="#7c9a8a"
                                    x-model="createColor"
                                >
                                <span
                                    class="h-10 w-10 flex-shrink-0 rounded-lg border border-white/20"
                                    :style="createColor ? `background: ${createColor};` : 'background: transparent;'"
                                ></span>
                            </div>
                            @if(old('form_action') === 'create')
                                @error('color')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                        <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeCreate()">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Create Category
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
                    <input type="hidden" name="edit_id" :value="editCategory.id">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Edit Category</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeEdit()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                x-model="editCategory.name"
                                required
                            >
                            @if(old('form_action') === 'edit')
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Description</label>
                            <textarea
                                name="description"
                                rows="3"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror"
                                x-model="editCategory.description"
                            ></textarea>
                            @if(old('form_action') === 'edit')
                                @error('description')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Icon</label>
                                <input
                                    type="text"
                                    name="icon"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('icon') border-red-500 @enderror"
                                    x-model="editCategory.icon"
                                    placeholder="e.g. wrap-icon"
                                >
                                @if(old('form_action') === 'edit')
                                    @error('icon')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Sort Order</label>
                                <input
                                    type="number"
                                    name="sort_order"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('sort_order') border-red-500 @enderror"
                                    x-model="editCategory.sort_order"
                                    min="0"
                                >
                                @if(old('form_action') === 'edit')
                                    @error('sort_order')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Color</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="text"
                                    name="color"
                                    class="flex-1 rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('color') border-red-500 @enderror"
                                    x-model="editCategory.color"
                                    placeholder="#7c9a8a"
                                >
                                <span
                                    class="h-10 w-10 flex-shrink-0 rounded-lg border border-white/20"
                                    :style="editCategory.color ? `background: ${editCategory.color};` : 'background: transparent;'"
                                ></span>
                            </div>
                            @if(old('form_action') === 'edit')
                                @error('color')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
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
