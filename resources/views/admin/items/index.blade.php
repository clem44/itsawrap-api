@extends('admin.layouts.app')

@section('title', 'Items')
@section('header', 'Items')



@push('scripts')

<script>
    window.AdminVuePage = () => ({
            createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
            editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            editOptionsOpen: false,
            categories: @js($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values()),
            editItemErrors: @js(old('form_action') === 'edit' ? $errors->getMessages() : []),
            editItemErrorList: @js(old('form_action') === 'edit' ? $errors->all() : []),
            editItemShowErrors: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            editItem: {
                id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
                name: '{{ old('form_action') === 'edit' ? addslashes(old('name', '')) : '' }}',
                description: '{{ old('form_action') === 'edit' ? addslashes(old('description', '')) : '' }}',
                cost: '{{ old('form_action') === 'edit' ? old('cost', '') : '' }}',
                category_id: '{{ old('form_action') === 'edit' ? old('category_id', '') : '' }}',
                image_path: '{{ old('form_action') === 'edit' ? addslashes(old('image_path', '')) : '' }}',
                short_code: '{{ old('form_action') === 'edit' ? addslashes(old('short_code', '')) : '' }}',
                active: {{ old('form_action') === 'edit' ? (old('active') ? 'true' : 'false') : 'false' }},
                options: @js(old('form_action') === 'edit' ? collect(old('options', []))->map(fn ($optionId) => (int) $optionId)->all() : [])
            },
            editOptionsItem: {
                id: null,
                options: []
            },
            allOptions: @js($allOptions),
            expandedOptions: {},
            expandedValues: {},
            selectedOptionInModal: null,
            newDependencyOptions: {},
            createOptions: @js(old('form_action') === 'create' ? collect(old('options', []))->map(fn ($optionId) => (int) $optionId)->all() : []),
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
            itemOptionRoute: '{{ url('/admin/items/__ITEM__/item-options/__ITEM_OPTION__') }}',
            itemOptionOrderRoute: '{{ url('/admin/items/__ITEM__/item-options/order') }}',
            itemsData: @js($itemsData),
            draggedItemOptionId: null,

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

            openEditOptions(itemId) {
                const item = this.itemsData.find((entry) => entry.id === itemId);
                const normalizedOptions = item ? item.itemOptions : [];
                this.editOptionsItem = {
                    id: itemId,
                    options: normalizedOptions
                };
                const firstPrimaryOption = normalizedOptions.find(option => option.type !== 'dependent');
                this.selectedOptionInModal = firstPrimaryOption ? firstPrimaryOption.id : null;
                this.expandedOptions = {};
                // Initialize expandedValues for all values
                this.expandedValues = {};
                normalizedOptions.forEach(option => {
                    this.expandedOptions[option.id] = false;
                    (option.optionValues || []).forEach(value => {
                        this.expandedValues[value.id] = false;
                    });
                });
                if (firstPrimaryOption) {
                    this.expandedOptions[firstPrimaryOption.id] = true;
                }
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

            getVisibleItemOptions() {
                return (this.editOptionsItem.options || []).filter(option => option.type !== 'dependent');
            },

            getDependencyOptionValues(parentValueId, childOptionId) {
                const option = this.editOptionsItem.options.find(opt => opt.id === this.selectedOptionInModal);
                if (!option) return [];
                const value = option.optionValues.find(v => v.id === parentValueId);
                if (!value) return [];
                const dependency = (value.optionDependencies || []).find(d => d.childOptionId === childOptionId);
                return dependency ? (dependency.optionValues || []) : [];
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

            toggleOptionPanel(optionId) {
                this.expandedOptions = {
                    ...this.expandedOptions,
                    [optionId]: !this.expandedOptions[optionId]
                };
                this.selectedOptionInModal = optionId;
            },

            isOptionExpanded(optionId) {
                return this.expandedOptions[optionId] === true;
            },

            startDraggingItemOption(option) {
                this.draggedItemOptionId = option.itemOptionId;
            },

            stopDraggingItemOption() {
                this.draggedItemOptionId = null;
            },

            async dropItemOptionBefore(targetOption) {
                if (!this.draggedItemOptionId || this.draggedItemOptionId === targetOption.itemOptionId) {
                    this.stopDraggingItemOption();
                    return;
                }

                const previousOptions = [...this.editOptionsItem.options];
                const draggedIndex = this.editOptionsItem.options.findIndex(option => option.itemOptionId === this.draggedItemOptionId);
                const targetIndex = this.editOptionsItem.options.findIndex(option => option.itemOptionId === targetOption.itemOptionId);

                if (draggedIndex < 0 || targetIndex < 0) {
                    this.stopDraggingItemOption();
                    return;
                }

                const [draggedOption] = this.editOptionsItem.options.splice(draggedIndex, 1);
                const insertIndex = this.editOptionsItem.options.findIndex(option => option.itemOptionId === targetOption.itemOptionId);
                this.editOptionsItem.options.splice(insertIndex, 0, draggedOption);

                try {
                    await this.persistItemOptionOrder();
                } catch (error) {
                    this.editOptionsItem.options = previousOptions;
                    console.error('Error updating item option order:', error);
                    alert('Failed to update option order. Please try again.');
                } finally {
                    this.stopDraggingItemOption();
                }
            },

            async persistItemOptionOrder() {
                const visibleOptions = this.getVisibleItemOptions();
                const response = await fetch(
                    this.itemOptionOrderRoute.replace('__ITEM__', this.editOptionsItem.id),
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            item_options: visibleOptions.map(option => option.itemOptionId)
                        })
                    }
                );

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to update option order.');
                }

                (result.item_options || []).forEach((orderedOption) => {
                    const option = this.editOptionsItem.options.find(itemOption => itemOption.itemOptionId === orderedOption.id);
                    if (option) {
                        option.sort_order = orderedOption.sort_order;
                    }
                });
            },

            async updateOptionEnableQty(option, enabled) {
                const previous = option.enable_qty;
                option.enable_qty = enabled;
                try {
                    const response = await fetch(
                        this.itemOptionRoute
                            .replace('__ITEM__', this.editOptionsItem.id)
                            .replace('__ITEM_OPTION__', option.itemOptionId),
                        {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                enable_qty: enabled
                            })
                        }
                    );

                    if (!response.ok) {
                        option.enable_qty = previous;
                        alert('Failed to update quantity setting. Please try again.');
                    }
                } catch (error) {
                    option.enable_qty = previous;
                    console.error('Error updating item option:', error);
                    alert('Failed to update quantity setting. Please try again.');
                }
            },
            
            async updateOptionEnableRange(option, enabled) {
                const previous = option.range;
                option.range = enabled;
                try {
                    const response = await fetch(
                        this.itemOptionRoute
                            .replace('__ITEM__', this.editOptionsItem.id)
                            .replace('__ITEM_OPTION__', option.itemOptionId),
                        {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                range: enabled,
                                enable_qty: option.enable_qty,
                                min: option.min,
                                max: option.max
                            })
                        }
                    );

                    if (!response.ok) {
                        option.range = previous;
                        alert('Failed to update range setting. Please try again.');
                    }
                } catch (error) {
                    option.range = previous;
                    console.error('Error updating item option:', error);
                    alert('Failed to update range setting. Please try again.');
                }
            },

            async updateOptionRangeBound(option, bound, value) {
                const previous = option[bound];
                option[bound] = value === '' ? null : parseInt(value, 10);

                try {
                    const response = await fetch(
                        this.itemOptionRoute
                            .replace('__ITEM__', this.editOptionsItem.id)
                            .replace('__ITEM_OPTION__', option.itemOptionId),
                        {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                range: option.range,
                                enable_qty: option.enable_qty,
                                min: option.min,
                                max: option.max
                            })
                        }
                    );

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        option[bound] = previous;
                        alert(result.message || 'Failed to update range limits. Please try again.');
                        return;
                    }

                    option.min = result.min;
                    option.max = result.max;
                } catch (error) {
                    option[bound] = previous;
                    console.error('Error updating item option range limits:', error);
                    alert('Failed to update range limits. Please try again.');
                }
            },

            async deleteItemOption(option) {
                if (!confirm('Delete this item option? This will remove its option values and dependencies.')) {
                    return;
                }

                try {
                    const response = await fetch(
                        this.itemOptionRoute
                            .replace('__ITEM__', this.editOptionsItem.id)
                            .replace('__ITEM_OPTION__', option.itemOptionId),
                        {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        }
                    );

                    if (response.ok) {
                        this.editOptionsItem.options = this.editOptionsItem.options.filter(opt => opt.id !== option.id);
                        delete this.expandedOptions[option.id];
                        if (this.selectedOptionInModal === option.id) {
                            const fallback = this.getVisibleItemOptions()[0];
                            this.selectedOptionInModal = fallback ? fallback.id : null;
                        }
                    } else {
                        alert('Failed to delete item option. Please try again.');
                    }
                } catch (error) {
                    console.error('Error deleting item option:', error);
                    alert('Failed to delete item option. Please try again.');
                }
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
                            const sourceOption = this.allOptions.find(opt => opt.id === parseInt(childOptionId));
                            value.optionDependencies.push({
                                childOptionId,
                                optionValues: sourceOption
                                    ? sourceOption.optionValues.map(depValue => ({
                                        id: depValue.id,
                                        name: depValue.name,
                                        price: depValue.price,
                                        itemOptionValueId: null
                                    }))
                                    : []
                            });
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
                const depOption = this.allOptions.find(opt => opt.id === parseInt(optionId));
                return depOption ? depOption.name : 'Unknown';
            },

            updateDependencyValuePrice(parentValueId, childOptionId, childValueId, newPrice) {
                const option = this.editOptionsItem.options.find(opt => opt.id === this.selectedOptionInModal);
                if (!option) return;
                const value = option.optionValues.find(v => v.id === parentValueId);
                if (!value) return;
                const dependency = (value.optionDependencies || []).find(d => d.childOptionId === childOptionId);
                if (!dependency) return;
                const childValue = (dependency.optionValues || []).find(v => v.id === childValueId);
                if (childValue) {
                    childValue.price = parseFloat(newPrice);
                }
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
                const dependencyValues = {};
                const dependencyValueOverrides = {};
                this.getVisibleItemOptions().forEach(option => {
                    option.optionValues.forEach(value => {
                        updatedValues[value.id] = parseFloat(value.price);
                        if (value.optionDependencies && value.optionDependencies.length > 0) {
                            dependencies[value.id] = value.optionDependencies.map(d => d.childOptionId);
                            value.optionDependencies.forEach(dep => {
                                if (!dependencyValueOverrides[value.id]) {
                                    dependencyValueOverrides[value.id] = {};
                                }
                                if (!dependencyValueOverrides[value.id][dep.childOptionId]) {
                                    dependencyValueOverrides[value.id][dep.childOptionId] = {};
                                }
                                (dep.optionValues || []).forEach(depValue => {
                                    if (depValue.itemOptionValueId) {
                                        dependencyValues[depValue.itemOptionValueId] = parseFloat(depValue.price);
                                    } else {
                                        dependencyValueOverrides[value.id][dep.childOptionId][depValue.id] = parseFloat(depValue.price);
                                    }
                                });
                            });
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
                        body: JSON.stringify({
                            values: updatedValues,
                            dependencies,
                            dependency_values: dependencyValues,
                            dependency_value_overrides: dependencyValueOverrides
                        })
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
    });
</script>
@endpush

@section('content')
<div>
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

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" action="{{ route('admin.items.index') }}" class="flex items-center gap-2">
            <label for="category_filter" class="text-sm font-medium text-gray-700">Category</label>
            <select
                id="category_filter"
                name="category_id"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                onchange="this.form.submit()"
            >
                <option value="">All</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </form>
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
                                @click.stop="openEditOptions({{ $item->id }})"
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
            {{ $items->links('admin.partials.pagination') }}
        </div>
    @endif

    <!-- Create Modal -->
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
                <!-- Backdrop -->
                <div
                    v-show="createOpen"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeCreate()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel - Large Size -->
                <div
                    v-show="createOpen"
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
                        @if(old('form_action') === 'edit' && $errors->any())
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
                                <label class="mb-2 block text-sm font-medium text-gray-900">Item Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    placeholder="e.g. Chicken Wrap"
                                    v-model="createItem.name"
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
                                    v-model="createItem.cost"
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
                                v-model="createItem.description"
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
                                    v-model="createItem.category_id"
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
                                    v-model="createItem.short_code"
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
                                v-model="createItem.image_path"
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
                            <template v-for="optionId in createOptions" :key="'create_option_input_' + optionId">
                                <input type="hidden" name="options[]" :value="optionId">
                            </template>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @forelse($options as $option)
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :id="'create_option_' + {{ $option->id }}"
                                            value="{{ $option->id }}"
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
                                v-model="createItem.active"
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
    </teleport>

    <edit-item-modal
        :action="getEditAction()"
        :categories="categories"
        :csrf-token="csrfToken"
        :error-list="editItemErrorList"
        :errors="editItemErrors"
        :item="editItem"
        :open="editOpen"
        :options="allOptions"
        :show-errors="editItemShowErrors"
        @close="closeEdit()"
    ></edit-item-modal>

    <!-- Edit Options Modal -->
    <teleport to="body">
        <div v-show="editOptionsOpen" v-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    v-show="editOptionsOpen"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-0"
                    @click="closeEditOptions()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel - Large Size for Two Columns -->
                <div
                    v-show="editOptionsOpen"
                    class="relative z-10 inline-block w-full max-w-5xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
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
                                <template v-for="option in getVisibleItemOptions()" :key="option.itemOptionId">
                                    <div
                                        class="border border-gray-200 rounded-lg overflow-hidden transition-shadow"
                                        :class="{ 'ring-2 ring-[var(--color-sage)] shadow-md': draggedItemOptionId === option.itemOptionId }"
                                        draggable="true"
                                        @dragstart="startDraggingItemOption(option)"
                                        @dragend="stopDraggingItemOption()"
                                        @dragover.prevent
                                        @drop.prevent="dropItemOptionBefore(option)"
                                    >
                                        <button
                                            type="button"
                                            @click.stop="toggleOptionPanel(option.id)"
                                            :class="{
                                                'bg-[var(--color-sage)] text-white': selectedOptionInModal === option.id,
                                                'bg-gray-100 text-gray-900 hover:bg-gray-200': selectedOptionInModal !== option.id
                                            }"
                                            class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-sm font-medium transition-colors"
                                        >
                                            <span class="shrink-0 cursor-grab text-gray-400" title="Drag to reorder">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"></path>
                                                </svg>
                                            </span>
                                            <span class="min-w-0 flex-1 text-left" v-text="option.name"></span>
                                            <svg class="shrink-0 w-4 h-4 transition-transform" :class="{ 'rotate-90': isOptionExpanded(option.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                        <div v-show="isOptionExpanded(option.id)" class="border-t border-gray-200 bg-white px-3 py-3 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-gray-700">Enable quantity</span>
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        class="sr-only peer"
                                                        :checked="option.enable_qty"
                                                        @change="updateOptionEnableQty(option, $event.target.checked)"
                                                    >
                                                    <div class="relative w-9 h-5 rounded-full bg-gray-200 transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--color-sage)] peer-checked:bg-[var(--color-sage)] after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4"></div>
                                                </label>
                                            </div>
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-gray-700">Enable Range</span>
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        class="sr-only peer"
                                                        :checked="option.range"
                                                        @change="updateOptionEnableRange(option, $event.target.checked)"
                                                    >
                                                    <div class="relative w-9 h-5 rounded-full bg-gray-200 transition-colors peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--color-sage)] peer-checked:bg-[var(--color-sage)] after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4"></div>
                                                </label>
                                            </div>
                                            <div v-if="option.range" class="grid grid-cols-2 gap-3">
                                                <label class="block">
                                                    <span class="text-xs text-gray-500">Min</span>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        :value="option.min"
                                                        @change.stop="updateOptionRangeBound(option, 'min', $event.target.value)"
                                                        @click.stop
                                                        class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                                        placeholder="0"
                                                    >
                                                </label>
                                                <label class="block">
                                                    <span class="text-xs text-gray-500">Max</span>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        :value="option.max"
                                                        @change.stop="updateOptionRangeBound(option, 'max', $event.target.value)"
                                                        @click.stop
                                                        class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                                        placeholder="0"
                                                    >
                                                </label>
                                            </div>
                                            <div class="flex items-center justify-end">
                                                    <button
                                                        type="button"
                                                        @click.stop="deleteItemOption(option)"
                                                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Delete option
                                                    </button>
                                                   
                                            </div>
                                            
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Column 2: Option Values (7/12) -->
                        <div class="col-span-3 overflow-y-auto px-6 py-5">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900">Values & Prices</h3>
                            <template v-if="selectedOptionInModal && getSelectedOptionValues().length > 0">
                                <div class="space-y-2">
                                    <template v-for="value in getSelectedOptionValues()" :key="value.id">
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
                                                    <p class="text-sm font-medium text-gray-900" v-text="value.name"></p>
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
                                                    <a
                                                        type="button"
                                                        @click.stop="console.log('Delete would go here')"
                                                        class="text-gray-400 hover:text-red-600 transition-colors p-1"
                                                        title="Delete value"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </a>
                                                  
                                                </div>
                                            </button>

                                            <!-- Expanded Content -->
                                            <div v-show="isValueExpanded(value.id)" class="border-t border-gray-200 px-3 py-3 bg-gray-50 space-y-3">
                                                <div>
                                                    <h4 class="text-xs font-semibold text-gray-700 mb-2">Dependent Options</h4>
                                                    <div class="space-y-3">
                                                        <template v-for="dep in (value.optionDependencies || [])" :key="dep.childOptionId">
                                                            <div class="bg-white rounded border border-gray-200 p-2">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-sm font-medium text-gray-900" v-text="getDependencyOptionName(dep.childOptionId)"></span>
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
                                                                <div class="space-y-1">
                                                                    <template v-for="depVal in getDependencyOptionValues(value.id, dep.childOptionId)" :key="depVal.id">
                                                                        <div class="flex items-center justify-between bg-gray-50 rounded px-2 py-1 text-xs">
                                                                            <span class="text-gray-700" v-text="depVal.name"></span>
                                                                            <input
                                                                                type="number"
                                                                                step="0.01"
                                                                                min="0"
                                                                                :value="depVal.price"
                                                                                @input.stop="updateDependencyValuePrice(value.id, dep.childOptionId, depVal.id, $event.target.value)"
                                                                                class="w-16 rounded border border-gray-200 bg-white px-1.5 py-0.5 text-xs text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                                                                placeholder="0.00"
                                                                            >
                                                                        </div>
                                                                    </template>
                                                                </div>
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
                                                        v-model="newDependencyOptions[value.id]"
                                                        @change.stop
                                                        class="flex-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                                    >
                                                        <option value="">Select an option</option>
                                                        <template v-for="opt in allOptions" :key="opt.id">
                                                            <option :value="opt.id" v-text="opt.name"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template v-if="selectedOptionInModal && getSelectedOptionValues().length === 0">
                                <p class="text-sm text-gray-500">No option values available for this option.</p>
                            </template>
                            <template v-if="!selectedOptionInModal">
                                <p class="text-sm text-gray-500">Select an option to view its values.</p>
                            </template>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                        <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="closeEditOptions()">
                            Cancel
                        </button>
                        <button type="button" @click="saveEditedOptions()" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-light)] transition-colors">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
