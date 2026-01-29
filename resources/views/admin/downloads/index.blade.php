@extends('admin.layouts.app')

@section('title', 'Downloads')
@section('header', 'Downloads')

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Downloads</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Manage downloadable files for your users.</p>
            </div>
            <a href="{{ route('admin.downloads.create') }}" class="btn btn-primary btn-forest">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Download
            </a>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($downloads as $download)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($download->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $download->name }}</div>
                                    <div class="user-meta">{{ $download->filename }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ number_format($download->size / 1048576, 2) }} MB</span>
                        </td>
                        <td>
                            @php
                                $statusColor = $download->published
                                    ? ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50']
                                    : ['bg' => 'rgba(244, 67, 54, 0.2)', 'text' => '#F44336'];
                            @endphp
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                                {{ $download->published ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.downloads.edit', $download) }}" class="action-btn edit" title="Edit Download">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="{{ asset($download->filepath) }}" class="action-btn view" title="Download File" target="_blank" rel="noopener">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v12m0 0l-3-3m3 3l3-3M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No downloads yet</h3>
                                <p class="empty-state-text">Upload files to make them available for download.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($downloads->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $downloads->links() }}
        </div>
    @endif
</div>
@endsection
