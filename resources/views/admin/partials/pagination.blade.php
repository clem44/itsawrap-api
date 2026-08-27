@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="admin-pagination__summary">
            <span>Showing</span>
            <strong>{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</strong>
            <span>of</span>
            <strong>{{ $paginator->total() }}</strong>
        </div>

        <div class="admin-pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination__button is-disabled" aria-disabled="true" aria-label="Previous page">
                    <svg class="admin-pagination__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span>Previous</span>
                </span>
            @else
                <a class="admin-pagination__button" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                    <svg class="admin-pagination__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span>Previous</span>
                </a>
            @endif

            <div class="admin-pagination__pages" aria-label="Page numbers">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="admin-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="admin-pagination__page is-current" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                            @else
                                <a class="admin-pagination__page" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a class="admin-pagination__button" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                    <span>Next</span>
                    <svg class="admin-pagination__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @else
                <span class="admin-pagination__button is-disabled" aria-disabled="true" aria-label="Next page">
                    <span>Next</span>
                    <svg class="admin-pagination__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
