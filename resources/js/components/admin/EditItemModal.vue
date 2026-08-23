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
                    class="relative inline-block w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                    <form method="POST" :action="action">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="form_action" value="edit">
                        <input type="hidden" name="edit_id" :value="item.id">

                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-xl font-semibold text-gray-900">Edit Item</h2>
                            <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors" @click="close">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6 px-6 py-5 max-h-96 overflow-y-auto">
                            <div v-if="showErrors && errorList.length" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <p class="font-semibold mb-1">Please fix the following:</p>
                                <ul class="list-disc pl-5 space-y-1">
                                    <li v-for="error in errorList" :key="error" v-text="error"></li>
                                </ul>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900">Item Name</label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                        :class="fieldClass('name')"
                                        v-model="item.name"
                                        required
                                    >
                                    <p v-for="error in fieldErrors('name')" :key="error" class="mt-1.5 text-sm text-red-600" v-text="error"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900">Cost</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="cost"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                        :class="fieldClass('cost')"
                                        v-model="item.cost"
                                        required
                                    >
                                    <p v-for="error in fieldErrors('cost')" :key="error" class="mt-1.5 text-sm text-red-600" v-text="error"></p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Description</label>
                                <textarea
                                    name="description"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                    :class="fieldClass('description')"
                                    v-model="item.description"
                                ></textarea>
                                <p v-for="error in fieldErrors('description')" :key="error" class="mt-1.5 text-sm text-red-600" v-text="error"></p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900">Category</label>
                                    <select
                                        name="category_id"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                        :class="fieldClass('category_id')"
                                        v-model="item.category_id"
                                        required
                                    >
                                        <option value="">Select a category</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id" v-text="category.name"></option>
                                    </select>
                                    <p v-for="error in fieldErrors('category_id')" :key="error" class="mt-1.5 text-sm text-red-600" v-text="error"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900">Short Code</label>
                                    <input
                                        type="text"
                                        name="short_code"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                        :class="fieldClass('short_code')"
                                        v-model="item.short_code"
                                    >
                                    <p v-for="error in fieldErrors('short_code')" :key="error" class="mt-1.5 text-sm text-red-600" v-text="error"></p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Image Path</label>
                                <input
                                    type="text"
                                    name="image_path"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
                                    :class="fieldClass('image_path')"
                                    v-model="item.image_path"
                                >
                                <p v-for="error in fieldErrors('image_path')" :key="error" class="mt-1.5 text-sm text-red-600" v-text="error"></p>
                            </div>

                            <div>
                                <label class="mb-3 block text-sm font-medium text-gray-900">Available Options</label>
                                <template v-for="optionId in item.options" :key="'edit_option_input_' + optionId">
                                    <input type="hidden" name="options[]" :value="optionId">
                                </template>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <div v-for="option in options" :key="option.id" class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :id="'edit_option_' + option.id"
                                            :value="option.id"
                                            @change="toggleOption(option.id)"
                                            :checked="item.options.includes(option.id)"
                                            class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                        >
                                        <label :for="'edit_option_' + option.id" class="ml-2 block text-sm text-gray-700" v-text="option.name"></label>
                                    </div>
                                    <p v-if="options.length === 0" class="text-sm text-gray-500">No options available</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="edit_active"
                                    name="active"
                                    class="h-4 w-4 rounded border-gray-300 text-[var(--color-sage)] focus:ring-[var(--color-sage)]"
                                    v-model="item.active"
                                >
                                <label for="edit_active" class="text-sm font-medium text-gray-900">Active Item</label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50 transition-colors" @click="close">
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
    name: 'EditItemModal',

    props: {
        action: {
            type: String,
            required: true,
        },
        categories: {
            type: Array,
            default: () => [],
        },
        csrfToken: {
            type: String,
            required: true,
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
        errorList: {
            type: Array,
            default: () => [],
        },
        item: {
            type: Object,
            required: true,
        },
        open: {
            type: Boolean,
            default: false,
        },
        options: {
            type: Array,
            default: () => [],
        },
        showErrors: {
            type: Boolean,
            default: false,
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

        toggleOption(optionId) {
            const id = Number(optionId);
            const options = Array.isArray(this.item.options) ? this.item.options : [];
            const index = options.indexOf(id);

            if (index > -1) {
                options.splice(index, 1);
                return;
            }

            options.push(id);
        },
    },
};
</script>
