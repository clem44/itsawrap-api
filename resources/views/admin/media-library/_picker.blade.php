<div id="media-library-picker-modal" class="media-library-modal is-hidden" aria-hidden="true">
    <div class="media-library-modal__backdrop" data-media-library-modal-backdrop>
        <div
            data-media-library-modal-mount
            data-media-library-endpoint="{{ route('admin.media-library.index') }}"
            data-media-library-upload-endpoint="{{ route('admin.media-library.store') }}"
            data-media-library-update-endpoint="{{ url('/admin/media-library/files/__ID__') }}"
            data-media-library-delete-endpoint="{{ url('/admin/media-library/files/__ID__') }}"
            data-media-library-mode="picker"
            data-media-library-accept="image"
            data-media-library-selectable="true"
            data-media-library-height="85vh"
        ></div>
    </div>
</div>
