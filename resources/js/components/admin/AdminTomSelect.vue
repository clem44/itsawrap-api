<template>
    <select ref="select" :name="name" :required="required" :disabled="disabled"></select>
</template>

<script>
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import '../../admin/modal-tom-select.css';

export default {
    props: {
        modelValue: { default: '' },
        options: { type: Array, default: () => [] },
        name: { type: String, required: true },
        required: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
    },
    emits: ['update:modelValue', 'change'],
    mounted() {
        this.tomSelect = new TomSelect(this.$refs.select, {
            create: false,
            allowEmptyOption: true,
            dropdownParent: 'body',
            wrapperClass: 'ts-wrapper admin-modal-tom',
            dropdownClass: 'ts-dropdown admin-modal-tom-dropdown',
            options: this.normalizedOptions(),
            onChange: (value) => {
                this.$emit('update:modelValue', value);
                this.$emit('change', value);
            },
        });
        this.optionsSignature = JSON.stringify(this.normalizedOptions());
        this.tomSelect.setValue(String(this.modelValue ?? ''), true);
    },
    beforeUnmount() {
        this.tomSelect?.destroy();
    },
    watch: {
        modelValue(value) {
            const next = String(value ?? '');
            if (this.tomSelect && this.tomSelect.getValue() !== next) {
                this.tomSelect.setValue(next, true);
            }
        },
        options: {
            deep: true,
            handler() {
                if (!this.tomSelect) return;
                const options = this.normalizedOptions();
                const signature = JSON.stringify(options);
                if (signature === this.optionsSignature) return;
                this.optionsSignature = signature;
                const selected = String(this.modelValue ?? '');
                this.tomSelect.clear(true);
                this.tomSelect.clearOptions(() => false);
                this.tomSelect.addOptions(options);
                this.tomSelect.setValue(selected, true);
                this.tomSelect.refreshOptions(false);
            },
        },
        disabled(value) {
            if (value) this.tomSelect?.disable();
            else this.tomSelect?.enable();
        },
    },
    methods: {
        normalizedOptions() {
            return this.options.map((option) => ({
                value: String(option.value ?? ''),
                text: String(option.text ?? ''),
                disabled: Boolean(option.disabled),
            }));
        },
    },
};
</script>
