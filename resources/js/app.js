import './bootstrap';
import { createApp } from 'vue';
import EditItemModal from './components/admin/EditItemModal.vue';
import EditOptionValueModal from './components/admin/EditOptionValueModal.vue';

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

function mountAdminApp() {
    const root = document.getElementById('admin-vue-app');

    if (!root) {
        return;
    }

    const pageManager = splitPageManager(window.AdminVuePage?.());

    createApp({
        data() {
            return {
                sidebarOpen: false,
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
                dataMenuOpen: root.dataset.menuOpen === 'true',
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
        .mount(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAdminApp);
} else {
    mountAdminApp();
}
