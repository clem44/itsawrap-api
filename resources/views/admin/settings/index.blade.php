@extends('admin.layouts.app')

@section('title', 'Settings')
@section('header', 'Settings')

@php
    $oldForm = old('form_action');
@endphp

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        modalOpen: @js($errors->any() && in_array($oldForm, ['create', 'edit'], true)),
        modalMode: @js($oldForm === 'edit' ? 'edit' : 'create'),
        createAction: @js(route('admin.settings.store')),
        updateAction: @js(route('admin.settings.update', ['setting' => '__ID__'])),
        modalAction: @js($oldForm === 'edit' && old('edit_id') ? route('admin.settings.update', ['setting' => old('edit_id')]) : route('admin.settings.store')),
        modalTrigger: null,
        setting: {
            id: @js($oldForm === 'edit' ? old('edit_id') : null),
            key: @js($oldForm ? old('key', '') : ''),
            value: @js($oldForm ? old('value', '') : ''),
        },

        initDatepickers() {
            if (this.modalOpen) this.focusSettingForm();
        },

        openCreate(event) {
            this.modalTrigger = event?.currentTarget || null;
            this.modalMode = 'create';
            this.modalAction = this.createAction;
            this.setting = { id: null, key: '', value: '' };
            this.modalOpen = true;
            this.focusSettingForm();
        },

        openEdit(setting, event) {
            this.modalTrigger = event?.currentTarget || null;
            this.modalMode = 'edit';
            this.modalAction = this.updateAction.replace('__ID__', setting.id);
            this.setting = {
                id: setting.id,
                key: setting.key || '',
                value: setting.value || '',
            };
            this.modalOpen = true;
            this.focusSettingForm();
        },

        closeSettingsModal() {
            this.modalOpen = false;
            this.$nextTick(() => this.modalTrigger?.focus());
        },

        focusSettingForm() {
            this.$nextTick(() => document.querySelector('#settings-dialog input[name="key"]')?.focus());
        },

        trapSettingsFocus(event) {
            const dialog = document.getElementById('settings-dialog');
            const controls = [...dialog.querySelectorAll('button, input, textarea')]
                .filter(element => !element.disabled && element.getClientRects().length);
            if (!controls.length) return;
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    });
</script>
@endpush

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="heading-serif mb-1 text-3xl font-semibold text-white">Application Settings</h1>
                <p class="max-w-2xl text-[var(--color-sage-light)] opacity-90">Manage the key-value settings used by the POS, ordering, and administration tools.</p>
            </div>
            <button type="button" class="btn btn-primary" @click.stop="openCreate($event)">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
                New Setting
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1 overflow-x-auto">
        <table class="users-table min-w-[42rem]">
            <thead>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                    <th>Updated</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" aria-hidden="true">
                                    <i class="bi bi-sliders2 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="user-name font-mono text-sm">{{ $setting->key }}</div>
                                    <div class="user-meta">ID {{ $setting->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="max-w-xl">
                            <span class="user-meta block whitespace-pre-wrap break-words">{{ filled($setting->value) ? \Illuminate\Support\Str::limit($setting->value, 140) : 'No value set' }}</span>
                        </td>
                        <td>
                            <span class="user-meta whitespace-nowrap">{{ $setting->updated_at?->format('M d, Y') ?? 'Not updated' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit {{ $setting->key }}"
                                    aria-label="Edit {{ $setting->key }}"
                                    @click.stop="openEdit(@js($setting->only(['id', 'key', 'value'])), $event)"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586Z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-state-icon" aria-hidden="true">
                                    <i class="bi bi-sliders2 text-2xl"></i>
                                </div>
                                <h2 class="empty-state-title">No settings yet</h2>
                                <p class="empty-state-text">Create the first setting to configure application behavior.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($settings->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $settings->links('admin.partials.pagination') }}
        </div>
    @endif

    <teleport to="body">
        <div
            v-show="modalOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-labelledby="settings-modal-title"
            @keydown.esc="closeSettingsModal()"
            @keydown.tab="trapSettingsFocus($event)"
        >
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
                <div class="fixed inset-0 bg-black/60" aria-hidden="true" @click="closeSettingsModal()"></div>

                <div id="settings-dialog" class="modal-card relative max-w-xl" @click.stop>
                    <form method="POST" :action="modalAction">
                        @csrf
                        <input v-if="modalMode === 'edit'" type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" :value="modalMode">
                        <input type="hidden" name="edit_id" :value="setting.id">

                        <div class="modal-header">
                            <div>
                                <h2 id="settings-modal-title" class="modal-title" v-text="modalMode === 'edit' ? 'Edit Setting' : 'Create Setting'"></h2>
                                <p class="mt-1 text-sm text-[var(--color-ink-light)]" v-text="modalMode === 'edit' ? 'Update this application value.' : 'Add a new application key and value.'"></p>
                            </div>
                            <button type="button" class="modal-close" aria-label="Close settings form" @click="closeSettingsModal()">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body space-y-5">
                            <div>
                                <label for="setting-key" class="mb-2 block text-sm font-medium text-[var(--color-ink)]">Key</label>
                                <input
                                    id="setting-key"
                                    v-model="setting.key"
                                    name="key"
                                    type="text"
                                    maxlength="255"
                                    autocomplete="off"
                                    placeholder="store_name"
                                    class="w-full rounded-xl border border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-4 py-3 text-[var(--color-ink)] placeholder:text-[var(--color-ink-light)] focus:border-[var(--color-sage)] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[rgba(124,154,138,0.15)] @error('key') border-[var(--color-error)] bg-[var(--color-error-light)] @enderror"
                                    required
                                >
                                <p class="mt-1.5 text-xs text-[var(--color-ink-light)]">Use a stable, descriptive identifier such as <span class="font-mono">receipt_footer</span>.</p>
                                @error('key')
                                    <p class="mt-1.5 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-2 flex items-baseline justify-between gap-4">
                                    <label for="setting-value" class="block text-sm font-medium text-[var(--color-ink)]">Value</label>
                                    <span class="text-xs text-[var(--color-ink-light)]">Optional</span>
                                </div>
                                <textarea
                                    id="setting-value"
                                    v-model="setting.value"
                                    name="value"
                                    rows="6"
                                    placeholder="Enter the value used by the application"
                                    class="w-full resize-y rounded-xl border border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-4 py-3 text-[var(--color-ink)] placeholder:text-[var(--color-ink-light)] focus:border-[var(--color-sage)] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[rgba(124,154,138,0.15)] @error('value') border-[var(--color-error)] bg-[var(--color-error-light)] @enderror"
                                ></textarea>
                                @error('value')
                                    <p class="mt-1.5 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeSettingsModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary" v-text="modalMode === 'edit' ? 'Save Changes' : 'Create Setting'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
