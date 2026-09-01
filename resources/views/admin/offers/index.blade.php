@extends('admin.layouts.app')

@section('title', 'Offers')
@section('header', 'Offers')

@php
    $offerTypeLabels = [
        \App\Models\Offer::TYPE_PERCENTAGE_DISCOUNT => 'Percentage Discount',
        \App\Models\Offer::TYPE_FIXED_DISCOUNT => 'Fixed Discount',
        \App\Models\Offer::TYPE_BUY_X_GET_Y => 'Buy X, Get Y',
        \App\Models\Offer::TYPE_SPEND_X_GET_Y => 'Spend X, Get Y',
    ];

    $discountTypeLabels = [
        \App\Models\Offer::DISCOUNT_PERCENT => 'Percent',
        \App\Models\Offer::DISCOUNT_FIXED_AMOUNT => 'Fixed Amount',
        \App\Models\Offer::DISCOUNT_FREE_ITEM => 'Free Item',
    ];
@endphp

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        modalOpen: {{ $errors->any() && in_array(old('form_action'), ['create', 'edit'], true) ? 'true' : 'false' }},
        modalMode: '{{ old('form_action') === 'edit' ? 'edit' : 'create' }}',
        createAction: '{{ route('admin.offers.store') }}',
        updateAction: '{{ route('admin.offers.update', ['offer' => '__ID__']) }}',
        modalAction: '{{ old('form_action') === 'edit' && old('edit_id') ? route('admin.offers.update', ['offer' => old('edit_id')]) : route('admin.offers.store') }}',
        offer: {
            id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
            name: @js(old('form_action') ? old('name', '') : ''),
            description: @js(old('form_action') ? old('description', '') : ''),
            offer_type: '{{ old('form_action') ? old('offer_type', \App\Models\Offer::TYPE_PERCENTAGE_DISCOUNT) : \App\Models\Offer::TYPE_PERCENTAGE_DISCOUNT }}',
            discount_type: '{{ old('form_action') ? old('discount_type', \App\Models\Offer::DISCOUNT_PERCENT) : \App\Models\Offer::DISCOUNT_PERCENT }}',
            discount_value: '{{ old('form_action') ? old('discount_value', '') : '' }}',
            qualifying_category_id: '{{ old('form_action') ? old('qualifying_category_id', '') : '' }}',
            qualifying_item_id: '{{ old('form_action') ? old('qualifying_item_id', '') : '' }}',
            reward_category_id: '{{ old('form_action') ? old('reward_category_id', '') : '' }}',
            reward_item_id: '{{ old('form_action') ? old('reward_item_id', '') : '' }}',
            minimum_subtotal: '{{ old('form_action') ? old('minimum_subtotal', '') : '' }}',
            required_quantity: '{{ old('form_action') ? old('required_quantity', '') : '' }}',
            reward_quantity: '{{ old('form_action') ? old('reward_quantity', '') : '' }}',
            starts_at: '{{ old('form_action') ? old('starts_at', '') : '' }}',
            ends_at: '{{ old('form_action') ? old('ends_at', '') : '' }}',
            is_active: {{ old('form_action') ? (old('is_active') ? 'true' : 'false') : 'true' }},
            is_stackable: {{ old('form_action') ? (old('is_stackable') ? 'true' : 'false') : 'false' }},
            priority: '{{ old('form_action') ? old('priority', 0) : 0 }}',
            media_id: '{{ old('form_action') ? old('media_id', '') : '' }}',
            primary_media: @js(old('form_action') ? $oldMedia : null),
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

        openCreate() {
            this.modalMode = 'create';
            this.modalAction = this.createAction;
            this.offer = {
                id: null,
                name: '',
                description: '',
                offer_type: '{{ \App\Models\Offer::TYPE_PERCENTAGE_DISCOUNT }}',
                discount_type: '{{ \App\Models\Offer::DISCOUNT_PERCENT }}',
                discount_value: '',
                qualifying_category_id: '',
                qualifying_item_id: '',
                reward_category_id: '',
                reward_item_id: '',
                minimum_subtotal: '',
                required_quantity: '',
                reward_quantity: '',
                starts_at: '',
                ends_at: '',
                is_active: true,
                is_stackable: false,
                priority: 0,
                media_id: '',
                primary_media: null,
            };
            this.modalOpen = true;
            this.initDatepickers();
        },

        openEdit(offer) {
            this.modalMode = 'edit';
            this.modalAction = this.updateAction.replace('__ID__', offer.id);
            this.offer = {
                id: offer.id,
                name: offer.name || '',
                description: offer.description || '',
                offer_type: offer.offer_type || '{{ \App\Models\Offer::TYPE_PERCENTAGE_DISCOUNT }}',
                discount_type: offer.discount_type || '{{ \App\Models\Offer::DISCOUNT_PERCENT }}',
                discount_value: offer.discount_value || '',
                qualifying_category_id: offer.qualifying_category_id ? String(offer.qualifying_category_id) : '',
                qualifying_item_id: offer.qualifying_item_id ? String(offer.qualifying_item_id) : '',
                reward_category_id: offer.reward_category_id ? String(offer.reward_category_id) : '',
                reward_item_id: offer.reward_item_id ? String(offer.reward_item_id) : '',
                minimum_subtotal: offer.minimum_subtotal || '',
                required_quantity: offer.required_quantity || '',
                reward_quantity: offer.reward_quantity || '',
                starts_at: offer.starts_at || '',
                ends_at: offer.ends_at || '',
                is_active: offer.is_active === true,
                is_stackable: offer.is_stackable === true,
                priority: offer.priority || 0,
                media_id: offer.media_id || '',
                primary_media: offer.primary_media || null,
            };
            this.modalOpen = true;
            this.initDatepickers();
        },

        closeModal() {
            this.modalOpen = false;
        },

        clearMedia() {
            this.offer.media_id = '';
            this.offer.primary_media = null;
        },

        openMediaPicker() {
            window.MediaLibraryPicker.open({
                selectedMediaId: this.offer.primary_media ? this.offer.primary_media.id : null,
                accept: ['image'],
                onSelect: (media) => {
                    const selected = Array.isArray(media) ? media[0] : media;
                    this.offer.primary_media = selected;
                    this.offer.media_id = selected.id;
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
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Offers</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create promotional offers for customer checkout and loyalty surfaces.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Offer
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Offer</th>
                    <th>Type</th>
                    <th>Benefit</th>
                    <th>Qualifying Target</th>
                    <th>Window</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($offers as $offer)
                    @php
                        $primaryMedia = $offer->firstMedia(\App\Models\Offer::IMAGE_TAG);
                        $qualifyingTarget = $offer->qualifyingItem?->name ?? $offer->qualifyingCategory?->name ?? 'Whole order';
                        $benefit = match ($offer->discount_type) {
                            \App\Models\Offer::DISCOUNT_PERCENT => rtrim(rtrim((string) $offer->discount_value, '0'), '.') . '% off',
                            \App\Models\Offer::DISCOUNT_FIXED_AMOUNT => '$' . number_format((float) $offer->discount_value, 2) . ' off',
                            default => ($offer->reward_quantity ?? 1) . ' free ' . ($offer->rewardItem?->name ?? $offer->rewardCategory?->name ?? 'item'),
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($primaryMedia)
                                    <div class="user-avatar overflow-hidden" style="background: var(--color-cream);">
                                        <img src="{{ $primaryMedia->getUrl() }}" alt="{{ $primaryMedia->alt ?: $offer->name }}" class="h-full w-full object-cover">
                                    </div>
                                @else
                                    <div class="user-avatar" style="background: linear-gradient(135deg, rgba(207, 126, 95, 0.18) 0%, rgba(207, 126, 95, 0.55) 100%); color: var(--color-forest);">
                                        {{ strtoupper(substr($offer->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="user-name">{{ $offer->name }}</div>
                                    <div class="user-meta">{{ \Illuminate\Support\Str::limit($offer->description ?: 'No description', 72) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $offerTypeLabels[$offer->offer_type] ?? $offer->offer_type }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $benefit }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $qualifyingTarget }}</span>
                        </td>
                        <td>
                            <span class="user-meta">
                                {{ $offer->starts_at?->format('M d, Y') ?? 'No start' }}
                                -
                                {{ $offer->ends_at?->format('M d, Y') ?? 'No end' }}
                            </span>
                        </td>
                        <td>
                            <span class="role-badge {{ $offer->is_active ? 'admin' : 'user' }}">{{ $offer->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Offer"
                                    @click.stop="openEdit(@js([
                                        'id' => $offer->id,
                                        'name' => $offer->name,
                                        'description' => $offer->description,
                                        'offer_type' => $offer->offer_type,
                                        'discount_type' => $offer->discount_type,
                                        'discount_value' => $offer->discount_value,
                                        'qualifying_category_id' => $offer->qualifying_category_id,
                                        'qualifying_item_id' => $offer->qualifying_item_id,
                                        'reward_category_id' => $offer->reward_category_id,
                                        'reward_item_id' => $offer->reward_item_id,
                                        'minimum_subtotal' => $offer->minimum_subtotal,
                                        'required_quantity' => $offer->required_quantity,
                                        'reward_quantity' => $offer->reward_quantity,
                                        'starts_at' => $offer->starts_at?->format('Y-m-d\TH:i'),
                                        'ends_at' => $offer->ends_at?->format('Y-m-d\TH:i'),
                                        'is_active' => $offer->is_active,
                                        'is_stackable' => $offer->is_stackable,
                                        'priority' => $offer->priority,
                                        'media_id' => $primaryMedia?->id,
                                        'primary_media' => $primaryMedia ? $mediaPresenter->present($primaryMedia) : null,
                                    ]))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.offers.destroy', $offer) }}" class="inline" onsubmit="return confirm('Delete this offer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Offer">
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
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.53 0 1.04.21 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No offers yet</h3>
                                <p class="empty-state-text">Create the first promotional offer for the customer app.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($offers->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $offers->links() }}
        </div>
    @endif

    <teleport to="body">
        <div
            v-show="modalOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="offer-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div v-show="modalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    v-show="modalOpen"
                    class="relative inline-block w-full max-w-5xl transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <form method="POST" :action="modalAction">
                        @csrf
                        <input v-if="modalMode === 'edit'" type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" :value="modalMode">
                        <input type="hidden" name="edit_id" :value="offer.id">

                        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                            <h2 id="offer-modal-title" class="text-xl font-semibold text-white" v-text="modalMode === 'edit' ? 'Edit Offer' : 'Create Offer'"></h2>
                            <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeModal()">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-6 px-6 py-5 lg:grid-cols-7">
                            <div class="space-y-5 lg:col-span-5">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Name</label>
                                        <input type="text" name="name" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror" v-model="offer.name" required>
                                        @error('name')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Priority</label>
                                        <input type="number" name="priority" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('priority') border-red-500 @enderror" v-model="offer.priority" min="0">
                                        @error('priority')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-white/80">Description</label>
                                    <textarea name="description" rows="3" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('description') border-red-500 @enderror" v-model="offer.description"></textarea>
                                    @error('description')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Offer Type</label>
                                        <select name="offer_type" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('offer_type') border-red-500 @enderror" v-model="offer.offer_type" required>
                                            @foreach($offerTypeLabels as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('offer_type')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Discount Type</label>
                                        <select name="discount_type" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('discount_type') border-red-500 @enderror" v-model="offer.discount_type" required>
                                            @foreach($discountTypeLabels as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('discount_type')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Discount Value</label>
                                        <input type="number" step="0.01" name="discount_value" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('discount_value') border-red-500 @enderror" v-model="offer.discount_value">
                                        @error('discount_value')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Qualifying Category</label>
                                        <select name="qualifying_category_id" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('qualifying_category_id') border-red-500 @enderror" v-model="offer.qualifying_category_id">
                                            <option value="">Any category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('qualifying_category_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Qualifying Item</label>
                                        <select name="qualifying_item_id" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('qualifying_item_id') border-red-500 @enderror" v-model="offer.qualifying_item_id">
                                            <option value="">Any item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}{{ $item->category ? ' - '.$item->category->name : '' }}</option>
                                            @endforeach
                                        </select>
                                        @error('qualifying_item_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Reward Category</label>
                                        <select name="reward_category_id" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('reward_category_id') border-red-500 @enderror" v-model="offer.reward_category_id">
                                            <option value="">No reward category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('reward_category_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Reward Item</label>
                                        <select name="reward_item_id" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('reward_item_id') border-red-500 @enderror" v-model="offer.reward_item_id">
                                            <option value="">No reward item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}{{ $item->category ? ' - '.$item->category->name : '' }}</option>
                                            @endforeach
                                        </select>
                                        @error('reward_item_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Minimum Subtotal</label>
                                        <input type="number" step="0.01" name="minimum_subtotal" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('minimum_subtotal') border-red-500 @enderror" v-model="offer.minimum_subtotal">
                                        @error('minimum_subtotal')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Required Quantity</label>
                                        <input type="number" name="required_quantity" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('required_quantity') border-red-500 @enderror" v-model="offer.required_quantity" min="1">
                                        @error('required_quantity')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Reward Quantity</label>
                                        <input type="number" name="reward_quantity" class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('reward_quantity') border-red-500 @enderror" v-model="offer.reward_quantity" min="1">
                                        @error('reward_quantity')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Starts At</label>
                                        <input type="text" name="starts_at" data-flatpickr-datetime class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('starts_at') border-red-500 @enderror" v-model="offer.starts_at">
                                        @error('starts_at')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-white/80">Ends At</label>
                                        <input type="text" name="ends_at" data-flatpickr-datetime class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('ends_at') border-red-500 @enderror" v-model="offer.ends_at">
                                        @error('ends_at')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-6">
                                    <label class="inline-flex items-center gap-2 text-sm font-medium text-white/80">
                                        <input type="checkbox" name="is_active" class="rounded border-white/20 bg-white/5 text-[var(--color-sage)] focus:ring-[var(--color-sage)]" v-model="offer.is_active">
                                        Active
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm font-medium text-white/80">
                                        <input type="checkbox" name="is_stackable" class="rounded border-white/20 bg-white/5 text-[var(--color-sage)] focus:ring-[var(--color-sage)]" v-model="offer.is_stackable">
                                        Stackable
                                    </label>
                                </div>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-white/80">Featured Image</label>
                                <div class="media-dropzone-wrap">
                                    <button type="button" class="media-dropzone" :class="{ 'has-image': offer.primary_media }" @click="openMediaPicker()">
                                        <span class="media-dropzone__image-wrap" v-if="offer.primary_media">
                                            <img v-if="offer.primary_media.preview_url" :src="offer.primary_media.preview_url" :alt="offer.primary_media.alt || offer.primary_media.basename">
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
                                    <button type="button" class="media-dropzone__remove" v-if="offer.primary_media" @click.stop="clearMedia()" aria-label="Remove offer image">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="media-dropzone-status">
                                    <span v-if="offer.primary_media" class="media-dropzone-status__name" v-text="offer.primary_media.basename"></span>
                                    <span v-else class="media-dropzone-status__empty">No image uploaded</span>
                                </div>
                                <input type="hidden" name="media_id" v-model="offer.media_id">
                                @error('media_id')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                            <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeModal()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-[var(--color-forest)] shadow-sm hover:bg-white/90 transition-colors" v-text="modalMode === 'edit' ? 'Save Changes' : 'Create Offer'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
