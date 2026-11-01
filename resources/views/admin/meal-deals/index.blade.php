@extends('admin.layouts.app')

@section('title', 'Meal Deals')
@section('header', 'Meal Deals')

@php
    $itemChoices = $items->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'category_id' => $item->category_id])->values();
    $categoryChoices = $categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values();
    $optionChoices = $options->map(fn ($option) => [
        'id' => $option->id,
        'name' => $option->name,
        'title' => $option->title,
        'values' => $option->optionValues->map(fn ($value) => ['id' => $value->id, 'name' => $value->name, 'price' => (float) $value->price])->values(),
    ])->values();
    $oldForm = old('form_action');
    $oldGroups = collect(old('selection_groups', []))->map(fn ($group, $index) => [
        ...$group,
        'uid' => "old-{$index}",
        'kind' => $group['kind'] ?? 'option',
        'allowed_ids' => array_map('strval', $group['allowed_ids'] ?? []),
        'expanded' => true,
        'allow_duplicates' => (bool) ($group['allow_duplicates'] ?? false),
        'paid_extra' => (bool) ($group['paid_extra'] ?? false),
        'dependencies' => collect($group['dependencies'] ?? [])->map(fn ($dependency, $dependencyIndex) => [
            ...$dependency,
            'uid' => "old-{$index}-dependency-{$dependencyIndex}",
            'parent_choice_id' => (string) $dependency['parent_choice_id'],
            'option_id' => (string) $dependency['option_id'],
            'allowed_ids' => array_map('strval', $dependency['allowed_ids'] ?? []),
            'prices' => collect($dependency['prices'] ?? [])->mapWithKeys(fn ($price, $id) => [(string) $id => $price])->all(),
        ])->values(),
    ])->values();
@endphp

@push('styles')
<style>
    .meal-deal-select { background-color: rgba(255, 255, 255, .05); color: #fff; }
    .meal-deal-select option, .meal-deal-select optgroup { background-color: #fff; color: var(--color-ink); }
    .meal-deal-select option:checked { background-color: var(--color-cream); color: var(--color-forest); }
</style>
@endpush

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        modalOpen: @js($errors->any() && in_array($oldForm, ['create', 'edit'], true)),
        modalMode: @js($oldForm === 'edit' ? 'edit' : 'create'),
        createAction: @js(route('admin.meal-deals.store')),
        updateAction: @js(route('admin.meal-deals.update', ['meal_deal' => '__ID__'])),
        modalAction: @js($oldForm === 'edit' && old('edit_id') ? route('admin.meal-deals.update', ['meal_deal' => old('edit_id')]) : route('admin.meal-deals.store')),
        itemChoices: @js($itemChoices),
        categoryChoices: @js($categoryChoices),
        optionChoices: @js($optionChoices),
        modalTrigger: null,
        groupUidSeed: 0,
        draggedGroupIndex: null,
        dragOverGroupIndex: null,
        dependencyDrafts: {},
        dependencyPanels: {},
        deal: {
            id: @js($oldForm === 'edit' ? old('edit_id') : null),
            name: @js($oldForm ? old('name', '') : ''),
            description: @js($oldForm ? old('description', '') : ''),
            allergens: @js($oldForm ? old('allergens', '') : ''),
            additional_info: @js($oldForm ? old('additional_info', '') : ''),
            base_price: @js($oldForm ? old('base_price', '') : ''),
            marketing_max_saving: @js($oldForm ? old('marketing_max_saving', '') : ''),
            primary_category_id: @js($oldForm ? old('primary_category_id', '') : ''),
            primary_item_id: @js($oldForm ? old('primary_item_id', '') : ''),
            selection_groups: @js($oldForm ? $oldGroups : []),
            fixed_option_value_ids: @js($oldForm ? old('fixed_option_value_ids', []) : []),
            sort_order: @js($oldForm ? old('sort_order', 0) : 0),
            is_active: @js($oldForm ? (bool) old('is_active') : true),
            media_id: @js($oldForm ? old('media_id', '') : ''),
            primary_media: @js($oldForm ? $oldMedia : null),
        },
        blankDeal() {
            return { id: null, name: '', description: '', allergens: '', additional_info: '', base_price: '', marketing_max_saving: '',
                primary_category_id: '', primary_item_id: '', selection_groups: [], fixed_option_value_ids: [],
                sort_order: 0, is_active: true, media_id: '', primary_media: null };
        },
        initDatepickers() { if (this.modalOpen) this.focusMealDealForm(); },
        openCreate(event) {
            this.modalTrigger = event?.currentTarget || null;
            this.modalMode = 'create';
            this.modalAction = this.createAction;
            this.dependencyDrafts = {};
            this.dependencyPanels = {};
            this.deal = this.blankDeal();
            this.modalOpen = true;
            this.focusMealDealForm();
        },
        openEdit(value, event) {
            this.modalTrigger = event?.currentTarget || null;
            this.modalMode = 'edit';
            this.modalAction = this.updateAction.replace('__ID__', value.id);
            this.dependencyDrafts = {};
            this.dependencyPanels = {};
            this.deal = {
                ...value,
                primary_category_id: value.primary_category_id ? String(value.primary_category_id) : '',
                primary_item_id: value.primary_item_id ? String(value.primary_item_id) : '',
                selection_groups: (value.selection_groups || []).map(group => ({
                    ...group, uid: `g-${++this.groupUidSeed}`, kind: group.kind || 'option',
                    option_id: group.option_id ? String(group.option_id) : '',
                    category_id: group.category_id ? String(group.category_id) : '',
                    allowed_ids: (group.allowed_ids || []).map(String),
                    dependencies: (group.dependencies || []).map(dependency => ({
                        ...dependency,
                        uid: `d-${++this.groupUidSeed}`,
                        parent_choice_id: String(dependency.parent_choice_id),
                        option_id: String(dependency.option_id),
                        allowed_ids: (dependency.allowed_ids || []).map(String),
                        prices: Object.fromEntries(Object.entries(dependency.prices || {}).map(([id, price]) => [String(id), price])),
                    })),
                    expanded: false,
                })),
                fixed_option_value_ids: (value.fixed_option_value_ids || []).map(String),
                media_id: value.media_id || '',
            };
            this.modalOpen = true;
            this.focusMealDealForm();
        },
        closeMealDealModal() {
            this.modalOpen = false;
            this.$nextTick(() => this.modalTrigger?.focus());
        },
        focusMealDealForm() {
            this.$nextTick(() => document.querySelector('#meal-deal-dialog input[name="name"]')?.focus());
        },
        trapMealDealFocus(event) {
            const dialog = document.getElementById('meal-deal-dialog');
            const controls = [...dialog.querySelectorAll('button, input, select, textarea')].filter(element => !element.disabled && element.getClientRects().length);
            if (!controls.length) return;
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },
        addMealDealGroup() {
            this.deal.selection_groups.push({ uid: `g-${++this.groupUidSeed}`, kind: 'option', option_id: '', category_id: '', allowed_ids: [], min: 1, max: 1, allow_duplicates: false, paid_extra: false, dependencies: [], expanded: true });
        },
        startDraggingGroup(index, event) {
            this.draggedGroupIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(index));
            const card = event.currentTarget.closest('[data-choice-card]');
            if (card) event.dataTransfer.setDragImage(card, 20, 20);
        },
        stopDraggingGroup() { this.draggedGroupIndex = null; this.dragOverGroupIndex = null; },
        dropGroupAt(index) {
            if (this.draggedGroupIndex !== null) this.moveGroup(this.draggedGroupIndex, index);
            this.stopDraggingGroup();
        },
        moveGroup(from, to) {
            const groups = this.deal.selection_groups;
            if (from === to || to < 0 || to >= groups.length) return;
            groups.splice(to, 0, groups.splice(from, 1)[0]);
        },
        revealInvalidChoices() {
            document.querySelectorAll('#meal-deal-dialog [data-choice-card]').forEach((card, index) => {
                if (!card.querySelector(':invalid')) return;
                this.deal.selection_groups[index].expanded = true;
                const body = card.querySelector('[data-choice-body]');
                if (body) body.style.display = '';
            });
        },
        changePrimaryCategory() { this.deal.primary_item_id = ''; },
        groupName(group) {
            return group.kind === 'item'
                ? this.categoryChoices.find(category => String(category.id) === String(group.category_id))?.name || 'Menu items'
                : this.optionChoices.find(option => String(option.id) === String(group.option_id))?.title || this.optionChoices.find(option => String(option.id) === String(group.option_id))?.name || 'Item option';
        },
        optionValues(optionId) { return this.optionChoices.find(option => String(option.id) === String(optionId))?.values || []; },
        availableChoices(group) {
            const choices = group.kind === 'item'
                ? this.itemChoices.filter(item => String(item.category_id) === String(group.category_id))
                : this.optionValues(group.option_id);
            const term = (group.search || '').trim().toLowerCase();
            return term ? choices.filter(choice => choice.name.toLowerCase().includes(term)) : choices;
        },
        changeGroupSource(group) { group.option_id = ''; group.category_id = ''; group.allowed_ids = []; group.dependencies = []; },
        changeGroupOption(group) { group.allowed_ids = []; group.dependencies = []; },
        dependencyFor(group, parentChoiceId) {
            return (group.dependencies || []).find(dependency => String(dependency.parent_choice_id) === String(parentChoiceId));
        },
        dependencyDraftKey(group, parentChoiceId) { return `${group.uid}-${parentChoiceId}`; },
        dependencyPanelKey(group, parentChoiceId) { return `${group.uid}-${parentChoiceId}`; },
        isDependencyPanelExpanded(group, parentChoiceId) {
            return Boolean(this.dependencyPanels[this.dependencyPanelKey(group, parentChoiceId)]);
        },
        toggleDependencyPanel(group, parentChoiceId) {
            const key = this.dependencyPanelKey(group, parentChoiceId);
            this.dependencyPanels[key] = !this.dependencyPanels[key];
        },
        handleChoiceToggle(group, parentChoiceId) {
            this.pruneGroupDependencies(group);
            const key = this.dependencyPanelKey(group, parentChoiceId);
            if (group.allowed_ids.includes(String(parentChoiceId))) this.dependencyPanels[key] = true;
            else delete this.dependencyPanels[key];
        },
        availableDependencyOptions(group) {
            return this.optionChoices.filter(option => String(option.id) !== String(group.option_id) && option.values.length);
        },
        addGroupDependency(group, parentChoiceId) {
            const draftKey = this.dependencyDraftKey(group, parentChoiceId);
            const optionId = this.dependencyDrafts[draftKey];
            const option = this.optionChoices.find(candidate => String(candidate.id) === String(optionId));
            if (!option) return;

            group.dependencies = (group.dependencies || []).filter(dependency => String(dependency.parent_choice_id) !== String(parentChoiceId));
            group.dependencies.push({
                uid: `d-${++this.groupUidSeed}`,
                parent_choice_id: String(parentChoiceId),
                option_id: String(option.id),
                allowed_ids: option.values.map(value => String(value.id)),
                prices: Object.fromEntries(option.values.map(value => [String(value.id), Number(value.price) || 0])),
            });
            this.dependencyDrafts[draftKey] = '';
        },
        removeGroupDependency(group, parentChoiceId) {
            group.dependencies = (group.dependencies || []).filter(dependency => String(dependency.parent_choice_id) !== String(parentChoiceId));
        },
        pruneGroupDependencies(group) {
            group.dependencies = (group.dependencies || []).filter(dependency => group.allowed_ids.includes(String(dependency.parent_choice_id)));
        },
        dependencyOption(dependency) {
            return this.optionChoices.find(option => String(option.id) === String(dependency.option_id));
        },
        dependencyOptionFor(group, parentChoiceId) {
            const dependency = this.dependencyFor(group, parentChoiceId);
            return dependency ? this.dependencyOption(dependency) : null;
        },
        dependencyValuesFor(group, parentChoiceId) {
            const dependency = this.dependencyFor(group, parentChoiceId);
            const option = dependency ? this.dependencyOption(dependency) : null;
            return (option?.values || []).filter(value => dependency.allowed_ids.includes(String(value.id)));
        },
        updateGroupDependencyPrice(group, parentChoiceId, valueId, price) {
            const dependency = this.dependencyFor(group, parentChoiceId);
            if (dependency) dependency.prices[String(valueId)] = price;
        },
        openMealDealMedia() {
            window.MediaLibraryPicker.open({
                selectedMediaId: this.deal.primary_media?.id || null,
                accept: ['image'],
                onSelect: media => {
                    const selected = Array.isArray(media) ? media[0] : media;
                    this.deal.primary_media = selected;
                    this.deal.media_id = selected.id;
                },
            });
        },
        clearMealDealMedia() { this.deal.primary_media = null; this.deal.media_id = ''; },
    });
</script>
@endpush

@section('content')
<div class="users-container">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="heading-serif mb-1 text-3xl font-semibold text-white">Meal Deals</h1>
                <p style="color: var(--color-sage-light); opacity: .9;">Build fixed-price meals with customer choices and included components.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate($event)">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-6-6h12"/></svg>
                Create Meal Deal
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <div class="overflow-x-auto">
            <table class="users-table">
                <thead><tr><th>Meal Deal</th><th>Base price</th><th>Choices</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($deals as $entry)
                        @php $image = $entry->firstMedia(\App\Models\MealDeal::IMAGE_TAG); @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if($image)
                                        <div class="user-avatar overflow-hidden" style="background: var(--color-cream)"><img src="{{ $image->getUrl() }}" alt="{{ $image->alt ?: $entry->name }}" class="h-full w-full object-cover"></div>
                                    @else
                                        <div class="user-avatar" style="background: linear-gradient(135deg, rgba(196,112,79,.18), rgba(196,112,79,.55)); color: var(--color-forest)">{{ strtoupper(substr($entry->name, 0, 2)) }}</div>
                                    @endif
                                    <div><div class="user-name">{{ $entry->name }}</div><div class="user-meta">{{ \Illuminate\Support\Str::limit($entry->description ?: 'No description', 72) }}</div></div>
                                </div>
                            </td>
                            <td><span class="user-name tabular-nums">${{ number_format((float) $entry->base_price, 2) }}</span><div class="user-meta">+ 15% service charge</div></td>
                            <td><span class="user-meta">{{ count($entry->selection_groups ?? []) }} option {{ count($entry->selection_groups ?? []) === 1 ? 'group' : 'groups' }}</span><div class="user-meta">{{ $entry->primaryCategory?->name ?? 'No category' }} · {{ $entry->primaryItem?->name ?? 'Any item' }}</div></td>
                            <td>
                                @if($entry->is_active && ($availabilityIssues[$entry->id] ?? []))
                                    <span class="role-badge user">Unavailable</span>
                                    <ul class="mt-2 max-w-xs list-disc space-y-1 pl-4 text-xs text-red-200">
                                        @foreach($availabilityIssues[$entry->id] as $issue)<li>{{ $issue }}</li>@endforeach
                                    </ul>
                                @else
                                    <span class="role-badge {{ $entry->is_active ? 'admin' : 'user' }}">{{ $entry->is_active ? 'Active' : 'Inactive' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button type="button" class="action-btn edit" title="Edit Meal Deal" aria-label="Edit {{ $entry->name }}" @click.stop="openEdit(@js([
                                        'id' => $entry->id, 'name' => $entry->name, 'description' => $entry->description,
                                        'allergens' => $entry->allergens, 'additional_info' => $entry->additional_info,
                                        'base_price' => $entry->base_price, 'marketing_max_saving' => $entry->marketing_max_saving,
                                        'primary_category_id' => $entry->primary_category_id, 'primary_item_id' => $entry->primary_item_id,
                                        'selection_groups' => $entry->selection_groups, 'fixed_option_value_ids' => $entry->fixed_option_value_ids,
                                        'sort_order' => $entry->sort_order, 'is_active' => $entry->is_active,
                                        'media_id' => $image?->id, 'primary_media' => $image ? $mediaPresenter->present($image) : null,
                                    ]), $event)">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-5m-1.4-9.4a2 2 0 112.8 2.8L11.8 15H9v-2.8l8.6-8.6z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('admin.meal-deals.destroy', $entry) }}" class="inline" onsubmit="return confirm('Delete this Meal Deal? Existing orders keep their saved details.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn delete" title="Delete Meal Deal" aria-label="Delete {{ $entry->name }}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><h3 class="empty-state-title">No Meal Deals yet</h3><p class="empty-state-text">Create a Meal Deal to offer a configurable fixed-price meal.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <teleport to="body">
        <div id="meal-deal-dialog" v-show="modalOpen" v-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="meal-deal-modal-title" @keydown.esc="closeMealDealModal()" @keydown.tab="trapMealDealFocus($event)">
            <div class="flex min-h-screen items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeMealDealModal()"></div>
                <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-[var(--color-forest)] text-white shadow-xl" @click.stop>
                    <form method="POST" :action="modalAction" class="flex max-h-[calc(100vh-2rem)] flex-col sm:max-h-[calc(100vh-3rem)]" @invalid.capture="revealInvalidChoices()">
                        @csrf
                        <input v-if="modalMode === 'edit'" type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" :value="modalMode">
                        <input type="hidden" name="edit_id" :value="deal.id">
                        <input type="hidden" name="service_charge_rate" value="0.15">
                        <input type="hidden" name="media_id" :value="deal.media_id">
                        <div class="flex shrink-0 items-center justify-between border-b border-white/10 px-6 py-4">
                            <div><h2 id="meal-deal-modal-title" class="heading-serif text-xl font-semibold" v-text="modalMode === 'edit' ? 'Edit Meal Deal' : 'Create Meal Deal'"></h2><p class="mt-1 text-sm text-white/70">Set the price, included items and choices customers can make.</p></div>
                            <button type="button" class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white" aria-label="Close form" @click="closeMealDealModal()"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="min-h-0 flex-1 space-y-7 overflow-y-auto px-6 py-6">
                            @if($errors->any())
                                <div class="rounded-lg border border-red-300 bg-red-950/50 p-4 text-sm" role="alert"><p class="font-semibold">Check the Meal Deal details below.</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <div class="grid gap-6 lg:grid-cols-3">
                                <div class="space-y-5 lg:col-span-2">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="block text-sm font-medium text-white/80 sm:col-span-2">Name <input name="name" v-model="deal.name" required maxlength="255" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]"></label>
                                        <label class="block text-sm font-medium text-white/80 sm:col-span-2">Description <textarea name="description" v-model="deal.description" rows="2" maxlength="2000" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]"></textarea></label>
                                        <label class="block text-sm font-medium text-white/80 sm:col-span-2">Allergens <textarea name="allergens" v-model="deal.allergens" rows="2" maxlength="2000" placeholder="List known allergens" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/50 focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]"></textarea></label>
                                        <label class="block text-sm font-medium text-white/80 sm:col-span-2">Additional information <textarea name="additional_info" v-model="deal.additional_info" rows="2" maxlength="2000" placeholder="Add customer-facing details" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/50 focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]"></textarea></label>
                                        <label class="block text-sm font-medium text-white/80">Base price <input name="base_price" v-model="deal.base_price" type="number" step="0.01" min="0" required class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]"></label>
                                        <label class="block text-sm font-medium text-white/80">Maximum saving callout <input name="marketing_max_saving" v-model="deal.marketing_max_saving" type="number" step="0.01" min="0" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]"></label>
                                        <label class="block text-sm font-medium text-white/80">Food category <admin-tom-select name="primary_category_id" v-model="deal.primary_category_id" :options="[{ value: '', text: 'Choose a category' }, ...categoryChoices.map(category => ({ value: category.id, text: category.name }))]" required class="meal-deal-select mt-2 w-full rounded-lg border border-white/20 px-4 py-2.5" @change="changePrimaryCategory()"></admin-tom-select></label>
                                        <label class="block text-sm font-medium text-white/80">Limit to one item <span class="font-normal text-white/60">(optional)</span> <admin-tom-select name="primary_item_id" v-model="deal.primary_item_id" :options="[{ value: '', text: 'Any available item in category' }, ...itemChoices.filter(item => String(item.category_id) === String(deal.primary_category_id)).map(item => ({ value: item.id, text: item.name }))]" class="meal-deal-select mt-2 w-full rounded-lg border border-white/20 px-4 py-2.5"></admin-tom-select></label>
                                    </div>
                                    <p class="text-sm text-white/70">Customers can choose any available item in the category, unless you limit this deal to one item. The choices below are configured for the deal itself and do not need to be attached to every menu item.</p>
                                    <p class="text-sm text-white/70">The service charge is 15% of the base price. Paid extras keep their regular menu prices.</p>
                                </div>
                                <div class="space-y-5">
                                    <div><label class="mb-2 block text-sm font-medium text-white/80">Featured image</label><div class="media-dropzone-wrap"><button type="button" class="media-dropzone" :class="{'has-image': deal.primary_media}" @click="openMealDealMedia()"><span v-if="deal.primary_media" class="media-dropzone__image-wrap"><img v-if="deal.primary_media.preview_url" :src="deal.primary_media.preview_url" :alt="deal.primary_media.alt || deal.name"><span class="media-dropzone__overlay">Replace image</span></span><template v-else><span class="media-dropzone__title">Choose an image</span><span class="media-dropzone__hint">From the Media Library</span></template></button><button v-if="deal.primary_media" type="button" class="media-dropzone__remove" aria-label="Remove Meal Deal image" @click="clearMealDealMedia()"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button></div></div>
                                    <label class="block text-sm font-medium text-white/80">Sort order <input name="sort_order" v-model="deal.sort_order" type="number" min="0" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white"></label>
                                    <label class="inline-flex items-center gap-2 text-sm font-medium text-white/80"><input name="is_active" value="1" v-model="deal.is_active" type="checkbox" class="rounded border-white/20 text-[var(--color-sage)]">Available to customers</label>
                                </div>
                            </div>
                            <section class="border-t border-white/10 pt-6">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div><h3 class="heading-serif text-lg font-semibold text-white">Customer choices</h3><p class="mt-1 text-sm text-white/70">Choose from item options or menu items in a category. These appear in the customer customizer.</p></div>
                                    <button type="button" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-medium hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" @click="addMealDealGroup()">Add choice group</button>
                                </div>
                                <div v-if="!deal.selection_groups.length" class="mt-4 rounded-xl border border-dashed border-white/25 p-5 text-sm text-white/70">No choice groups yet. Add at least one to make this Meal Deal configurable.</div>
                                <div class="mt-4 space-y-4">
                                    <div v-for="(group, index) in deal.selection_groups" :key="group.uid" data-choice-card class="rounded-xl border border-white/15 bg-white/5 p-4 transition-shadow" :class="{ 'opacity-50': draggedGroupIndex === index, 'ring-2 ring-white/60': dragOverGroupIndex === index && draggedGroupIndex !== index }" @dragover.prevent="draggedGroupIndex !== null && (dragOverGroupIndex = index)" @drop.prevent="dropGroupAt(index)">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="mt-0.5 shrink-0 cursor-grab text-white/50 hover:text-white active:cursor-grabbing" draggable="true" title="Drag to reorder" aria-hidden="true" @dragstart="startDraggingGroup(index, $event)" @dragend="stopDraggingGroup()">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"/></svg>
                                            </span>
                                            <button type="button" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" :aria-expanded="Boolean(group.expanded)" :aria-controls="`meal-deal-choice-${index}`" @click="group.expanded = !group.expanded">
                                                <svg class="h-5 w-5 shrink-0 text-white/70 transition-transform duration-200" :class="group.expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                                                <span class="min-w-0"><span role="heading" aria-level="4" class="block truncate font-semibold text-white" v-text="groupName(group)"></span><span class="block text-sm text-white/65" v-text="`${(group.allowed_ids || []).length} selected · choose ${group.min}–${group.max}`"></span></span>
                                            </button>
                                            <div class="flex shrink-0 items-center gap-2">
                                                <button type="button" class="rounded p-1 text-white/60 hover:text-white disabled:opacity-30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white" :disabled="index === 0" aria-label="Move choice group up" @click="moveGroup(index, index - 1)"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 15 6-6 6 6"/></svg></button>
                                                <button type="button" class="rounded p-1 text-white/60 hover:text-white disabled:opacity-30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white" :disabled="index === deal.selection_groups.length - 1" aria-label="Move choice group down" @click="moveGroup(index, index + 1)"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
                                                <button type="button" class="text-sm text-red-200 underline hover:text-white" @click="deal.selection_groups.splice(index, 1)">Remove group</button>
                                            </div>
                                        </div>
                                        <div :id="`meal-deal-choice-${index}`" data-choice-body v-show="group.expanded" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <label class="block text-sm text-white/80">Choose from <admin-tom-select :name="`selection_groups[${index}][kind]`" v-model="group.kind" :options="[{ value: 'option', text: 'Item option values' }, { value: 'item', text: 'Menu items' }]" class="meal-deal-select mt-2 w-full rounded-lg border border-white/20 px-4 py-2.5" @change="changeGroupSource(group)"></admin-tom-select></label>
                                            <label v-if="group.kind === 'item'" class="block text-sm text-white/80">Menu category <admin-tom-select :name="`selection_groups[${index}][category_id]`" v-model="group.category_id" :options="[{ value: '', text: 'Choose a category' }, ...categoryChoices.map(category => ({ value: category.id, text: category.name }))]" required class="meal-deal-select mt-2 w-full rounded-lg border border-white/20 px-4 py-2.5" @change="changeGroupOption(group)"></admin-tom-select></label>
                                            <label v-else class="block text-sm text-white/80">Item option <admin-tom-select :name="`selection_groups[${index}][option_id]`" v-model="group.option_id" :options="[{ value: '', text: 'Select an option' }, ...optionChoices.map(option => ({ value: option.id, text: option.title || option.name }))]" required class="meal-deal-select mt-2 w-full rounded-lg border border-white/20 px-4 py-2.5" @change="changeGroupOption(group)"></admin-tom-select></label>
                                            <div class="sm:col-span-2">
                                                <label class="block text-sm text-white/80">Allowed choices <input v-model="group.search" type="search" placeholder="Find a choice" class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/50"></label>
                                                <div class="mt-2 max-h-96 space-y-2 overflow-y-auto rounded-lg border border-white/20 p-2">
                                                    <div v-for="choice in availableChoices(group)" :key="choice.id" class="overflow-hidden rounded-lg border border-white/10 bg-black/10">
                                                        <div class="flex min-h-11 items-center gap-2 px-3 py-2">
                                                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 text-sm text-white/90">
                                                                <input :value="String(choice.id)" v-model="group.allowed_ids" type="checkbox" class="rounded border-white/30 text-[var(--color-sage)]" @change="handleChoiceToggle(group, choice.id)">
                                                                <span class="truncate font-medium" v-text="choice.name"></span>
                                                            </label>
                                                            <button
                                                                v-if="group.kind === 'option' && group.allowed_ids.includes(String(choice.id))"
                                                                type="button"
                                                                class="flex shrink-0 items-center gap-2 rounded-md p-2 text-xs text-white/65 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:px-2 sm:py-1"
                                                                :aria-expanded="isDependencyPanelExpanded(group, choice.id)"
                                                                :aria-controls="`meal-deal-dependency-${group.uid}-${choice.id}`"
                                                                :aria-label="dependencyOptionFor(group, choice.id) ? `Edit ${dependencyOptionFor(group, choice.id).title || dependencyOptionFor(group, choice.id).name} dependency for ${choice.name}` : `Set dependent option for ${choice.name}`"
                                                                @click="toggleDependencyPanel(group, choice.id)"
                                                            >
                                                                <span class="hidden sm:inline" v-text="dependencyOptionFor(group, choice.id)?.title || dependencyOptionFor(group, choice.id)?.name || 'Set dependent option'"></span>
                                                                <span v-if="dependencyOptionFor(group, choice.id)" class="h-2 w-2 rounded-full bg-[var(--color-sage-light)] sm:hidden" aria-hidden="true"></span>
                                                                <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="isDependencyPanelExpanded(group, choice.id) ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                                                            </button>
                                                        </div>
                                                        <div
                                                            v-if="group.kind === 'option' && group.allowed_ids.includes(String(choice.id))"
                                                            v-show="isDependencyPanelExpanded(group, choice.id)"
                                                            :id="`meal-deal-dependency-${group.uid}-${choice.id}`"
                                                            class="border-t border-white/10 bg-white/5 px-3 py-3"
                                                        >
                                                            <template v-if="dependencyFor(group, choice.id)">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <div class="min-w-0">
                                                                        <p class="text-xs text-white/55">Dependent option</p>
                                                                        <p class="truncate text-sm font-semibold text-white" v-text="dependencyOptionFor(group, choice.id)?.title || dependencyOptionFor(group, choice.id)?.name"></p>
                                                                    </div>
                                                                    <button type="button" class="shrink-0 rounded-md px-2 py-1 text-xs font-medium text-red-200 underline underline-offset-2 hover:bg-white/10 hover:text-white" @click="removeGroupDependency(group, choice.id)">Remove</button>
                                                                </div>
                                                                <div class="mt-3 space-y-2">
                                                                    <div v-for="value in dependencyValuesFor(group, choice.id)" :key="value.id" class="flex items-center justify-between gap-3 rounded-md bg-black/10 px-3 py-2">
                                                                        <span class="min-w-0 truncate text-sm text-white/80" v-text="value.name"></span>
                                                                        <label class="flex shrink-0 items-center gap-2 text-xs text-white/60">Price
                                                                            <input :value="dependencyFor(group, choice.id).prices[String(value.id)]" type="number" min="0" step="0.01" class="w-24 rounded-lg border border-white/20 bg-white/5 px-2.5 py-1.5 text-right text-sm text-white focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]" @input="updateGroupDependencyPrice(group, choice.id, value.id, $event.target.value)">
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                            <div v-else>
                                                                <p class="mb-2 text-xs text-white/60">Choose an option to show when <span class="font-semibold text-white/80" v-text="choice.name"></span> is selected.</p>
                                                                <div class="flex flex-col gap-2 sm:flex-row">
                                                                    <select v-model="dependencyDrafts[dependencyDraftKey(group, choice.id)]" class="meal-deal-select min-w-0 flex-1 rounded-lg border border-white/20 px-3 py-2 text-sm">
                                                                        <option value="">Choose a dependent option</option>
                                                                        <option v-for="option in availableDependencyOptions(group)" :key="option.id" :value="String(option.id)" v-text="option.title || option.name"></option>
                                                                    </select>
                                                                    <button type="button" class="rounded-lg bg-[var(--color-sage)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--color-sage-light)] hover:text-[var(--color-forest)] disabled:cursor-not-allowed disabled:opacity-50" :disabled="!dependencyDrafts[dependencyDraftKey(group, choice.id)]" @click="addGroupDependency(group, choice.id)">Add dependency</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p v-if="!availableChoices(group).length" class="p-2 text-sm text-white/60">No matching choices.</p>
                                                </div>
                                                <p class="mt-1 text-xs text-white/60" v-text="`${group.allowed_ids.length} selected`"></p>
                                                <input v-for="id in group.allowed_ids" :key="id" type="hidden" :name="`selection_groups[${index}][allowed_ids][]`" :value="id">
                                                <template v-for="(dependency, dependencyIndex) in group.dependencies" :key="dependency.uid">
                                                    <input type="hidden" :name="`selection_groups[${index}][dependencies][${dependencyIndex}][parent_choice_id]`" :value="dependency.parent_choice_id">
                                                    <input type="hidden" :name="`selection_groups[${index}][dependencies][${dependencyIndex}][option_id]`" :value="dependency.option_id">
                                                    <input v-for="allowedId in dependency.allowed_ids" :key="allowedId" type="hidden" :name="`selection_groups[${index}][dependencies][${dependencyIndex}][allowed_ids][]`" :value="allowedId">
                                                    <input v-for="(price, valueId) in dependency.prices" :key="valueId" type="hidden" :name="`selection_groups[${index}][dependencies][${dependencyIndex}][prices][${valueId}]`" :value="price">
                                                </template>
                                            </div>
                                            <label class="block text-sm text-white/80">Minimum <input :name="`selection_groups[${index}][min]`" v-model="group.min" type="number" min="0" max="20" required class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white"></label>
                                            <label class="block text-sm text-white/80">Maximum <input :name="`selection_groups[${index}][max]`" v-model="group.max" type="number" min="1" max="20" required class="mt-2 w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white"></label>
                                            <div class="flex flex-col justify-end gap-2 pb-2 text-sm text-white/80"><label class="inline-flex items-center gap-2"><input :name="`selection_groups[${index}][allow_duplicates]`" v-model="group.allow_duplicates" type="checkbox" value="1">Allow the same choice twice</label><label class="inline-flex items-center gap-2"><input :name="`selection_groups[${index}][paid_extra]`" v-model="group.paid_extra" type="checkbox" value="1">Charge regular price</label></div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            <section class="border-t border-white/10 pt-6"><h3 class="heading-serif text-lg font-semibold">Included fixed options</h3><p class="mb-3 mt-1 text-sm text-white/70">Choose options included automatically, such as avocado.</p><div class="max-h-52 space-y-3 overflow-y-auto rounded-lg border border-white/20 p-3"><div v-for="option in optionChoices.filter(option => option.values.length)" :key="option.id"><h4 class="mb-1 text-xs font-semibold uppercase tracking-wide text-white/60" v-text="option.name"></h4><label v-for="value in option.values" :key="value.id" class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-sm text-white/90 hover:bg-white/10"><input name="fixed_option_value_ids[]" :value="String(value.id)" v-model="deal.fixed_option_value_ids" type="checkbox" class="rounded border-white/30 text-[var(--color-sage)]"><span v-text="value.name"></span></label></div></div></section>
                        </div>
                        <div class="flex shrink-0 justify-end gap-3 border-t border-white/10 px-6 py-4"><button type="button" class="rounded-lg border border-white/20 px-5 py-2.5 text-sm font-medium hover:bg-white/10" @click="closeMealDealModal()">Cancel</button><button type="submit" class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-[var(--color-forest)] shadow-sm hover:bg-white/90" @click="revealInvalidChoices()" v-text="modalMode === 'edit' ? 'Save Changes' : 'Create Meal Deal'"></button></div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
