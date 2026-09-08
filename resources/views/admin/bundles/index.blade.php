@extends('admin.layouts.app')

@section('title', 'Bundles')
@section('header', 'Bundles')

@push('styles')
<style>
    .bundle-modal-select {
        background-color: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }

    .bundle-modal-select option {
        background-color: #ffffff;
        color: var(--color-ink);
    }
</style>
@endpush

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        modalOpen: {{ $errors->any() && in_array(old('form_action'), ['create', 'edit'], true) ? 'true' : 'false' }},
        modalMode: '{{ old('form_action') === 'edit' ? 'edit' : 'create' }}',
        createAction: '{{ route('admin.bundles.store') }}',
        updateAction: '{{ route('admin.bundles.update', ['bundle' => '__ID__']) }}',
        modalAction: '{{ old('form_action') === 'edit' && old('edit_id') ? route('admin.bundles.update', ['bundle' => old('edit_id')]) : route('admin.bundles.store') }}',
        itemOptionsByItemId: @js($items->mapWithKeys(fn ($item) => [(string) $item->id => $item->itemOptions->map(fn ($itemOption) => [
            'id' => $itemOption->id,
            'name' => $itemOption->option?->name,
            'values' => $itemOption->itemOptionValues->map(fn ($itemOptionValue) => [
                'id' => $itemOptionValue->option_value_id,
                'name' => $itemOptionValue->optionValue?->name,
                'price' => $itemOptionValue->price,
            ])->values()->all(),
        ])->values()->all()])->all()),
        bundle: {
            id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
            name: @js(old('form_action') ? old('name', '') : ''),
            description: @js(old('form_action') ? old('description', '') : ''),
            starts_at: '{{ old('form_action') ? old('starts_at', '') : '' }}',
            ends_at: '{{ old('form_action') ? old('ends_at', '') : '' }}',
            is_active: {{ old('form_action') ? (old('is_active') ? 'true' : 'false') : 'true' }},
            sort_order: '{{ old('form_action') ? old('sort_order', 0) : 0 }}',
            media_id: '{{ old('form_action') ? old('media_id', '') : '' }}',
            primary_media: @js(old('form_action') ? $oldMedia : null),
            items: @js(old('form_action') ? old('items', [['item_id' => '', 'quantity' => 1, 'sort_order' => 0, 'price_override' => '', 'label_override' => '', 'option_values' => []]]) : [['item_id' => '', 'quantity' => 1, 'sort_order' => 0, 'price_override' => '', 'label_override' => '', 'option_values' => []]]),
        },

        initDatepickers() {
            this.$nextTick(() => {
                document.querySelectorAll('[data-flatpickr-datetime]').forEach((input) => {
                    input._flatpickr?.destroy();
                    window.flatpickr(input, {
                        altInput: true,
                        altFormat: 'M j, Y h:i K',
                        dateFormat: 'Y-m-d\\TH:i',
                        enableTime: true,
                        time_24hr: false,
                    });
                });
            });
        },

        emptyItem(index = 0) {
            return {
                item_id: '',
                quantity: 1,
                sort_order: index,
                price_override: '',
                label_override: '',
                option_values: [],
            };
        },

        normalizeItems(items) {
            const rows = (items || []).map((item, index) => ({
                item_id: item.item_id ? String(item.item_id) : '',
                quantity: item.quantity || 1,
                sort_order: item.sort_order ?? index,
                price_override: item.price_override || '',
                label_override: item.label_override || '',
                option_values: item.option_values || [],
            }));

            return rows.length ? rows : [this.emptyItem()];
        },

        openCreate() {
            this.modalMode = 'create';
            this.modalAction = this.createAction;
            this.bundle = {
                id: null,
                name: '',
                description: '',
                starts_at: '',
                ends_at: '',
                is_active: true,
                sort_order: 0,
                media_id: '',
                primary_media: null,
                items: [this.emptyItem()],
            };
            this.modalOpen = true;
            this.initDatepickers();
        },

        openEdit(bundle) {
            this.modalMode = 'edit';
            this.modalAction = this.updateAction.replace('__ID__', bundle.id);
            this.bundle = {
                id: bundle.id,
                name: bundle.name || '',
                description: bundle.description || '',
                starts_at: bundle.starts_at || '',
                ends_at: bundle.ends_at || '',
                is_active: bundle.is_active === true,
                sort_order: bundle.sort_order || 0,
                media_id: bundle.media_id || '',
                primary_media: bundle.primary_media || null,
                items: this.normalizeItems(bundle.items),
            };
            this.modalOpen = true;
            this.initDatepickers();
        },

        closeModal() {
            this.modalOpen = false;
        },

        addItem() {
            this.bundle.items.push(this.emptyItem(this.bundle.items.length));
        },

        removeItem(index) {
            if (this.bundle.items.length === 1) {
                this.bundle.items = [this.emptyItem()];
                return;
            }

            this.bundle.items.splice(index, 1);
        },

        itemOptionsFor(bundleItem) {
            return this.itemOptionsByItemId[bundleItem.item_id] || [];
        },

        optionValuesFor(bundleItem, optionValue) {
            const itemOption = this.itemOptionsFor(bundleItem).find((option) => String(option.id) === String(optionValue.item_option_id));

            return itemOption ? itemOption.values : [];
        },

        addOptionValue(bundleItem) {
            bundleItem.option_values = bundleItem.option_values || [];
            bundleItem.option_values.push({
                item_option_id: '',
                option_value_id: '',
                quantity: 1,
                parent_option_value_id: '',
                price_override: '',
            });
        },

        removeOptionValue(bundleItem, optionIndex) {
            bundleItem.option_values.splice(optionIndex, 1);
        },

        clearMedia() {
            this.bundle.media_id = '';
            this.bundle.primary_media = null;
        },

        openMediaPicker() {
            window.MediaLibraryPicker.open({
                selectedMediaId: this.bundle.primary_media ? this.bundle.primary_media.id : null,
                accept: ['image'],
                onSelect: (media) => {
                    const selected = Array.isArray(media) ? media[0] : media;
                    this.bundle.primary_media = selected;
                    this.bundle.media_id = selected.id;
                },
            });
        },
    });
</script>
@endpush

@section('content')
<div class="users-container">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Bundles</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Build reusable item combinations for customer ordering and bundle offers.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Bundle
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Bundle</th>
                    <th>Items</th>
                    <th>Window</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bundles as $bundle)
                    @php
                        $primaryMedia = $bundle->firstMedia(\App\Models\Bundle::IMAGE_TAG);
                        $itemSummary = $bundle->bundleItems
                            ->map(fn ($bundleItem) => ($bundleItem->label_override ?: $bundleItem->item?->name) . ' x' . $bundleItem->quantity)
                            ->implode(' + ');
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($primaryMedia)
                                    <div class="user-avatar overflow-hidden" style="background: var(--color-cream);">
                                        <img src="{{ $primaryMedia->getUrl() }}" alt="{{ $primaryMedia->alt ?: $bundle->name }}" class="h-full w-full object-cover">
                                    </div>
                                @else
                                    <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.18) 0%, rgba(207, 126, 95, 0.55) 100%); color: var(--color-forest);">
                                        {{ strtoupper(substr($bundle->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="user-name">{{ $bundle->name }}</div>
                                    <div class="user-meta">{{ \Illuminate\Support\Str::limit($bundle->description ?: 'No description', 72) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $itemSummary ?: 'No items configured' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">
                                {{ $bundle->starts_at?->format('M d, Y') ?? 'No start' }}
                                -
                                {{ $bundle->ends_at?->format('M d, Y') ?? 'No end' }}
                            </span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $bundle->sort_order }}</span>
                        </td>
                        <td>
                            <span class="role-badge {{ $bundle->is_active ? 'admin' : 'user' }}">{{ $bundle->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Bundle"
                                    @click.stop="openEdit(@js([
                                        'id' => $bundle->id,
                                        'name' => $bundle->name,
                                        'description' => $bundle->description,
                                        'starts_at' => $bundle->starts_at?->format('Y-m-d\TH:i'),
                                        'ends_at' => $bundle->ends_at?->format('Y-m-d\TH:i'),
                                        'is_active' => $bundle->is_active,
                                        'sort_order' => $bundle->sort_order,
                                        'media_id' => $primaryMedia?->id,
                                        'primary_media' => $primaryMedia ? $mediaPresenter->present($primaryMedia) : null,
                                        'items' => $bundle->bundleItems->map(fn ($bundleItem) => [
                                            'item_id' => $bundleItem->item_id,
                                            'quantity' => $bundleItem->quantity,
                                            'sort_order' => $bundleItem->sort_order,
                                            'price_override' => $bundleItem->price_override,
                                            'label_override' => $bundleItem->label_override,
                                            'option_values' => $bundleItem->optionValues->map(fn ($optionValue) => [
                                                'item_option_id' => $optionValue->item_option_id,
                                                'option_value_id' => $optionValue->option_value_id,
                                                'quantity' => $optionValue->quantity,
                                                'parent_option_value_id' => $optionValue->parent_option_value_id,
                                                'price_override' => $optionValue->price_override,
                                            ])->values()->all(),
                                        ])->values()->all(),
                                    ]))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.bundles.destroy', $bundle) }}" class="inline" onsubmit="return confirm('Delete this bundle?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Bundle">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0H4m16 0l-2-4H6L4 7m5 4h6"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No bundles yet</h3>
                                <p class="empty-state-text">Create a reusable item combination for the client apps.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bundles->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $bundles->links() }}
        </div>
    @endif

    <teleport to="body">
        <div
            v-show="modalOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="bundle-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-6">
                <div v-show="modalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()"></div>

                <div
                    v-show="modalOpen"
                    class="relative inline-block max-h-[calc(100vh-2rem)] w-full max-w-5xl transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:max-h-[calc(100vh-3rem)] sm:align-middle"
                    @click.stop
                >
                    <form method="POST" :action="modalAction" class="flex max-h-[calc(100vh-2rem)] flex-col sm:max-h-[calc(100vh-3rem)]">
                        @csrf
                        <input v-if="modalMode === 'edit'" type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" :value="modalMode">
                        <input type="hidden" name="edit_id" :value="bundle.id">

                        <div class="flex shrink-0 items-center justify-between border-b border-white/10 px-6 py-4">
                            <h2 id="bundle-modal-title" class="text-xl font-semibold text-white" v-text="modalMode === 'edit' ? 'Edit Bundle' : 'Create Bundle'"></h2>
                            <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeModal()">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-7">
                            <div class="space-y-5 lg:col-span-5">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Name</label>
                                        <input type="text" name="name" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror" v-model="bundle.name" required>
                                        @error('name')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Sort Order</label>
                                        <input type="number" name="sort_order" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('sort_order') border-red-500 @enderror" v-model="bundle.sort_order" min="0">
                                        @error('sort_order')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-white/80">Description</label>
                                    <textarea name="description" rows="3" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror" v-model="bundle.description"></textarea>
                                    @error('description')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Starts At</label>
                                        <input type="text" name="starts_at" data-flatpickr-datetime class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('starts_at') border-red-500 @enderror" v-model="bundle.starts_at">
                                        @error('starts_at')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Ends At</label>
                                        <input type="text" name="ends_at" data-flatpickr-datetime class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('ends_at') border-red-500 @enderror" v-model="bundle.ends_at">
                                        @error('ends_at')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <label class="inline-flex items-center gap-2 text-sm font-medium text-white/80">
                                    <input type="checkbox" name="is_active" class="rounded border-white/20 bg-white/5 text-[var(--color-sage)] focus:ring-[var(--color-sage)]" v-model="bundle.is_active">
                                    Active
                                </label>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="block text-sm font-medium text-white/80">Bundle Items</label>
                                        <button type="button" class="rounded-lg border border-white/20 px-3 py-1.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="addItem()">Add Item</button>
                                    </div>
                                    @error('items')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror

                                    <div class="space-y-3">
                                        <div v-for="(bundleItem, index) in bundle.items" :key="index" class="rounded-lg border border-white/10 bg-white/5 p-4">
                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                                                <div class="md:col-span-2">
                                                    <label class="mb-1.5 block text-xs font-medium text-white/70">Item</label>
                                                    <select :name="`items[${index}][item_id]`" class="bundle-modal-select w-full rounded-lg border border-white/20 px-3 py-2 text-sm focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="bundleItem.item_id" required>
                                                        <option value="">Choose item</option>
                                                        @foreach($items as $item)
                                                            <option value="{{ $item->id }}">{{ $item->name }}{{ $item->category ? ' - '.$item->category->name : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-white/70">Qty</label>
                                                    <input type="number" :name="`items[${index}][quantity]`" class="w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="bundleItem.quantity" min="1" required>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-white/70">Sort</label>
                                                    <input type="number" :name="`items[${index}][sort_order]`" class="w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="bundleItem.sort_order" min="0">
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-white/70">Price Override</label>
                                                    <input type="number" step="0.01" :name="`items[${index}][price_override]`" class="w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="bundleItem.price_override">
                                                </div>
                                                <div class="flex items-end justify-end">
                                                    <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" title="Remove item" @click="removeItem(index)">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="mb-1.5 block text-xs font-medium text-white/70">Label Override</label>
                                                <input type="text" :name="`items[${index}][label_override]`" class="w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="bundleItem.label_override" placeholder="Optional display name for this bundle">
                                            </div>
                                            <div class="mt-4 border-t border-white/10 pt-3" v-if="itemOptionsFor(bundleItem).length">
                                                <div class="flex items-center justify-between gap-3">
                                                    <label class="block text-xs font-medium text-white/70">Default Options</label>
                                                    <button type="button" class="rounded-lg border border-white/20 px-2.5 py-1 text-xs font-medium text-white hover:bg-white/10 transition-colors" @click="addOptionValue(bundleItem)">Add Option</button>
                                                </div>

                                                <div class="mt-3 space-y-3" v-if="(bundleItem.option_values || []).length">
                                                    <div v-for="(optionValue, optionIndex) in bundleItem.option_values" :key="`option-${index}-${optionIndex}`" class="grid grid-cols-1 gap-3 md:grid-cols-6">
                                                        <div class="md:col-span-2">
                                                            <label class="mb-1.5 block text-xs font-medium text-white/60">Option</label>
                                                            <select :name="`items[${index}][option_values][${optionIndex}][item_option_id]`" class="bundle-modal-select w-full rounded-lg border border-white/20 px-3 py-2 text-sm focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="optionValue.item_option_id" required>
                                                                <option value="">Choose option</option>
                                                                <option v-for="itemOption in itemOptionsFor(bundleItem)" :key="itemOption.id" :value="itemOption.id" v-text="itemOption.name"></option>
                                                            </select>
                                                        </div>
                                                        <div class="md:col-span-2">
                                                            <label class="mb-1.5 block text-xs font-medium text-white/60">Value</label>
                                                            <select :name="`items[${index}][option_values][${optionIndex}][option_value_id]`" class="bundle-modal-select w-full rounded-lg border border-white/20 px-3 py-2 text-sm focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="optionValue.option_value_id" required>
                                                                <option value="">Choose value</option>
                                                                <option v-for="value in optionValuesFor(bundleItem, optionValue)" :key="value.id" :value="value.id" v-text="value.name"></option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1.5 block text-xs font-medium text-white/60">Qty</label>
                                                            <input type="number" min="1" :name="`items[${index}][option_values][${optionIndex}][quantity]`" class="w-full rounded-lg border border-white/20 bg-white/5 px-3 py-2 text-sm text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]" v-model="optionValue.quantity">
                                                        </div>
                                                        <div class="flex items-end justify-end">
                                                            <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" title="Remove option" @click="removeOptionValue(bundleItem, optionIndex)">
                                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" :name="`items[${index}][option_values][${optionIndex}][parent_option_value_id]`" :value="optionValue.parent_option_value_id || ''">
                                                        <input type="hidden" :name="`items[${index}][option_values][${optionIndex}][price_override]`" :value="optionValue.price_override || ''">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @error('items.*.item_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    @error('items.*.quantity')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-white/80">Featured Image</label>
                                <div class="media-dropzone-wrap">
                                    <button type="button" class="media-dropzone" :class="{ 'has-image': bundle.primary_media }" @click="openMediaPicker()">
                                        <span class="media-dropzone__image-wrap" v-if="bundle.primary_media">
                                            <img v-if="bundle.primary_media.preview_url" :src="bundle.primary_media.preview_url" :alt="bundle.primary_media.alt || bundle.primary_media.basename">
                                            <span class="media-dropzone__overlay">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Replace image
                                            </span>
                                        </span>
                                        <template v-else>
                                            <span class="media-dropzone__icon">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l-5-5-5 5"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12"></path>
                                                </svg>
                                            </span>
                                            <span class="media-dropzone__title">Click to upload an image</span>
                                            <span class="media-dropzone__hint">JPG, PNG, GIF or WEBP up to 10MB</span>
                                        </template>
                                    </button>
                                    <button type="button" class="media-dropzone__remove" v-if="bundle.primary_media" @click.stop="clearMedia()" aria-label="Remove bundle image">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="media-dropzone-status">
                                    <span v-if="bundle.primary_media" class="media-dropzone-status__name" v-text="bundle.primary_media.basename"></span>
                                    <span v-else class="media-dropzone-status__empty">No image uploaded</span>
                                </div>
                                <input type="hidden" name="media_id" v-model="bundle.media_id">
                                @error('media_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                            </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 justify-end gap-3 border-t border-white/10 px-6 py-4">
                            <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeModal()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-[var(--color-forest)] shadow-sm hover:bg-white/90 transition-colors" v-text="modalMode === 'edit' ? 'Save Changes' : 'Create Bundle'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
