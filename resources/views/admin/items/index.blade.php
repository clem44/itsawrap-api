@extends('admin.layouts.app')

@section('title', 'Items')
@section('header', 'Items')

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('itemManager', () => ({
            createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
            editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            editOptionsOpen: false,
            editItem: {
                id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
                name: '{{ old('form_action') === 'edit' ? addslashes(old('name', '')) : '' }}',
                description: '{{ old('form_action') === 'edit' ? addslashes(old('description', '')) : '' }}',
                cost: '{{ old('form_action') === 'edit' ? old('cost', '') : '' }}',
                category_id: '{{ old('form_action') === 'edit' ? old('category_id', '') : '' }}',
                image_path: '{{ old('form_action') === 'edit' ? addslashes(old('image_path', '')) : '' }}',
                short_code: '{{ old('form_action') === 'edit' ? addslashes(old('short_code', '')) : '' }}',
                active: {{ old('form_action') === 'edit' ? (old('active') ? 'true' : 'false') : 'false' }},
                options: {{ old('form_action') === 'edit' && old('options') ? json_encode(old('options')) : '[]' }}
            },
            editOptionsItem: {
                id: null,
                options: []
            },
            expandedValues: {},
            selectedOptionInModal: null,
            newDependencyOptions: {},
            createOptions: [],
            createItem: {
                name: '',
                description: '',
                cost: '',
                category_id: '',
                image_path: '',
                short_code: '',
                active: false
            },
            editAction: '{{ route('admin.items.update', ['item' => '__ID__']) }}',

            openCreate() {
                this.createOpen = true;
                this.editOpen = false;
                this.createItem = {
                    name: '',
                    description: '',
                    cost: '',
                    category_id: '',
                    image_path: '',
                    short_code: '',
                    active: false
                };
                this.createOptions = [];
            },

            closeCreate() {
                this.createOpen = false;
            },

            openEdit(item, selectedOptions) {
                this.editItem = {
                    id: item.id,
                    name: item.name || '',
                    description: item.description || '',
                    cost: item.cost || '',
                    category_id: item.category_id || '',
                    image_path: item.image_path || '',
                    short_code: item.short_code || '',
                    active: item.active || false,
                    options: selectedOptions || []
                };
                this.editAction = '{{ route('admin.items.update', ['item' => '__ID__']) }}'.replace('__ID__', item.id);
                this.editOpen = true;
                this.createOpen = false;
            },

            closeEdit() {
                this.editOpen = false;
            },

            openEditOptions(itemId, itemOptions) {
                this.editOptionsItem = {
                    id: itemId,
                    options: itemOptions
                };
                this.selectedOptionInModal = itemOptions.length > 0 ? itemOptions[0] : null;
                this.editOptionsOpen = true;
            },

            closeEditOptions() {
                this.editOptionsOpen = false;
                this.selectedOptionInModal = null;
                this.editOptionsItem = { id: null, options: [] };
            },

            getSelectedOptionValues() {
                if (!this.selectedOptionInModal) return [];
                const option = this.editOptionsItem.options.find(opt => opt.id === this.selectedOptionInModal);
                return option ? option.optionValues : [];
            },

            toggleValuePanel(valueId) {
                this.expandedValues = {
                    ...this.expandedValues,
                    [valueId]: !this.expandedValues[valueId]
                };
            },

            isValueExpanded(valueId) {
                return this.expandedValues[valueId] === true;
            },

            initializeDependencies() {
                const values = this.getSelectedOptionValues();
                values.forEach(value => {
                    if (!value.optionDependencies) {
                        value.optionDependencies = [];
                    }
                });
            },

            addDependency(valueId, childOptionId) {
                const option = this.editOptionsItem.options.find(opt => opt.id === this.selectedOptionInModal);
                if (option) {
                    const value = option.optionValues.find(v => v.id === valueId);
                    if (value) {
                        if (!value.optionDependencies) {
                            value.optionDependencies = [];
                        }
                        // Add if not already present
                        if (!value.optionDependencies.find(d => d.childOptionId === childOptionId)) {
                            value.optionDependencies.push({ childOptionId });
                        }
                        this.newDependencyOptions[valueId] = null;
                    }
                }
            },

            removeDependency(valueId, childOptionId) {
                const option = this.editOptionsItem.options.find(opt => opt.id === this.selectedOptionInModal);
                if (option) {
                    const value = option.optionValues.find(v => v.id === valueId);
                    if (value && value.optionDependencies) {
                        value.optionDependencies = value.optionDependencies.filter(d => d.childOptionId !== childOptionId);
                    }
                }
            },

            getDependencyOptionName(optionId) {
                const allOptions = this.editOptionsItem.options || [];
                const depOption = allOptions.find(opt => opt.id === optionId);
                return depOption ? depOption.name : 'Unknown';
            },

            updateOptionValuePrice(valueId, newPrice) {
                const option = this.editOptionsItem.options.find(opt => opt.id === this.selectedOptionInModal);
                if (option) {
                    const value = option.optionValues.find(v => v.id === valueId);
                    if (value) {
                        value.price = parseFloat(newPrice);
                    }
                }
            },

            async saveEditedOptions() {
                const updatedValues = {};
                const dependencies = {};
                this.editOptionsItem.options.forEach(option => {
                    option.optionValues.forEach(value => {
                        updatedValues[value.id] = parseFloat(value.price);
                        if (value.optionDependencies && value.optionDependencies.length > 0) {
                            dependencies[value.id] = value.optionDependencies.map(d => d.childOptionId);
                        }
                    });
                });

                try {
                    const response = await fetch(`/admin/items/${this.editOptionsItem.id}/option-values`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ values: updatedValues, dependencies })
                    });

                    if (response.ok) {
                        this.closeEditOptions();
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error saving option values:', error);
                    alert('Error saving option values. Please try again.');
                }
            },

            toggleCreateOption(optionId) {
                const index = this.createOptions.indexOf(optionId);
                if (index > -1) {
                    this.createOptions.splice(index, 1);
                } else {
                    this.createOptions.push(optionId);
                }
            },

            toggleEditOption(optionId) {
                const index = this.editItem.options.indexOf(optionId);
                if (index > -1) {
                    this.editItem.options.splice(index, 1);
                } else {
                    this.editItem.options.push(optionId);
                }
            },

            getEditAction() {
                return this.editAction.replace('__ID__', this.editItem.id);
            }
        }));
    });
</script>
@endpush

@section('content')
<div x-data="itemManager">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Item Management</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create, edit, and manage menu items.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Item
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Cost</th>
                    <th>Options</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($item->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $item->name }}</div>
                                    @if($item->short_code)
                                        <div class="user-meta">{{ $item->short_code }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $item->category?->name ?: 'Uncategorized' }}</span>
                        </td>
                        <td>
                            <span class="user-meta font-semibold">${{ number_format($item->cost, 2) }}</span>
                        </td>
                        <td>
                            <button
                                type="button"
                                @click.stop="openEditOptions({{ $item->id }}, @js($item->itemOptions->map(fn($io) => ['id' => $io->option_id, 'name' => $io->option->name, 'optionValues' => $io->option->optionValues->map(fn($ov) => ['id' => $ov->id, 'name' => $ov->name, 'price' => $ov->price, 'optionDependencies' => ($ov->optionDependencies?->map(fn($od) => ['childOptionId' => $od->child_id]) ?? collect())->toArray()])->toArray()])->toArray()))"
                                class="role-badge user cursor-pointer hover:opacity-80 transition-opacity"
                                title="Click to edit option values"
                            >
                                {{ $item->item_options_count }}
                            </button>
                        </td>
                        <td>
                            @if($item->active)
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
                                    title="Edit Item"
                                    @click.stop="openEdit(@js($item->only(['id', 'name', 'description', 'cost', 'category_id', 'image_path', 'short_code', 'active'])), @js($item->itemOptions->pluck('option_id')->toArray()))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.items.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this item? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Item">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No items yet</h3>
                                <p class="empty-state-text">Create your first item to get started.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $items->links() }}
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

                <!-- Modal panel - Large Size -->
                <div
                    x-show="createOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" action="{{ route('admin.items.store') }}">
                    @csrf
                    <input type="hidden" name="form_action" value="create">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h2 class="text-xl font-semibold text-gray-900">Create Item</h2>
                        <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeCreate()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-6 px-6 py-5 max-h-96 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Item Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    placeholder="e.g. Chicken Wrap"
                                    x-model="createItem.name"
                                    required
                                >
                                @if(old('form_action') === 'create')
                                    @error('name')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Cost</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="cost"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('cost') border-red-500 @enderror"
                                    placeholder="0.00"
                                    x-model="createItem.cost"
                                    required
                                >
                                @if(old('form_action') === 'create')
                                    @error('cost')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Description</label>
                            <textarea
                                name="description"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror"
                                placeholder="Enter item description"
                                x-model="createItem.description"
                            ></textarea>
                            @if(old('form_action') === 'create')
                                @error('description')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Category</label>
                                <select
                                    name="category_id"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('category_id') border-red-500 @enderror"
                                    x-model="createItem.category_id"
                                    required
                                >
                                    <option value="">Select a category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @if(old('form_action') === 'create')
                                    @error('category_id')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Short Code</label>
                                <input
                                    type="text"
                                    name="short_code"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('short_code') border-red-500 @enderror"
                                    placeholder="e.g. CW001"
                                    x-model="createItem.short_code"
                                >
                                @if(old('form_action') === 'create')
                                    @error('short_code')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Image Path</label>
                            <input
                                type="text"
                                name="image_path"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('image_path') border-red-500 @enderror"
                                placeholder="e.g. /images/items/chicken-wrap.jpg"
                                x-model="createItem.image_path"
                            >
                            @if(old('form_action') === 'create')
                                @error('image_path')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <!-- Options Selection -->
                        <div>
                            <label class="mb-3 block text-sm font-medium text-gray-900">Available Options</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @forelse($options as $option)
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :id="'create_option_' + {{ $option->id }}"
                                            name="options[]"
                                            :value="{{ $option->id }}"
                                            @change="toggleCreateOption({{ $option->id }})"
                                            :checked="createOptions.includes({{ $option->id }})"
                                            class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                        >
                                        <label :for="'create_option_' + {{ $option->id }}" class="ml-2 block text-sm text-gray-700">
                                            {{ $option->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No options available</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                id="create_active"
                                name="active"
                                class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                x-model="createItem.active"
                            >
                            <label for="create_active" class="text-sm font-medium text-gray-900">Active Item</label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                        <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeCreate()">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Create Item
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

                <!-- Modal panel - Large Size -->
                <div
                    x-show="editOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" :action="getEditAction()">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="edit_id" :value="editItem.id">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h2 class="text-xl font-semibold text-gray-900">Edit Item</h2>
                        <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeEdit()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-6 px-6 py-5 max-h-96 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Item Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    x-model="editItem.name"
                                    required
                                >
                                @if(old('form_action') === 'edit')
                                    @error('name')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Cost</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="cost"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('cost') border-red-500 @enderror"
                                    x-model="editItem.cost"
                                    required
                                >
                                @if(old('form_action') === 'edit')
                                    @error('cost')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Description</label>
                            <textarea
                                name="description"
                                rows="3"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror"
                                x-model="editItem.description"
                            ></textarea>
                            @if(old('form_action') === 'edit')
                                @error('description')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Category</label>
                                <select
                                    name="category_id"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('category_id') border-red-500 @enderror"
                                    x-model="editItem.category_id"
                                    required
                                >
                                    <option value="">Select a category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @if(old('form_action') === 'edit')
                                    @error('category_id')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Short Code</label>
                                <input
                                    type="text"
                                    name="short_code"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('short_code') border-red-500 @enderror"
                                    x-model="editItem.short_code"
                                >
                                @if(old('form_action') === 'edit')
                                    @error('short_code')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Image Path</label>
                            <input
                                type="text"
                                name="image_path"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('image_path') border-red-500 @enderror"
                                x-model="editItem.image_path"
                            >
                            @if(old('form_action') === 'edit')
                                @error('image_path')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <!-- Options Selection -->
                        <div>
                            <label class="mb-3 block text-sm font-medium text-gray-900">Available Options</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @forelse($options as $option)
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :id="'edit_option_' + {{ $option->id }}"
                                            name="options[]"
                                            :value="{{ $option->id }}"
                                            @change="toggleEditOption({{ $option->id }})"
                                            :checked="editItem.options.includes({{ $option->id }})"
                                            class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                        >
                                        <label :for="'edit_option_' + {{ $option->id }}" class="ml-2 block text-sm text-gray-700">
                                            {{ $option->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No options available</p>
                                @endforelse
                            </div>
                            <input type="hidden" name="options" :value="JSON.stringify(editItem.options)">
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                id="edit_active"
                                name="active"
                                class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                x-model="editItem.active"
                            >
                            <label for="edit_active" class="text-sm font-medium text-gray-900">Active Item</label>
                        </div>
                    </div>

                    <!-- Footer -->
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
    </template>

    <!-- Edit Options Modal -->
    <template x-teleport="body">
        <div
            x-show="editOptionsOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    x-show="editOptionsOpen"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeEditOptions()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel - Large Size for Two Columns -->
                <div
                    x-show="editOptionsOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-5xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h2 class="text-xl font-semibold text-gray-900">Edit Option Values</h2>
                        <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="closeEditOptions()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body with Two Columns -->
                    <div class="h-96 grid grid-cols-5 gap-0 divide-x divide-gray-200">
                        <!-- Column 1: Options List (5/12) -->
                        <div class="col-span-2 overflow-y-auto px-6 py-5">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900">Options</h3>
                            <div class="space-y-2">
                                <template x-for="option in editOptionsItem.options" :key="option.id">
                                    <button
                                        type="button"
                                        @click.stop="selectedOptionInModal = option.id"
                                        :class="{
                                            'bg-[var(--color-sage)] text-white': selectedOptionInModal === option.id,
                                            'bg-gray-100 text-gray-900 hover:bg-gray-200': selectedOptionInModal !== option.id
                                        }"
                                        class="w-full text-left rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                                    >
                                        <span x-text="option.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Column 2: Option Values (7/12) -->
                        <div class="col-span-3 overflow-y-auto px-6 py-5">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900">Values & Prices</h3>
                            <template x-if="selectedOptionInModal && getSelectedOptionValues().length > 0">
                                <div class="space-y-2">
                                    <template x-for="value in getSelectedOptionValues()" :key="value.id">
                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                            <!-- Collapsed Header -->
                                            <button
                                                type="button"
                                                @click.stop="toggleValuePanel(value.id)"
                                                class="w-full flex items-center gap-2 px-3 py-2.5 hover:bg-gray-50 transition-colors"
                                            >
                                                <svg class="w-4 h-4 text-gray-600 transition-transform" :class="{ 'rotate-90': isValueExpanded(value.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                                <div class="flex-1 text-left">
                                                    <p class="text-sm font-medium text-gray-900" x-text="value.name"></p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500">Price:</span>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        :value="value.price"
                                                        @input.stop="updateOptionValuePrice(value.id, $event.target.value)"
                                                        @click.stop
                                                        class="w-20 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                                        placeholder="0.00"
                                                    >
                                                    <button
                                                        type="button"
                                                        @click.stop="console.log('Delete would go here')"
                                                        class="text-gray-400 hover:text-red-600 transition-colors p-1"
                                                        title="Delete option value"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                  
                                                </div>
                                            </button>

                                            <!-- Expanded Content -->
                                            <div x-show="isValueExpanded(value.id)" x-transition class="border-t border-gray-200 px-3 py-3 bg-gray-50 space-y-3">
                                                <div>
                                                    <h4 class="text-xs font-semibold text-gray-700 mb-2">Dependent Options</h4>
                                                    <div class="space-y-2">
                                                        <template x-for="dep in (value.optionDependencies || [])" :key="dep.childOptionId">
                                                            <div class="flex items-center justify-between bg-white rounded px-2 py-1.5 text-sm">
                                                                <span class="text-gray-700" x-text="getDependencyOptionName(dep.childOptionId)"></span>
                                                                <button
                                                                    type="button"
                                                                    @click.stop="removeDependency(value.id, dep.childOptionId)"
                                                                    class="text-gray-400 hover:text-red-600 transition-colors p-1"
                                                                    title="Remove dependency"
                                                                >
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Add New Dependency -->
                                                <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
                                                    <button
                                                        type="button"
                                                        @click.stop="addDependency(value.id, newDependencyOptions[value.id]); initializeDependencies();"
                                                        :disabled="!newDependencyOptions[value.id]"
                                                        class="px-3 py-1.5 text-xs font-medium bg-[var(--color-sage)] text-white rounded-lg hover:bg-opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                                    >
                                                        Add
                                                    </button>
                                                    <select
                                                        x-model="newDependencyOptions[value.id]"
                                                        @change.stop
                                                        class="flex-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                                    >
                                                        <option value="">Select an option</option>
                                                        <template x-for="opt in editOptionsItem.options" :key="opt.id">
                                                            <option :value="opt.id" x-text="opt.name"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="selectedOptionInModal && getSelectedOptionValues().length === 0">
                                <p class="text-sm text-gray-500">No option values available for this option.</p>
                            </template>
                            <template x-if="!selectedOptionInModal">
                                <p class="text-sm text-gray-500">Select an option to view its values.</p>
                            </template>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                        <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeEditOptions()">
                            Cancel
                        </button>
                        <button type="button" @click="saveEditedOptions()" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
