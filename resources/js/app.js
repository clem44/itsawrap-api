import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';

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
    }).mount(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAdminApp);
} else {
    mountAdminApp();
}
