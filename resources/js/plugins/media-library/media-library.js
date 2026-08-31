const MEDIA_TYPES = [
    { id: 'all', label: 'All' },
    { id: 'image', label: 'Images' },
    { id: 'document', label: 'Documents' },
    { id: 'audio', label: 'Audio' },
    { id: 'video', label: 'Video' },
];

const DETAIL_TABS = [
    { id: 'details', label: 'Details', icon: 'info' },
    { id: 'activity', label: 'Activity', icon: 'activity' },
    { id: 'permissions', label: 'Permissions', icon: 'lock' },
    { id: 'versions', label: 'Versions', icon: 'versions' },
];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizeBoolean(value, fallback = false) {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    return ['true', '1', 'yes'].includes(String(value).toLowerCase());
}

function normalizeList(value, fallback = ['image']) {
    if (!value) {
        return fallback;
    }

    if (Array.isArray(value)) {
        return value;
    }

    return String(value)
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean);
}

function icon(name) {
    const attrs = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    const paths = {
        search: '<path d="m21 21-4.35-4.35"></path><circle cx="11" cy="11" r="7"></circle>',
        upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="m17 8-5-5-5 5"></path><path d="M12 3v12"></path>',
        grid: '<rect width="7" height="7" x="3" y="3"></rect><rect width="7" height="7" x="14" y="3"></rect><rect width="7" height="7" x="14" y="14"></rect><rect width="7" height="7" x="3" y="14"></rect>',
        list: '<path d="M8 6h13"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M3 6h.01"></path><path d="M3 12h.01"></path><path d="M3 18h.01"></path>',
        panel: '<rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M15 3v18"></path>',
        close: '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>',
        check: '<path d="m5 12 4 4L19 6"></path>',
        info: '<circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path>',
        activity: '<path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle>',
        lock: '<rect width="18" height="11" x="3" y="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        versions: '<rect width="8" height="8" x="8" y="8"></rect><path d="M4 4h8v2"></path><path d="M20 20h-8v-2"></path><path d="M20 4h-4v4"></path><path d="M4 20h4v-4"></path>',
        image: '<rect width="18" height="18" x="3" y="3" rx="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"></path>',
        document: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
        pdf: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 16h8"></path>',
        video: '<path d="m22 8-6 4 6 4V8Z"></path><rect width="14" height="12" x="2" y="6" rx="2"></rect>',
        audio: '<path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle>',
        trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path>',
    };

    return `<svg ${attrs}>${paths[name] || paths.document}</svg>`;
}

class MediaLibraryInstance {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            mode: options.mode || element.dataset.mediaLibraryMode || 'library',
            endpoint: options.endpoint || element.dataset.mediaLibraryEndpoint || '/admin/media-library/files',
            uploadEndpoint: options.uploadEndpoint || element.dataset.mediaLibraryUploadEndpoint || '/admin/media-library/files',
            updateEndpointTemplate: options.updateEndpointTemplate || element.dataset.mediaLibraryUpdateEndpoint || '/admin/media-library/files/__ID__',
            deleteEndpointTemplate: options.deleteEndpointTemplate || element.dataset.mediaLibraryDeleteEndpoint || '/admin/media-library/files/__ID__',
            accept: normalizeList(options.accept || element.dataset.mediaLibraryAccept),
            selectable: options.selectable ?? normalizeBoolean(element.dataset.mediaLibrarySelectable, true),
            multiple: options.multiple ?? normalizeBoolean(element.dataset.mediaLibraryMultiple, false),
            detailsOpen: options.detailsOpen ?? normalizeBoolean(element.dataset.mediaLibraryDetailsOpen, true),
            view: options.view || element.dataset.mediaLibraryView || 'grid',
            height: options.height || element.dataset.mediaLibraryHeight || 'auto',
            selectedMediaId: options.selectedMediaId || element.dataset.mediaLibrarySelectedMediaId || null,
            onSelect: options.onSelect || null,
            onClose: options.onClose || null,
        };

        this.state = {
            files: [],
            selectedIds: this.options.selectedMediaId ? [Number(this.options.selectedMediaId)] : [],
            query: '',
            type: 'all',
            view: this.options.view,
            detailsOpen: this.options.detailsOpen,
            activeTab: 'details',
            loading: false,
            error: '',
            upload: null,
            pagination: {
                current_page: 1,
                last_page: 1,
                total: 0,
            },
        };

        this.searchTimer = null;
        this.fileInput = document.createElement('input');
        this.fileInput.type = 'file';
        this.fileInput.accept = this.options.accept.includes('image') ? 'image/*' : '';
        this.fileInput.tabIndex = -1;
        this.fileInput.setAttribute('aria-hidden', 'true');
        this.fileInput.style.position = 'fixed';
        this.fileInput.style.left = '-9999px';
        this.fileInput.style.width = '1px';
        this.fileInput.style.height = '1px';
        this.fileInput.style.opacity = '0';
        this.fileInput.addEventListener('change', () => this.uploadSelectedFiles());
        document.body.appendChild(this.fileInput);

        this.element.classList.add('media-library');
        this.element.dataset.mediaLibraryMounted = 'true';
        this.element.style.setProperty('--media-library-height', this.options.height);
        this.bindEvents();
        this.render();
        this.load();
    }

    destroy() {
        this.fileInput.remove();
        this.element.innerHTML = '';
        this.element.removeAttribute('data-media-library-mounted');
    }

    bindEvents() {
        this.element.addEventListener('click', (event) => {
            const target = event.target.closest('[data-media-action]');

            if (!target || !this.element.contains(target)) {
                return;
            }

            event.preventDefault();
            const action = target.dataset.mediaAction;

            if (action === 'filter') {
                this.state.type = target.dataset.mediaType || 'all';
                this.state.pagination.current_page = 1;
                this.load();
            }

            if (action === 'select') {
                this.select(Number(target.dataset.mediaId));
            }

            if (action === 'view') {
                this.state.view = target.dataset.mediaView || 'grid';
                this.render();
            }

            if (action === 'toggle-details') {
                this.state.detailsOpen = !this.state.detailsOpen;
                this.render();
            }

            if (action === 'tab') {
                this.state.activeTab = target.dataset.mediaTab || 'details';
                this.render();
            }

            if (action === 'upload') {
                this.fileInput.value = '';
                this.fileInput.click();
            }

            if (action === 'insert') {
                this.insertSelection();
            }

            if (action === 'delete') {
                this.deleteSelected();
            }

            if (action === 'close') {
                this.options.onClose?.();
            }

            if (action === 'page') {
                const page = Number(target.dataset.mediaPage || 1);
                this.load(page);
            }
        });

        this.element.addEventListener('input', (event) => {
            if (!event.target.matches('[data-media-search]')) {
                return;
            }

            this.state.query = event.target.value;
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.state.pagination.current_page = 1;
                this.load();
            }, 250);
        });

        this.element.addEventListener('change', (event) => {
            if (!event.target.matches('[data-media-alt-input]')) {
                return;
            }

            this.updateAlt(event.target.value);
        });
    }

    selectedFile() {
        return this.state.files.find((file) => this.state.selectedIds.includes(Number(file.id))) || null;
    }

    select(id) {
        if (this.options.multiple) {
            this.state.selectedIds = this.state.selectedIds.includes(id)
                ? this.state.selectedIds.filter((selectedId) => selectedId !== id)
                : [...this.state.selectedIds, id];
        } else {
            this.state.selectedIds = [id];
        }

        this.render();
    }

    insertSelection() {
        const selected = this.options.multiple
            ? this.state.files.filter((file) => this.state.selectedIds.includes(Number(file.id)))
            : this.selectedFile();

        if (!selected || (Array.isArray(selected) && selected.length === 0)) {
            return;
        }

        this.options.onSelect?.(selected);
    }

    endpointFor(template, id) {
        return template.replace('__ID__', id);
    }

    async load(page = this.state.pagination.current_page) {
        this.state.loading = true;
        this.state.error = '';
        this.render();

        const url = new URL(this.options.endpoint, window.location.origin);
        url.searchParams.set('page', page);
        url.searchParams.set('type', this.state.type);

        if (this.state.query.trim()) {
            url.searchParams.set('query', this.state.query.trim());
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Could not load media.');
            }

            this.state.files = payload.data || [];
            this.state.pagination = payload.meta || this.state.pagination;

            if (!this.selectedFile() && this.state.files.length > 0 && !this.options.multiple) {
                this.state.selectedIds = [Number(this.state.files[0].id)];
            }
        } catch (error) {
            this.state.error = error.message || 'Could not load media.';
        } finally {
            this.state.loading = false;
            this.render();
        }
    }

    uploadSelectedFiles() {
        const [file] = Array.from(this.fileInput.files || []);

        if (!file) {
            return;
        }

        const form = new FormData();
        form.append('file', file);

        const request = new XMLHttpRequest();
        this.state.upload = {
            name: file.name,
            progress: 0,
            error: '',
        };
        this.render();

        request.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                return;
            }

            this.state.upload = {
                ...this.state.upload,
                progress: Math.round((event.loaded / event.total) * 100),
            };
            this.renderUploadProgress();
        });

        request.addEventListener('load', () => {
            let payload = {};

            try {
                payload = JSON.parse(request.responseText || '{}');
            } catch {
                payload = {};
            }

            if (request.status < 200 || request.status >= 300) {
                this.state.upload = {
                    ...this.state.upload,
                    error: payload.message || 'Upload failed.',
                };
                this.render();
                return;
            }

            const file = payload.data;

            if (file) {
                this.state.files = [file, ...this.state.files.filter((entry) => entry.id !== file.id)];
                this.state.selectedIds = [Number(file.id)];
            }

            this.state.upload = null;
            this.render();
        });

        request.addEventListener('error', () => {
            this.state.upload = {
                ...this.state.upload,
                error: 'Upload failed. Check the file and try again.',
            };
            this.render();
        });

        request.open('POST', this.options.uploadEndpoint);
        request.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.send(form);
    }

    async updateAlt(alt) {
        const selected = this.selectedFile();

        if (!selected) {
            return;
        }

        try {
            const response = await fetch(this.endpointFor(this.options.updateEndpointTemplate, selected.id), {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ alt }),
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Could not update alt text.');
            }

            this.state.files = this.state.files.map((file) => (file.id === selected.id ? payload.data : file));
            this.render();
        } catch (error) {
            this.state.error = error.message || 'Could not update alt text.';
            this.render();
        }
    }

    async deleteSelected() {
        const selected = this.selectedFile();

        if (!selected || !window.confirm(`Delete ${selected.basename}?`)) {
            return;
        }

        try {
            const response = await fetch(this.endpointFor(this.options.deleteEndpointTemplate, selected.id), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Could not delete media.');
            }

            this.state.files = this.state.files.filter((file) => file.id !== selected.id);
            this.state.selectedIds = this.state.files[0] ? [Number(this.state.files[0].id)] : [];
            this.render();
        } catch (error) {
            this.state.error = error.message || 'Could not delete media.';
            this.render();
        }
    }

    renderUploadProgress() {
        const progress = this.element.querySelector('[data-media-upload-progress]');

        if (!progress || !this.state.upload) {
            return;
        }

        progress.style.width = `${this.state.upload.progress}%`;
        const percent = this.element.querySelector('[data-media-upload-percent]');

        if (percent) {
            percent.textContent = `${this.state.upload.progress}%`;
        }
    }

    render() {
        const modeClass = this.options.mode === 'picker' ? 'media-library--picker' : 'media-library--embedded';
        const detailClass = this.state.detailsOpen ? 'has-details' : 'details-collapsed';
        this.element.className = `media-library ${modeClass} ${detailClass}`;
        this.element.style.setProperty('--media-library-height', this.options.height);
        this.element.innerHTML = `
            <div class="media-library__shell">
                ${this.renderToolbar()}
                <div class="media-library__body">
                    <div class="media-library__content">
                        ${this.renderAlert()}
                        ${this.renderFiles()}
                        ${this.renderPagination()}
                    </div>
                    ${this.state.detailsOpen ? this.renderDetails() : ''}
                </div>
                ${this.renderUpload()}
            </div>
        `;
        this.renderUploadProgress();
    }

    renderToolbar() {
        const typeTabs = MEDIA_TYPES.map((type) => `
            <button
                type="button"
                class="media-library__tab ${this.state.type === type.id ? 'is-active' : ''}"
                data-media-action="filter"
                data-media-type="${type.id}"
            >${escapeHtml(type.label)}</button>
        `).join('');

        return `
            <div class="media-library__toolbar">
                <label class="media-library__search">
                    ${icon('search')}
                    <input type="search" value="${escapeHtml(this.state.query)}" placeholder="Search media... (cmd+K)" data-media-search>
                </label>
                <div class="media-library__tabs" role="tablist">${typeTabs}</div>
                <div class="media-library__actions">
                    <button type="button" class="media-library__upload" data-media-action="upload">
                        ${icon('upload')}
                        <span>Upload</span>
                    </button>
                    <button type="button" class="media-library__icon-button ${!this.state.detailsOpen ? 'is-active' : ''}" data-media-action="toggle-details" title="${this.state.detailsOpen ? 'Hide details' : 'Show details'}">
                        ${icon('panel')}
                    </button>
                    <button type="button" class="media-library__icon-button ${this.state.view === 'grid' ? 'is-active' : ''}" data-media-action="view" data-media-view="grid" title="Grid view">
                        ${icon('grid')}
                    </button>
                    <button type="button" class="media-library__icon-button ${this.state.view === 'list' ? 'is-active' : ''}" data-media-action="view" data-media-view="list" title="List view">
                        ${icon('list')}
                    </button>
                    ${this.options.mode === 'picker' ? '<span class="media-library__divider"></span><button type="button" class="media-library__icon-button media-library__close" data-media-action="close" title="Close">' + icon('close') + '</button>' : ''}
                </div>
            </div>
        `;
    }

    renderAlert() {
        if (!this.state.error) {
            return '';
        }

        return `<div class="media-library__alert">${escapeHtml(this.state.error)}</div>`;
    }

    renderFiles() {
        if (this.state.loading) {
            return '<div class="media-library__status">Loading media...</div>';
        }

        if (this.state.files.length === 0) {
            return `
                <div class="media-library__empty">
                    <div class="media-library__empty-icon">${icon('image')}</div>
                    <h3>No media files</h3>
                    <p>Upload an image to start building the library.</p>
                </div>
            `;
        }

        if (this.state.view === 'list') {
            return `
                <div class="media-library__list">
                    ${this.state.files.map((file) => this.renderListItem(file)).join('')}
                </div>
            `;
        }

        return `
            <div class="media-library__grid">
                ${this.state.files.map((file) => this.renderTile(file)).join('')}
            </div>
        `;
    }

    renderTile(file) {
        const selected = this.state.selectedIds.includes(Number(file.id));
        const type = this.iconForFile(file);

        return `
            <button
                type="button"
                class="media-library__tile ${selected ? 'is-selected' : ''}"
                data-media-action="select"
                data-media-id="${file.id}"
                title="${escapeHtml(file.basename)}"
            >
                ${selected ? `<span class="media-library__check">${icon('check')}</span>` : ''}
                <span class="media-library__thumb">
                    ${file.preview_url ? `<img src="${escapeHtml(file.preview_url)}" alt="${escapeHtml(file.alt || file.basename)}">` : `<span class="media-library__file-icon media-library__file-icon--${type}">${icon(type)}</span>`}
                </span>
                ${file.preview_url ? '' : `<span class="media-library__filename">${escapeHtml(file.basename)}</span>`}
            </button>
        `;
    }

    renderListItem(file) {
        const selected = this.state.selectedIds.includes(Number(file.id));

        return `
            <button
                type="button"
                class="media-library__row ${selected ? 'is-selected' : ''}"
                data-media-action="select"
                data-media-id="${file.id}"
            >
                <span class="media-library__row-thumb">
                    ${file.preview_url ? `<img src="${escapeHtml(file.preview_url)}" alt="${escapeHtml(file.alt || file.basename)}">` : icon(this.iconForFile(file))}
                </span>
                <span class="media-library__row-main">
                    <span class="media-library__row-name">${escapeHtml(file.basename)}</span>
                    <span class="media-library__row-meta">${escapeHtml(file.mime_type)} · ${escapeHtml(file.size_label || '')}</span>
                </span>
                <span class="media-library__row-date">${escapeHtml(file.uploaded_label || '')}</span>
            </button>
        `;
    }

    iconForFile(file) {
        if (file.aggregate_type === 'image' || file.aggregate_type === 'vector') {
            return 'image';
        }

        if (file.aggregate_type === 'pdf') {
            return 'pdf';
        }

        if (file.aggregate_type === 'audio') {
            return 'audio';
        }

        if (file.aggregate_type === 'video') {
            return 'video';
        }

        return 'document';
    }

    renderDetails() {
        const file = this.selectedFile();

        if (!file) {
            return '<aside class="media-library__details"><div class="media-library__details-empty">Select a file to view details.</div></aside>';
        }

        const tabs = DETAIL_TABS.map((tab) => `
            <button
                type="button"
                class="media-library__detail-tab ${this.state.activeTab === tab.id ? 'is-active' : ''}"
                data-media-action="tab"
                data-media-tab="${tab.id}"
            >
                ${icon(tab.icon)}
                <span>${escapeHtml(tab.label)}</span>
            </button>
        `).join('');

        return `
            <aside class="media-library__details">
                <div class="media-library__detail-tabs">${tabs}</div>
                ${this.renderDetailPanel(file)}
            </aside>
        `;
    }

    renderDetailPanel(file) {
        if (this.state.activeTab !== 'details') {
            return `
                <div class="media-library__placeholder-panel">
                    <h3>${escapeHtml(DETAIL_TABS.find((tab) => tab.id === this.state.activeTab)?.label)}</h3>
                    <p>This panel is reserved for future media management metadata.</p>
                </div>
            `;
        }

        return `
            <div class="media-library__detail-panel">
                <div class="media-library__preview">
                    ${file.preview_url ? `<img src="${escapeHtml(file.preview_url)}" alt="${escapeHtml(file.alt || file.basename)}">` : `<span>${icon(this.iconForFile(file))}</span>`}
                </div>
                <h2>${escapeHtml(file.basename)}</h2>
                <p>Selected file details</p>
                <div class="media-library__properties">
                    <h3>Properties</h3>
                    ${this.property('Type', file.mime_type)}
                    ${this.property('Size', file.size_label)}
                    ${this.property('Dimensions', file.dimensions_label)}
                    ${this.property('Uploaded', file.uploaded_label)}
                    ${this.property('Attached', `${file.attached_count || 0} records`)}
                </div>
                <label class="media-library__alt">
                    <span>Alt text</span>
                    <textarea rows="3" data-media-alt-input>${escapeHtml(file.alt || '')}</textarea>
                </label>
                <div class="media-library__footer-actions">
                    ${this.options.selectable ? `<button type="button" class="media-library__insert" data-media-action="insert">Insert</button>` : ''}
                    <button type="button" class="media-library__delete" data-media-action="delete">Delete File</button>
                </div>
            </div>
        `;
    }

    property(label, value) {
        if (!value) {
            return '';
        }

        return `
            <div class="media-library__property">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>
        `;
    }

    renderPagination() {
        if (this.state.pagination.last_page <= 1) {
            return '';
        }

        const current = Number(this.state.pagination.current_page);
        const last = Number(this.state.pagination.last_page);

        return `
            <div class="media-library__pagination">
                <button type="button" data-media-action="page" data-media-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>Previous</button>
                <span>Page ${current} of ${last}</span>
                <button type="button" data-media-action="page" data-media-page="${current + 1}" ${current >= last ? 'disabled' : ''}>Next</button>
            </div>
        `;
    }

    renderUpload() {
        if (!this.state.upload) {
            return '';
        }

        return `
            <div class="media-library__upload-dock">
                <div class="media-library__upload-file">
                    <span class="media-library__upload-icon">${icon('image')}</span>
                    <div>
                        <div class="media-library__upload-title">Uploading 1 file... ${escapeHtml(this.state.upload.name)}</div>
                        ${this.state.upload.error ? `<div class="media-library__upload-error">${escapeHtml(this.state.upload.error)}</div>` : '<div class="media-library__upload-track"><span data-media-upload-progress style="width: ' + this.state.upload.progress + '%"></span></div>'}
                    </div>
                    <span class="media-library__upload-percent" data-media-upload-percent>${this.state.upload.progress}%</span>
                </div>
            </div>
        `;
    }
}

const instances = new WeakMap();

function mount(target, options = {}) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;

    if (!element) {
        return null;
    }

    instances.get(element)?.destroy();
    const instance = new MediaLibraryInstance(element, options);
    instances.set(element, instance);

    return instance;
}

function autoMount() {
    document.querySelectorAll('[data-media-library]:not([data-media-library-mounted])').forEach((element) => {
        mount(element);
    });
}

const picker = {
    instance: null,
    previousFocus: null,

    open(options = {}) {
        const modal = document.getElementById('media-library-picker-modal');
        const mountPoint = modal?.querySelector('[data-media-library-modal-mount]');

        if (!modal || !mountPoint) {
            return;
        }

        this.previousFocus = document.activeElement;
        modal.classList.remove('is-hidden');
        document.body.classList.add('media-library-modal-open');

        this.instance = mount(mountPoint, {
            ...options,
            mode: 'picker',
            selectable: true,
            height: '693px',
            endpoint: options.endpoint || mountPoint.dataset.mediaLibraryEndpoint,
            uploadEndpoint: options.uploadEndpoint || mountPoint.dataset.mediaLibraryUploadEndpoint,
            updateEndpointTemplate: options.updateEndpointTemplate || mountPoint.dataset.mediaLibraryUpdateEndpoint,
            deleteEndpointTemplate: options.deleteEndpointTemplate || mountPoint.dataset.mediaLibraryDeleteEndpoint,
            onClose: () => this.close(),
            onSelect: (media) => {
                options.onSelect?.(media);
                this.close();
            },
        });

        setTimeout(() => modal.querySelector('[data-media-search]')?.focus(), 0);
    },

    close() {
        const modal = document.getElementById('media-library-picker-modal');

        if (!modal) {
            return;
        }

        modal.classList.add('is-hidden');
        document.body.classList.remove('media-library-modal-open');
        this.instance?.destroy();
        this.instance = null;

        if (this.previousFocus instanceof HTMLElement) {
            this.previousFocus.focus();
        }
    },
};

let documentShortcutsBound = false;

function bindDocumentShortcuts() {
    if (documentShortcutsBound) {
        return;
    }

    documentShortcutsBound = true;

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            picker.close();
        }

        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            const activeModal = document.querySelector('#media-library-picker-modal:not(.is-hidden)');
            const search = activeModal?.querySelector('[data-media-search]') || document.querySelector('[data-media-library-mounted] [data-media-search]');

            if (search) {
                event.preventDefault();
                search.focus();
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.matches('[data-media-library-modal-backdrop]')) {
            picker.close();
        }

        const trigger = event.target.closest('[data-media-picker-trigger]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        const input = document.getElementById(trigger.dataset.mediaPickerTargetInput);
        const preview = trigger.dataset.mediaPickerPreview ? document.getElementById(trigger.dataset.mediaPickerPreview) : null;
        const label = trigger.dataset.mediaPickerLabel ? document.getElementById(trigger.dataset.mediaPickerLabel) : null;

        picker.open({
            selectedMediaId: input?.value || null,
            accept: normalizeList(trigger.dataset.mediaPickerAccept),
            multiple: normalizeBoolean(trigger.dataset.mediaPickerMultiple, false),
            onSelect(media) {
                const selected = Array.isArray(media) ? media[0] : media;

                if (input) {
                    input.value = selected.id;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (preview) {
                    preview.innerHTML = selected.preview_url
                        ? `<img src="${escapeHtml(selected.preview_url)}" alt="${escapeHtml(selected.alt || selected.basename)}">`
                        : icon('image');
                    preview.classList.add('has-media');
                }

                if (label) {
                    label.textContent = selected.basename;
                }

                trigger.dispatchEvent(new CustomEvent('media-picker:selected', {
                    bubbles: true,
                    detail: { media: selected },
                }));
            },
        });
    });
}

window.MediaLibrary = { mount, autoMount, bindDocumentShortcuts };
window.MediaLibraryPicker = picker;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindDocumentShortcuts);
} else {
    bindDocumentShortcuts();
}
