@extends('admin.layouts.app')

@section('title', 'Add Download')
@section('header', 'Add Download')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="form-container animate-in">
    <a href="{{ route('admin.downloads.index') }}" class="back-link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Downloads
    </a>

    <div class="form-card with-accent">
        <div class="form-header">
            <h1 class="heading-serif text-2xl font-semibold">Upload Download</h1>
            <p>Add a new file for users to download.</p>
        </div>

        <form method="POST" action="{{ route('admin.downloads.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-section">
                <h2 class="section-title">File Details</h2>

                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="form-input @error('name') error @enderror"
                           placeholder="iOS App (IPA)">
                    @error('name')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="file" class="form-label">File</label>
                    <input type="file" name="file" id="file" required
                           class="form-input @error('file') error @enderror">
                    @error('file')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="bundle_identifier" class="form-label">Bundle Identifier <span class="optional">(Required for IPA)</span></label>
                    <input type="text" name="bundle_identifier" id="bundle_identifier" value="{{ old('bundle_identifier') }}"
                           class="form-input @error('bundle_identifier') error @enderror"
                           placeholder="com.yourcompany.yourapp">
                    @error('bundle_identifier')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="bundle_version" class="form-label">Bundle Version <span class="optional">(Required for IPA)</span></label>
                        <input type="text" name="bundle_version" id="bundle_version" value="{{ old('bundle_version') }}"
                               class="form-input @error('bundle_version') error @enderror"
                               placeholder="1.0.0">
                        @error('bundle_version')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="title" class="form-label">Title <span class="optional">(Optional)</span></label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}"
                               class="form-input @error('title') error @enderror"
                               placeholder="Your App Name">
                        @error('title')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="filetype" class="form-label">File Type <span class="optional">(Optional)</span></label>
                        <input type="text" name="filetype" id="filetype" value="{{ old('filetype') }}"
                               class="form-input @error('filetype') error @enderror"
                               placeholder="IPA">
                        @error('filetype')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="type" class="form-label">Category <span class="optional">(Optional)</span></label>
                        <input type="text" name="type" id="type" value="{{ old('type') }}"
                               class="form-input @error('type') error @enderror"
                               placeholder="iOS">
                        @error('type')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="published" value="1" class="form-checkbox" {{ old('published', true) ? 'checked' : '' }}>
                        Published
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.downloads.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-forest">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Save Download
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
