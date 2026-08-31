@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('header', 'Media Library')

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                {{-- <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Media Library</h1> --}}
                <p style="color: var(--color-sage-light); opacity: 0.9;">Upload, organize, and manage your media assets.</p>
            </div>
        </div>
    </div>

    <div
        id="admin-media-library"
        data-media-library
        data-media-library-endpoint="{{ route('admin.media-library.index') }}"
        data-media-library-upload-endpoint="{{ route('admin.media-library.store') }}"
        data-media-library-update-endpoint="{{ url('/admin/media-library/files/__ID__') }}"
        data-media-library-delete-endpoint="{{ url('/admin/media-library/files/__ID__') }}"
        data-media-library-mode="library"
        data-media-library-accept="image"
        data-media-library-selectable="false"
        data-media-library-details-open="true"
        data-media-library-height="70vh"
    ></div>
</div>
@endsection
