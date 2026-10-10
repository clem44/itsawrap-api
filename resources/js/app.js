import './bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import './plugins/media-library/media-library';
import './plugins/media-library/Media-library.css';
import './plugins/hero-slider/hero-slider-builder';
import './plugins/hero-slider/hero-slider-builder.css';
import { createApp } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import EditItemModal from './components/admin/EditItemModal.vue';
import EditOptionValueModal from './components/admin/EditOptionValueModal.vue';
import UserForm from './components/admin/users/UserForm.vue';
import AdminTomSelect from './components/admin/AdminTomSelect.vue';

window.flatpickr = flatpickr;

function splitPageManager(manager) {
    const data = {};
    const methods = {};

    Object.entries(manager || {}).forEach(([key, value]) => {
        if (typeof value === 'function') {
            methods[key] = value;
            return;
        }

        data[key] = value;
    });

    return { data, methods };
}

function prepareRuntimeDomTemplate(root) {
    root.querySelectorAll('[v-text]').forEach((element) => {
        if (element.childNodes.length > 0) {
            element.textContent = '';
        }
    });
}

function mountAdminApp() {
    const root = document.getElementById('admin-vue-app');

    if (!root) {
        return;
    }

    prepareRuntimeDomTemplate(root);

    const pageManager = splitPageManager(window.AdminVuePage?.());

    createApp({
        data() {
            return {
                sidebarOpen: false,
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
                dataMenuOpen: root.dataset.menuOpen === 'true',
                deliveryMenuOpen: root.dataset.deliveryMenuOpen === 'true',
                sidebarCollapsed: JSON.parse(localStorage.getItem('adminSidebarCollapsed') || 'false'),
                openEndpointGroups: {},
                ...pageManager.data,
            };
        },

        watch: {
            sidebarCollapsed(value) {
                localStorage.setItem('adminSidebarCollapsed', JSON.stringify(value));
            },
        },

        mounted() {
            this.initDatepickers?.();
        },

        methods: {
            toggleEndpointGroup(group) {
                this.openEndpointGroups = {
                    ...this.openEndpointGroups,
                    [group]: !this.openEndpointGroups[group],
                };
            },

            isEndpointGroupOpen(group) {
                return this.openEndpointGroups?.[group] === true;
            },

            ...pageManager.methods,
        },
    })
        .component('edit-item-modal', EditItemModal)
        .component('edit-option-value-modal', EditOptionValueModal)
        .component('user-form', UserForm)
        .component('admin-tom-select', AdminTomSelect)
        .mount(root);

    window.MediaLibrary?.autoMount();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAdminApp);
} else {
    mountAdminApp();
}
