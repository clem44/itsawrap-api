const instances = new WeakMap();

function escapeHtml(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function clone(value) {
    return JSON.parse(JSON.stringify(value));
}

function icon(name, className = '') {
    const paths = {
        add: '<path d="M12 5v14M5 12h14"/>',
        alignCenter: '<path d="M5 6h14M8 10h8M5 14h14M8 18h8"/>',
        alignLeft: '<path d="M4 6h16M4 10h11M4 14h16M4 18h10"/>',
        alignRight: '<path d="M4 6h16M9 10h11M4 14h16M10 18h10"/>',
        arrowLeft: '<path d="m15 18-6-6 6-6"/>',
        arrowRight: '<path d="m9 18 6-6-6-6"/>',
        check: '<path d="m5 12 4 4L19 6"/>',
        close: '<path d="m6 6 12 12M18 6 6 18"/>',
        drag: '<path d="M9 5h.01M15 5h.01M9 12h.01M15 12h.01M9 19h.01M15 19h.01" stroke-width="3"/>',
        eye: '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.6"/>',
        image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5L5 20"/>',
        save: '<path d="m5 12 4 4L19 6"/>',
        warning: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
    };

    return `<svg class="hsb__icon ${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] || paths.image}</svg>`;
}

function blankSlide(sortOrder) {
    return {
        id: null,
        client_key: `slide-${Date.now()}-${Math.random().toString(16).slice(2)}`,
        sort_order: sortOrder,
        is_active: true,
        badge: '',
        heading: 'New hero slide',
        body: '',
        text_alignment: 'left',
        overlay_color: '#000000',
        overlay_opacity: 45,
        transition: 'fade',
        transition_ms: 500,
        media: null,
        buttons: [],
    };
}

class HeroSliderBuilderInstance {
    constructor(element, options) {
        this.element = element;
        this.options = options;
        this.slider = clone(options.slider);
        this.selectedIndex = 0;
        this.workingSlide = clone(this.slider.slides[0] || blankSlide(0));
        this.previewMode = false;
        this.previewPaused = false;
        this.dirty = false;
        this.saving = false;
        this.notice = null;
        this.dragIndex = null;
        this.autoplayTimer = null;
        this.workingDirty = false;
        this.animateStage = false;
        this.eventController = new AbortController();
        this.onBeforeUnload = (event) => {
            if (!this.dirty) return;
            event.preventDefault();
            event.returnValue = '';
        };

        this.element.classList.add('hsb');
        this.element.dataset.heroSliderMounted = 'true';
        this.bindEvents();
        window.addEventListener('beforeunload', this.onBeforeUnload);
        this.render();
        this.startAutoplay();
    }

    destroy() {
        clearInterval(this.autoplayTimer);
        this.eventController.abort();
        window.removeEventListener('beforeunload', this.onBeforeUnload);
        this.element.innerHTML = '';
        this.element.classList.remove('hsb');
        this.element.removeAttribute('data-hero-slider-mounted');
    }

    bindEvents() {
        const eventOptions = { signal: this.eventController.signal };

        this.element.addEventListener('click', (event) => {
            const control = event.target.closest('[data-hsb-action]');
            if (!control || !this.element.contains(control)) return;
            event.preventDefault();
            this.handleAction(control.dataset.hsbAction, control);
        }, eventOptions);

        this.element.addEventListener('input', (event) => this.handleField(event), eventOptions);
        this.element.addEventListener('change', (event) => this.handleField(event), eventOptions);
        this.element.addEventListener('keydown', (event) => {
            if (event.target.closest('.hsb__stage') && ['ArrowLeft', 'ArrowRight'].includes(event.key)) {
                event.preventDefault();
                this.selectRelative(event.key === 'ArrowLeft' ? -1 : 1);
            }
        }, eventOptions);

        this.element.addEventListener('dragstart', (event) => {
            const card = event.target.closest('[data-hsb-slide-index]');
            if (!card) return;
            this.dragIndex = Number(card.dataset.hsbSlideIndex);
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
        }, eventOptions);
        this.element.addEventListener('dragover', (event) => {
            const card = event.target.closest('[data-hsb-slide-index]');
            if (!card || this.dragIndex === null) return;
            event.preventDefault();
            card.classList.add('is-drop-target');
        }, eventOptions);
        this.element.addEventListener('dragleave', (event) => {
            event.target.closest('[data-hsb-slide-index]')?.classList.remove('is-drop-target');
        }, eventOptions);
        this.element.addEventListener('drop', (event) => {
            const card = event.target.closest('[data-hsb-slide-index]');
            if (!card || this.dragIndex === null) return;
            event.preventDefault();
            this.moveSlide(this.dragIndex, Number(card.dataset.hsbSlideIndex));
            this.dragIndex = null;
        }, eventOptions);
        this.element.addEventListener('dragend', () => {
            this.dragIndex = null;
            this.element.querySelectorAll('.is-dragging, .is-drop-target').forEach((node) => node.classList.remove('is-dragging', 'is-drop-target'));
        }, eventOptions);
        this.element.addEventListener('pointerover', (event) => {
            if (event.target.closest('.hsb__stage') && !event.relatedTarget?.closest?.('.hsb__stage')) {
                this.previewPaused = true;
            }
        }, eventOptions);
        this.element.addEventListener('pointerout', (event) => {
            if (event.target.closest('.hsb__stage') && !event.relatedTarget?.closest?.('.hsb__stage')) {
                this.previewPaused = false;
            }
        }, eventOptions);
        this.element.addEventListener('focusin', (event) => {
            if (event.target.closest('.hsb__stage')) this.previewPaused = true;
        }, eventOptions);
        this.element.addEventListener('focusout', (event) => {
            if (event.target.closest('.hsb__stage') && !event.relatedTarget?.closest?.('.hsb__stage')) {
                this.previewPaused = false;
            }
        }, eventOptions);
    }

    handleAction(action, control) {
        if (action === 'select') this.selectSlide(Number(control.dataset.index));
        if (action === 'previous') this.selectRelative(-1);
        if (action === 'next') this.selectRelative(1);
        if (action === 'add-slide') this.addSlide();
        if (action === 'remove-slide') this.removeSlide(Number(control.dataset.index));
        if (action === 'move-left') this.moveSlide(Number(control.dataset.index), Number(control.dataset.index) - 1);
        if (action === 'move-right') this.moveSlide(Number(control.dataset.index), Number(control.dataset.index) + 1);
        if (action === 'add-button') this.addButton();
        if (action === 'remove-button') this.removeButton(Number(control.dataset.buttonIndex));
        if (action === 'button-style') this.setButtonStyle(Number(control.dataset.buttonIndex), control.dataset.style);
        if (action === 'alignment') this.setField('text_alignment', control.dataset.alignment);
        if (action === 'choose-media') this.chooseMedia();
        if (action === 'apply') this.applyWorkingSlide();
        if (action === 'reset') this.resetWorkingSlide();
        if (action === 'save') this.save();
        if (action === 'dismiss-notice') {
            this.notice = null;
            this.renderNotice();
        }
        if (action === 'toggle-slider-active') {
            this.slider.is_active = !this.slider.is_active;
            this.markDirty();
            this.render();
        }
        if (action === 'preview') {
            this.commitWorkingSlide();
            this.previewMode = !this.previewMode;
            this.animateStage = true;
            this.render();
            this.animateStage = false;
            this.startAutoplay();
        }
        if (action === 'toggle-playback') {
            this.slider.autoplay = !this.slider.autoplay;
            this.markDirty();
            this.render();
            this.startAutoplay();
        }
    }

    handleField(event) {
        const field = event.target.dataset.hsbField;
        const sliderField = event.target.dataset.hsbSliderField;
        const buttonField = event.target.dataset.hsbButtonField;

        if (sliderField) {
            this.slider[sliderField] = event.target.type === 'checkbox' ? event.target.checked : Number(event.target.value);
            this.markDirty();
            this.updatePlaybackMeta();
            this.startAutoplay();
            return;
        }

        if (buttonField) {
            const button = this.workingSlide.buttons[Number(event.target.dataset.buttonIndex)];
            if (button) button[buttonField] = event.target.value;
            this.markWorkingDirty();
            this.updateStage();
            return;
        }

        if (!field) return;
        const value = event.target.type === 'checkbox'
            ? event.target.checked
            : ['overlay_opacity', 'transition_ms'].includes(field)
                ? Number(event.target.value)
                : event.target.value;
        this.workingSlide[field] = value;
        this.markWorkingDirty();
        this.updateStage();
        if (field === 'overlay_opacity') {
            const output = this.element.querySelector('[data-hsb-opacity-output]');
            if (output) output.textContent = `${value}%`;
        }
    }

    selectSlide(index) {
        if (!this.slider.slides[index]) return;
        this.commitWorkingSlide();
        this.selectedIndex = index;
        this.workingSlide = clone(this.slider.slides[index]);
        this.workingDirty = false;
        this.animateStage = true;
        this.notice = null;
        this.render();
        this.animateStage = false;
        this.startAutoplay();
    }

    selectRelative(delta) {
        const count = this.slider.slides.length;
        if (!count) return;
        this.selectSlide((this.selectedIndex + delta + count) % count);
    }

    addSlide() {
        this.commitWorkingSlide();
        this.slider.slides.push(blankSlide(this.slider.slides.length));
        this.selectedIndex = this.slider.slides.length - 1;
        this.workingSlide = clone(this.slider.slides[this.selectedIndex]);
        this.workingDirty = false;
        this.markDirty();
        this.render();
    }

    removeSlide(index) {
        if (this.slider.slides.length === 1) {
            this.notice = { type: 'error', text: 'A slider needs at least one slide.' };
            this.renderNotice();
            return;
        }
        this.commitWorkingSlide();
        this.slider.slides.splice(index, 1);
        this.reindexSlides();
        this.selectedIndex = Math.min(this.selectedIndex, this.slider.slides.length - 1);
        this.workingSlide = clone(this.slider.slides[this.selectedIndex]);
        this.workingDirty = false;
        this.markDirty();
        this.render();
    }

    moveSlide(from, to) {
        if (from === to || from < 0 || to < 0 || from >= this.slider.slides.length || to >= this.slider.slides.length) return;
        this.commitWorkingSlide();
        const [slide] = this.slider.slides.splice(from, 1);
        this.slider.slides.splice(to, 0, slide);
        this.reindexSlides();
        this.selectedIndex = to;
        this.workingSlide = clone(slide);
        this.workingDirty = false;
        this.markDirty();
        this.render();
    }

    reindexSlides() {
        this.slider.slides.forEach((slide, index) => {
            slide.sort_order = index;
            slide.buttons.forEach((button, buttonIndex) => button.sort_order = buttonIndex);
        });
    }

    addButton() {
        if (this.workingSlide.buttons.length >= 2) return;
        this.workingSlide.buttons.push({
            id: null,
            sort_order: this.workingSlide.buttons.length,
            label: this.workingSlide.buttons.length ? 'Learn More' : 'Order Now',
            url: '/',
            style: this.workingSlide.buttons.length ? 'outline' : 'solid',
        });
        this.markWorkingDirty();
        this.render();
    }

    removeButton(index) {
        this.workingSlide.buttons.splice(index, 1);
        this.workingSlide.buttons.forEach((button, buttonIndex) => button.sort_order = buttonIndex);
        this.markWorkingDirty();
        this.render();
    }

    setButtonStyle(index, style) {
        if (!this.workingSlide.buttons[index]) return;
        this.workingSlide.buttons[index].style = style;
        this.markWorkingDirty();
        this.render();
    }

    setField(field, value) {
        this.workingSlide[field] = value;
        this.markWorkingDirty();
        this.render();
    }

    chooseMedia() {
        this.options.openMediaPicker?.({
            selectedMediaId: this.workingSlide.media?.id || null,
            onSelect: (media) => {
                this.workingSlide.media = clone(Array.isArray(media) ? media[0] : media);
                this.markWorkingDirty();
                this.render();
            },
        });
    }

    applyWorkingSlide() {
        this.commitWorkingSlide();
        this.notice = { type: 'success', text: `Slide ${this.selectedIndex + 1} applied to the deck.` };
        this.render();
    }

    commitWorkingSlide() {
        if (!this.workingDirty) return;
        this.slider.slides[this.selectedIndex] = clone(this.workingSlide);
        this.reindexSlides();
        this.workingDirty = false;
        this.markDirty();
    }

    resetWorkingSlide() {
        this.workingSlide = clone(this.slider.slides[this.selectedIndex] || blankSlide(this.selectedIndex));
        this.workingDirty = false;
        this.notice = null;
        this.render();
    }

    markDirty() {
        this.dirty = true;
        this.notice = null;
    }

    markWorkingDirty() {
        this.workingDirty = true;
        this.markDirty();
    }

    async save() {
        if (this.saving) return;
        this.commitWorkingSlide();
        this.saving = true;
        this.notice = null;
        this.render();

        try {
            const saved = await this.options.save(this.serialize());
            this.slider = clone(saved.slider || saved);
            this.selectedIndex = Math.min(this.selectedIndex, this.slider.slides.length - 1);
            this.workingSlide = clone(this.slider.slides[this.selectedIndex]);
            this.workingDirty = false;
            this.dirty = false;
            this.notice = { type: 'success', text: saved.message || 'Slider saved.' };
        } catch (error) {
            this.notice = { type: 'error', text: this.errorMessage(error) };
        } finally {
            this.saving = false;
            this.render();
            this.startAutoplay();
        }
    }

    serialize() {
        return {
            name: this.slider.name,
            is_active: Boolean(this.slider.is_active),
            autoplay: Boolean(this.slider.autoplay),
            autoplay_interval_ms: Number(this.slider.autoplay_interval_ms),
            slides: this.slider.slides.map((slide, index) => ({
                ...(slide.id ? { id: slide.id } : {}),
                sort_order: index,
                is_active: Boolean(slide.is_active),
                media_id: slide.media?.id || null,
                badge: slide.badge || null,
                heading: slide.heading,
                body: slide.body || null,
                text_alignment: slide.text_alignment,
                overlay_color: slide.overlay_color,
                overlay_opacity: Number(slide.overlay_opacity),
                transition: slide.transition,
                transition_ms: Number(slide.transition_ms),
                buttons: slide.buttons.map((button, buttonIndex) => ({
                    sort_order: buttonIndex,
                    label: button.label,
                    url: button.url,
                    style: button.style,
                })),
            })),
        };
    }

    errorMessage(error) {
        const errors = error?.errors;
        if (errors && typeof errors === 'object') {
            return Object.values(errors).flat()[0] || 'Please check the highlighted slide fields.';
        }
        return error?.message || 'The slider could not be saved. Please try again.';
    }

    startAutoplay() {
        clearInterval(this.autoplayTimer);
        if (!this.previewMode || !this.slider.autoplay || this.slider.slides.length < 2) return;
        this.autoplayTimer = setInterval(() => {
            if (this.previewPaused || document.hidden) return;
            this.selectRelative(1);
        }, Number(this.slider.autoplay_interval_ms));
    }

    updateStage() {
        const stage = this.element.querySelector('[data-hsb-stage-content]');
        if (stage) stage.outerHTML = this.renderStageContent(false);
    }

    updatePlaybackMeta() {
        const meta = this.element.querySelector('[data-hsb-playback-meta]');
        if (meta) meta.textContent = this.slider.autoplay ? `Advance every ${(this.slider.autoplay_interval_ms / 1000).toFixed(1)} seconds` : 'Manual playback';
        const output = this.element.querySelector('[data-hsb-interval-output]');
        if (output) output.textContent = `${(this.slider.autoplay_interval_ms / 1000).toFixed(1)}s`;
    }

    render() {
        const count = this.slider.slides.length;
        this.element.classList.toggle('hsb--preview', this.previewMode);
        this.element.innerHTML = `
            <div class="hsb__notice-slot" aria-live="polite">${this.noticeMarkup()}</div>
            <div class="hsb__workspace">
                <section class="hsb__canvas" aria-label="Slider preview and slide deck">
                    <div class="hsb__stage-card">
                        <div class="hsb__stage-bar">
                            <div class="hsb__stage-status"><span></span> Live Stage: Slide ${count ? this.selectedIndex + 1 : 0} of ${count}</div>
                            <div class="hsb__stage-size">1920 × 1080px <span>Scale to fit</span></div>
                            <div class="hsb__stage-nav">
                                <button type="button" data-hsb-action="previous" aria-label="Previous slide">${icon('arrowLeft')}</button>
                                <strong>${count ? this.selectedIndex + 1 : 0} / ${count}</strong>
                                <button type="button" data-hsb-action="next" aria-label="Next slide">${icon('arrowRight')}</button>
                            </div>
                        </div>
                        <div class="hsb__stage" tabindex="0" aria-label="Current slide preview. Use left and right arrow keys to change slides.">
                            ${this.renderStageContent(this.animateStage)}
                        </div>
                    </div>
                    <div class="hsb__deck-heading">
                        <h2>Slide deck sequence</h2>
                        <p>${icon('drag')} Drag to reorder <span>or use arrow controls</span></p>
                    </div>
                    <div class="hsb__deck">
                        ${this.slider.slides.map((slide, index) => this.renderSlideCard(slide, index)).join('')}
                        <button type="button" class="hsb__add-slide" data-hsb-action="add-slide">${icon('add')}<span>Add new slide</span></button>
                    </div>
                </section>
                ${this.renderInspector()}
            </div>`;
    }

    renderStageContent(animate = false) {
        const slide = this.workingSlide;
        const mediaUrl = slide.media?.preview_url || slide.media?.url || '';
        const align = slide.text_alignment || 'left';
        const overlay = `${slide.overlay_color || '#000000'}${Math.round((Number(slide.overlay_opacity) / 100) * 255).toString(16).padStart(2, '0')}`;
        return `
            <div class="hsb__stage-content hsb__stage-content--${escapeHtml(align)} ${animate ? `hsb__stage-content--animate-${escapeHtml(slide.transition)}` : ''}" data-hsb-stage-content style="--hsb-transition-ms: ${Number(slide.transition_ms) || 500}ms; ${mediaUrl ? `background-image: linear-gradient(${overlay}, ${overlay}), url('${escapeHtml(mediaUrl)}')` : ''}">
                ${mediaUrl ? '' : `<div class="hsb__stage-empty">${icon('image')}<strong>Choose an image to begin</strong><span>The media picker accepts JPG, PNG, GIF, WebP, and HEIC.</span></div>`}
                <div class="hsb__copy ${mediaUrl ? '' : 'is-hidden'}">
                    ${slide.badge ? `<span class="hsb__eyebrow">${escapeHtml(slide.badge)}</span>` : ''}
                    <h2>${escapeHtml(slide.heading || 'Untitled slide')}</h2>
                    ${slide.body ? `<p>${escapeHtml(slide.body)}</p>` : ''}
                    <div class="hsb__stage-buttons">${slide.buttons.map((button) => `<span class="hsb__stage-button hsb__stage-button--${escapeHtml(button.style)}">${escapeHtml(button.label || 'Button')}</span>`).join('')}</div>
                </div>
                ${this.slider.slides.length > 1 ? `<button type="button" class="hsb__carousel-control hsb__carousel-control--left" data-hsb-action="previous" aria-label="Previous slide">${icon('arrowLeft')}</button><button type="button" class="hsb__carousel-control hsb__carousel-control--right" data-hsb-action="next" aria-label="Next slide">${icon('arrowRight')}</button>` : ''}
                <div class="hsb__dots" aria-hidden="true">${this.slider.slides.map((_, index) => `<span class="${index === this.selectedIndex ? 'is-active' : ''}"></span>`).join('')}</div>
            </div>`;
    }

    renderSlideCard(slide, index) {
        const mediaUrl = slide.media?.preview_url || slide.media?.url || '';
        return `
            <article class="hsb__slide-card ${index === this.selectedIndex ? 'is-selected' : ''}" draggable="true" data-hsb-slide-index="${index}">
                <button type="button" class="hsb__slide-select" data-hsb-action="select" data-index="${index}" aria-label="Edit slide ${index + 1}">
                    <span class="hsb__slide-number">#${index + 1}</span>
                    <span class="hsb__slide-thumb" style="${mediaUrl ? `background-image: linear-gradient(#00000030, #00000030), url('${escapeHtml(mediaUrl)}')` : ''}">${mediaUrl ? '' : icon('image')}</span>
                    <strong>${escapeHtml(slide.heading || 'Untitled slide')}</strong>
                    <small>${escapeHtml(slide.transition)} ${slide.transition_ms}ms</small>
                </button>
                <div class="hsb__slide-tools">
                    <button type="button" data-hsb-action="move-left" data-index="${index}" aria-label="Move slide left" ${index === 0 ? 'disabled' : ''}>${icon('arrowLeft')}</button>
                    <button type="button" data-hsb-action="move-right" data-index="${index}" aria-label="Move slide right" ${index === this.slider.slides.length - 1 ? 'disabled' : ''}>${icon('arrowRight')}</button>
                    <button type="button" data-hsb-action="remove-slide" data-index="${index}" aria-label="Remove slide">${icon('close')}</button>
                </div>
            </article>`;
    }

    renderInspector() {
        const slide = this.workingSlide;
        return `
            <aside class="hsb__inspector" aria-label="Slide ${this.selectedIndex + 1} configuration">
                <div class="hsb__inspector-header">
                    <div class="hsb__inspector-actions">
                        <span class="hsb__inspector-save-state" aria-live="polite">${this.dirty ? '<span class="hsb__unsaved">Unsaved changes</span>' : ''}</span>
                        <button type="button" class="hsb__button hsb__button--secondary" data-hsb-action="preview">${icon('eye')} ${this.previewMode ? 'Exit Preview' : 'Preview Mode'}</button>
                        <button type="button" class="hsb__button hsb__button--save" data-hsb-action="save" ${this.saving ? 'disabled' : ''}>${this.saving ? '<span class="hsb__spinner"></span> Saving…' : `${icon('save')} Save Changes`}</button>
                    </div>
                    <div class="hsb__inspector-context">
                        <h2><span></span> Slide #${this.selectedIndex + 1} configuration</h2>
                        <div class="hsb__inspector-status">
                            <button type="button" class="hsb__status ${this.slider.is_active ? 'is-active' : ''}" data-hsb-action="toggle-slider-active" aria-pressed="${this.slider.is_active}">${this.slider.is_active ? 'Active' : 'Inactive'}</button>
                            <span class="hsb__selected-pill">Selected</span>
                        </div>
                    </div>
                </div>
                <div class="hsb__inspector-scroll">
                    <section class="hsb__form-section">
                        <h3>Slide media & overlay</h3>
                        <button type="button" class="hsb__media" data-hsb-action="choose-media">
                            <span class="hsb__media-thumb">${slide.media?.preview_url ? `<img src="${escapeHtml(slide.media.preview_url)}" alt="">` : icon('image')}</span>
                            <span><strong>${escapeHtml(slide.media?.basename || 'No image selected')}</strong><small>${slide.media ? [slide.media.dimensions_label, slide.media.size_label].filter(Boolean).join(' · ') : 'Choose a hero image'}</small></span>
                            <em>${slide.media ? 'Change' : 'Choose'}</em>
                        </button>
                        <div class="hsb__field-grid">
                            <label class="hsb__field"><span>Color overlay</span><span class="hsb__color-field"><input type="color" value="${escapeHtml(slide.overlay_color)}" data-hsb-field="overlay_color"><input type="text" value="${escapeHtml(slide.overlay_color)}" data-hsb-field="overlay_color" maxlength="7"></span></label>
                            <label class="hsb__field"><span>Opacity <output data-hsb-opacity-output>${slide.overlay_opacity}%</output></span><input type="range" min="0" max="90" step="5" value="${slide.overlay_opacity}" data-hsb-field="overlay_opacity"></label>
                        </div>
                    </section>
                    <section class="hsb__form-section">
                        <div class="hsb__section-row"><h3>Slide text content</h3><div class="hsb__segmented" aria-label="Text alignment">${['left', 'center', 'right'].map((alignment) => `<button type="button" data-hsb-action="alignment" data-alignment="${alignment}" class="${slide.text_alignment === alignment ? 'is-active' : ''}" aria-label="Align ${alignment}">${icon(`align${alignment[0].toUpperCase()}${alignment.slice(1)}`)}</button>`).join('')}</div></div>
                        <label class="hsb__field"><span>Badge / tag <small>Optional</small></span><input type="text" maxlength="80" value="${escapeHtml(slide.badge || '')}" data-hsb-field="badge" placeholder="Featured special"></label>
                        <label class="hsb__field"><span>Main heading</span><input type="text" maxlength="160" value="${escapeHtml(slide.heading || '')}" data-hsb-field="heading"></label>
                        <label class="hsb__field"><span>Body text / subtitle</span><textarea maxlength="500" rows="3" data-hsb-field="body">${escapeHtml(slide.body || '')}</textarea></label>
                        <label class="hsb__toggle-row"><span><strong>Published slide</strong><small>Show this slide in the public API</small></span><input type="checkbox" data-hsb-field="is_active" ${slide.is_active ? 'checked' : ''}><i></i></label>
                    </section>
                    <section class="hsb__form-section">
                        <div class="hsb__section-row"><h3>Slide buttons <small>Max 2</small></h3><button type="button" class="hsb__text-button" data-hsb-action="add-button" ${slide.buttons.length >= 2 ? 'disabled' : ''}>${icon('add')} Add button</button></div>
                        <div class="hsb__button-list">${slide.buttons.length ? slide.buttons.map((button, index) => this.renderButtonEditor(button, index)).join('') : '<p class="hsb__empty-note">No buttons yet. Add one when this slide needs a clear next step.</p>'}</div>
                    </section>
                    <section class="hsb__form-section">
                        <h3>Slide animation & playback</h3>
                        <div class="hsb__field-grid">
                            <label class="hsb__field"><span>Transition</span><select data-hsb-field="transition">${[['fade', 'Fade'], ['slide-left', 'Slide left'], ['slide-right', 'Slide right']].map(([value, label]) => `<option value="${value}" ${slide.transition === value ? 'selected' : ''}>${label}</option>`).join('')}</select></label>
                            <label class="hsb__field"><span>Duration</span><select data-hsb-field="transition_ms">${[300, 500, 600, 800, 1000].map((duration) => `<option value="${duration}" ${Number(slide.transition_ms) === duration ? 'selected' : ''}>${duration}ms${duration === 500 ? ' (Smooth)' : ''}</option>`).join('')}</select></label>
                        </div>
                        <label class="hsb__toggle-row"><span><strong>Autoplay slider</strong><small data-hsb-playback-meta>${this.slider.autoplay ? `Advance every ${(this.slider.autoplay_interval_ms / 1000).toFixed(1)} seconds` : 'Manual playback'}</small></span><input type="checkbox" data-hsb-slider-field="autoplay" ${this.slider.autoplay ? 'checked' : ''}><i></i></label>
                        <label class="hsb__field hsb__interval"><span>Autoplay interval</span><input type="range" min="2000" max="12000" step="500" value="${this.slider.autoplay_interval_ms}" data-hsb-slider-field="autoplay_interval_ms"><output data-hsb-interval-output>${(this.slider.autoplay_interval_ms / 1000).toFixed(1)}s</output></label>
                    </section>
                </div>
                <div class="hsb__inspector-footer"><button type="button" class="hsb__reset" data-hsb-action="reset">Reset slide</button><button type="button" class="hsb__button hsb__button--apply" data-hsb-action="apply">Apply to slide</button></div>
            </aside>`;
    }

    renderButtonEditor(button, index) {
        return `
            <div class="hsb__button-editor">
                <div class="hsb__button-editor-heading"><strong>${index === 0 ? 'Primary' : 'Secondary'} button</strong><span>Button ${index + 1}</span><button type="button" data-hsb-action="remove-button" data-button-index="${index}" aria-label="Remove button">${icon('close')}</button></div>
                <div class="hsb__button-fields"><input aria-label="Button label" type="text" maxlength="60" value="${escapeHtml(button.label)}" data-hsb-button-field="label" data-button-index="${index}"><input aria-label="Button URL" type="text" maxlength="2048" value="${escapeHtml(button.url)}" data-hsb-button-field="url" data-button-index="${index}"></div>
                <div class="hsb__style-row"><span>Style</span><div class="hsb__segmented"><button type="button" data-hsb-action="button-style" data-button-index="${index}" data-style="solid" class="${button.style === 'solid' ? 'is-active' : ''}">Solid</button><button type="button" data-hsb-action="button-style" data-button-index="${index}" data-style="outline" class="${button.style === 'outline' ? 'is-active' : ''}">Outline</button></div></div>
            </div>`;
    }

    noticeMarkup() {
        if (!this.notice) return '';
        return `<div class="hsb__notice hsb__notice--${this.notice.type}">${icon(this.notice.type === 'error' ? 'warning' : 'check')}<span>${escapeHtml(this.notice.text)}</span><button type="button" data-hsb-action="dismiss-notice" aria-label="Dismiss">${icon('close')}</button></div>`;
    }

    renderNotice() {
        const slot = this.element.querySelector('.hsb__notice-slot');
        if (slot) slot.innerHTML = this.noticeMarkup();
    }
}

function mount(target, options) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;
    if (!element) return null;
    instances.get(element)?.destroy();
    const instance = new HeroSliderBuilderInstance(element, options);
    instances.set(element, instance);
    return instance;
}

window.HeroSliderBuilder = { mount };

export { mount };
