@extends('admin.layouts.app')

@section('title', 'Options')
@section('header', 'Options')

@push('scripts')
<script>
    window.AdminVuePage = () => ({
            createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
            editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            createMediaId: '{{ old('form_action') === 'create' ? old('media_id', '') : '' }}',
            createMedia: @js(old('form_action') === 'create' ? $oldMedia : null),
            createValueOpen: false,
            editValueOpen: false,
            editValueErrors: @js(old('form_action') === 'edit_value' ? $errors->getMessages() : []),
            editValueShowErrors: {{ $errors->any() && old('form_action') === 'edit_value' ? 'true' : 'false' }},
            selectedOption: null,
            editOption: {
                id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
                name: @js(old('form_action') === 'edit' ? old('name', '') : ''),
                title: @js(old('form_action') === 'edit' ? old('title', '') : ''),
                description: @js(old('form_action') === 'edit' ? old('description', '') : ''),
                media_id: '{{ old('form_action') === 'edit' ? old('media_id', '') : '' }}',
                primary_media: @js(old('form_action') === 'edit' ? $oldMedia : null)
            },
            createValue: {
                name: '',
                price: ''
            },
            editValue: {
                id: null,
                name: '',
                price: ''
            },
            editAction: '{{ route('admin.options.update', ['option' => '__ID__']) }}',

            openCreate() {
                this.createOpen = true;
                this.editOpen = false;
                this.createMediaId = '';
                this.createMedia = null;
            },

            closeCreate() {
                this.createOpen = false;
            },

            clearCreateMedia() {
                this.createMediaId = '';
                this.createMedia = null;
            },

            openCreateMediaPicker() {
                window.MediaLibraryPicker.open({
                    selectedMediaId: this.createMedia ? this.createMedia.id : null,
                    accept: ['image'],
                    onSelect: (media) => {
                        const selected = Array.isArray(media) ? media[0] : media;
                        this.createMedia = selected;
                        this.createMediaId = selected.id;
                    },
                });
            },

            openEdit(option) {
                this.editOption = {
                    id: option.id,
                    name: option.name || '',
                    title: option.title || '',
                    description: option.description || '',
                    media_id: option.media_id || '',
                    primary_media: option.primary_media || null
                };
                this.editAction = '{{ route('admin.options.update', ['option' => '__ID__']) }}'.replace('__ID__', option.id);
                this.editOpen = true;
                this.createOpen = false;
            },

            closeEdit() {
                this.editOpen = false;
            },

            clearEditMedia() {
                this.editOption.media_id = '';
                this.editOption.primary_media = null;
            },

            openEditMediaPicker() {
                window.MediaLibraryPicker.open({
                    selectedMediaId: this.editOption.primary_media ? this.editOption.primary_media.id : null,
                    accept: ['image'],
                    onSelect: (media) => {
                        const selected = Array.isArray(media) ? media[0] : media;
                        this.editOption.primary_media = selected;
                        this.editOption.media_id = selected.id;
                    },
                });
            },

            openCreateValue(option) {
                this.selectedOption = option;
                this.createValue = { name: '', price: '' };
                this.createValueOpen = true;
                this.editValueOpen = false;
            },

            closeCreateValue() {
                this.createValueOpen = false;
                this.selectedOption = null;
            },

            openEditValue(value, option) {
                this.selectedOption = option;
                this.editValue = {
                    id: value.id,
                    name: value.name || '',
                    price: value.price || ''
                };
                this.editValueOpen = true;
                this.createValueOpen = false;
            },

            closeEditValue() {
                this.editValueOpen = false;
                this.selectedOption = null;
            },

            getCreateValueAction() {
                return this.selectedOption ? `{{ route('admin.option-values.store', ['option' => '__ID__']) }}`.replace('__ID__', this.selectedOption.id) : '#';
            },

            getEditValueAction() {
                return this.selectedOption && this.editValue.id ? `{{ route('admin.option-values.update', ['option' => '__OID__', 'optionValue' => '__VID__']) }}`.replace('__OID__', this.selectedOption.id).replace('__VID__', this.editValue.id) : '#';
            },

            getEditAction() {
                return this.editAction.replace('__ID__', this.editOption.id);
            }
    });
</script>
@endpush

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Option Management</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create, edit, and manage menu item options.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Option
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Option Name</th>
                    <th>Values</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($options as $option)
                    @php($primaryMedia = $option->firstMedia('primary_image'))
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($primaryMedia)
                                    <div class="user-avatar overflow-hidden" style="background: var(--color-cream);">
                                        <img src="{{ $primaryMedia->getUrl() }}" alt="{{ $primaryMedia->alt ?: $option->name }}" class="h-full w-full object-cover">
                                    </div>
                                @else
                                    <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                        {{ strtoupper(substr($option->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="user-name">{{ $option->name }}</div>
                                    @if($option->title)
                                        <div class="user-meta">{{ $option->title }}</div>
                                    @endif
                                    @if($option->description)
                                        <div class="user-meta">{{ \Illuminate\Support\Str::limit($option->description, 120) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2 items-center">
                                @forelse($option->optionValues as $value)
                                    <button
                                        type="button"
                                        class="inline-block px-3 py-1 text-sm rounded-full hover:opacity-80 transition-opacity cursor-pointer"
                                        style="background: rgba(124, 154, 138, 0.2);"
                                        @click.stop="openEditValue(@js($value), @js($option->only(['id', 'name'])))"
                                        title="Click to edit value"
                                    >
                                        {{ $value->name }}
                                        @if($value->price > 0)
                                            <span style="color: var(--color-ink); opacity: 0.7;">(+${{ number_format($value->price, 2) }})</span>
                                        @endif
                                    </button>
                                @empty
                                    <span class="user-meta">No values yet</span>
                                @endforelse
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full hover:bg-white/10 transition-colors"
                                    style="color: var(--color-sage); border: 1px solid rgba(147, 180, 165, 0.3);"
                                    @click.stop="openCreateValue(@js($option->only(['id', 'name'])))"
                                    title="Add new value"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Option"
                                    @click.stop="openEdit(@js(array_merge($option->only(['id', 'name', 'title', 'description']), ['media_id' => $primaryMedia?->id, 'primary_media' => $primaryMedia ? $mediaPresenter->present($primaryMedia) : null])))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.options.destroy', $option) }}" class="inline" onsubmit="return confirm('Delete this option? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Option">
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
                        <td colspan="3">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No options yet</h3>
                                <p class="empty-state-text">Create your first option to get started.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($options->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $options->links() }}
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

                <!-- Modal panel -->
                <div
                    v-show="createOpen"
                    class="relative inline-block w-full max-w-3xl transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" action="{{ route('admin.options.store') }}">
                    @csrf
                    <input type="hidden" name="form_action" value="create">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Create Option</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeCreate()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="grid grid-cols-1 gap-6 px-6 py-5 md:grid-cols-5">
                        <div class="space-y-5 md:col-span-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Option Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    placeholder="e.g. Size, Dressing, Topping"
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
                                <label class="mb-2 block text-sm font-medium text-white/80">Title</label>
                                <input
                                    type="text"
                                    name="title"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('title') border-red-500 @enderror"
                                    placeholder="e.g. Choose your size"
                                    value="{{ old('form_action') === 'create' ? old('title') : '' }}"
                                >
                                @if(old('form_action') === 'create')
                                    @error('title')
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
                                    placeholder="Optional helper text for this option"
                                >{{ old('form_action') === 'create' ? old('description') : '' }}</textarea>
                                @if(old('form_action') === 'create')
                                    @error('description')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <p class="text-sm text-white/60">You can add option values after creating the option.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-white/80">Option Image</label>
                            <div class="media-dropzone-wrap">
                                <button
                                    type="button"
                                    class="media-dropzone"
                                    :class="{ 'has-image': createMedia }"
                                    @click="openCreateMediaPicker()"
                                >
                                    <span class="media-dropzone__image-wrap" v-if="createMedia">
                                        <img v-if="createMedia.preview_url" :src="createMedia.preview_url" :alt="createMedia.alt || createMedia.basename">
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
                                <button
                                    type="button"
                                    class="media-dropzone__remove"
                                    v-if="createMedia"
                                    @click.stop="clearCreateMedia()"
                                    aria-label="Remove option image"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="media-dropzone-status">
                                <span v-if="createMedia" class="media-dropzone-status__name" v-text="createMedia.basename"></span>
                                <span v-else class="media-dropzone-status__empty">No image uploaded</span>
                            </div>
                            <input type="hidden" name="media_id" v-model="createMediaId">
                            @if(old('form_action') === 'create')
                                @error('media_id')
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
                            Create Option
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </teleport>

    <!-- Edit Modal -->
    <teleport to="body">
        <div
            v-show="editOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    v-show="editOpen"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeEdit()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    v-show="editOpen"
                    class="relative inline-block w-full max-w-3xl transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" :action="getEditAction()">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="edit_id" :value="editOption.id">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Edit Option</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeEdit()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="grid grid-cols-1 gap-6 px-6 py-5 md:grid-cols-5">
                        <div class="space-y-5 md:col-span-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Option Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                    v-model="editOption.name"
                                    required
                                >
                                @if(old('form_action') === 'edit')
                                    @error('name')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Title</label>
                                <input
                                    type="text"
                                    name="title"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('title') border-red-500 @enderror"
                                    v-model="editOption.title"
                                >
                                @if(old('form_action') === 'edit')
                                    @error('title')
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
                                    v-model="editOption.description"
                                ></textarea>
                                @if(old('form_action') === 'edit')
                                    @error('description')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-white/80">Option Image</label>
                            <div class="media-dropzone-wrap">
                                <button
                                    type="button"
                                    class="media-dropzone"
                                    :class="{ 'has-image': editOption.primary_media }"
                                    @click="openEditMediaPicker()"
                                >
                                    <span class="media-dropzone__image-wrap" v-if="editOption.primary_media">
                                        <img v-if="editOption.primary_media.preview_url" :src="editOption.primary_media.preview_url" :alt="editOption.primary_media.alt || editOption.primary_media.basename">
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
                                <button
                                    type="button"
                                    class="media-dropzone__remove"
                                    v-if="editOption.primary_media"
                                    @click.stop="clearEditMedia()"
                                    aria-label="Remove option image"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="media-dropzone-status">
                                <span v-if="editOption.primary_media" class="media-dropzone-status__name" v-text="editOption.primary_media.basename"></span>
                                <span v-else class="media-dropzone-status__empty">No image uploaded</span>
                            </div>
                            <input type="hidden" name="media_id" v-model="editOption.media_id">
                            @if(old('form_action') === 'edit')
                                @error('media_id')
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
    </teleport>

    <!-- Create Option Value Modal -->
    <teleport to="body">
        <div
            v-show="createValueOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    v-show="createValueOpen"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeCreateValue()"
                ></div>

                <!-- Centering trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    v-show="createValueOpen"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" :action="getCreateValueAction()">
                    @csrf
                    <input type="hidden" name="form_action" value="create_value">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Add Option Value</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeCreateValue()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-5 px-6 py-5">
                        <div class="text-sm text-white/60">
                            For: <span class="font-semibold text-white" v-text="selectedOption?.name || ''"></span>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Value Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                placeholder="e.g. Small, Medium, Large"
                                v-model="createValue.name"
                                required
                            >
                            @if(old('form_action') === 'create_value')
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Price (Optional)</label>
                            <input
                                type="number"
                                name="price"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('price') border-red-500 @enderror"
                                placeholder="0.00"
                                v-model="createValue.price"
                            >
                            @if(old('form_action') === 'create_value')
                                @error('price')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                        <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeCreateValue()">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Add Value
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </teleport>

    <edit-option-value-modal
        :action="getEditValueAction()"
        :csrf-token="csrfToken"
        :errors="editValueErrors"
        :open="editValueOpen"
        :selected-option="selectedOption"
        :show-errors="editValueShowErrors"
        :value="editValue"
        @close="closeEditValue()"
    ></edit-option-value-modal>
</div>
@endsection
