<template>
    <teleport to="body">
        <div
            v-show="open"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div v-show="open" class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="close"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    v-show="open"
                    class="relative inline-block w-full max-w-3xl transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <form method="POST" :action="action">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" value="edit_value">

                        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                            <h2 class="text-xl font-semibold text-white">Edit Option Value</h2>
                            <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="close">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-6 px-6 py-5 md:grid-cols-5">
                            <div class="space-y-5 md:col-span-3">
                                <div class="text-sm text-white/60">
                                    For: <span class="font-semibold text-white" v-text="selectedOption?.name || ''"></span>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-white/80">Value Name</label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                        :class="fieldClass('name')"
                                        v-model="value.name"
                                        required
                                    >
                                    <p v-for="error in fieldErrors('name')" :key="error" class="mt-1.5 text-sm text-red-400" v-text="error"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-white/80">Price (Optional)</label>
                                    <input
                                        type="number"
                                        name="price"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                        :class="fieldClass('price')"
                                        v-model="value.price"
                                    >
                                    <p v-for="error in fieldErrors('price')" :key="error" class="mt-1.5 text-sm text-red-400" v-text="error"></p>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-white/80">Option Value Image</label>
                                <div class="media-dropzone-wrap">
                                    <button
                                        type="button"
                                        class="media-dropzone"
                                        :class="{ 'has-image': value.primary_media }"
                                        @click="openEditValueMediaPicker"
                                    >
                                        <span class="media-dropzone__image-wrap" v-if="value.primary_media">
                                            <img v-if="value.primary_media.preview_url" :src="value.primary_media.preview_url" :alt="value.primary_media.alt || value.primary_media.basename">
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
                                        v-if="value.primary_media"
                                        @click.stop="clearEditValueMedia"
                                        aria-label="Remove option value image"
                                    >
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="media-dropzone-status">
                                    <span v-if="value.primary_media" class="media-dropzone-status__name" v-text="value.primary_media.basename"></span>
                                    <span v-else class="media-dropzone-status__empty">No image uploaded</span>
                                </div>
                                <input type="hidden" name="media_id" v-model="value.media_id">
                                <p v-for="error in fieldErrors('media_id')" :key="error" class="mt-1.5 text-sm text-red-400" v-text="error"></p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                            <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="close">
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
</template>

<script>
export default {
    name: 'EditOptionValueModal',

    props: {
        action: {
            type: String,
            required: true,
        },
        csrfToken: {
            type: String,
            required: true,
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
        open: {
            type: Boolean,
            default: false,
        },
        selectedOption: {
            type: Object,
            default: null,
        },
        showErrors: {
            type: Boolean,
            default: false,
        },
        value: {
            type: Object,
            required: true,
        },
    },

    emits: ['close'],

    methods: {
        close() {
            this.$emit('close');
        },

        fieldClass(field) {
            return this.showErrors && this.errors[field] ? 'border-red-500' : '';
        },

        fieldErrors(field) {
            return this.showErrors ? this.errors[field] || [] : [];
        },

        clearEditValueMedia() {
            this.value.media_id = '';
            this.value.primary_media = null;
        },

        openEditValueMediaPicker() {
            window.MediaLibraryPicker.open({
                selectedMediaId: this.value.primary_media ? this.value.primary_media.id : null,
                accept: ['image'],
                onSelect: (media) => {
                    const selected = Array.isArray(media) ? media[0] : media;
                    this.value.primary_media = selected;
                    this.value.media_id = selected.id;
                },
            });
        },
    },
};
</script>
