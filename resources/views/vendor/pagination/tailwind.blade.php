@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-base-content/70">
            @if ($paginator->firstItem())
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} di {{ $paginator->total() }}
            @else
                {{ $paginator->count() }} risultati
            @endif
        </p>

        <div class="join">
            @if ($paginator->onFirstPage())
                <button class="join-item btn btn-sm btn-disabled" disabled aria-label="{{ __('pagination.previous') }}">«</button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="join-item btn btn-sm" aria-label="{{ __('pagination.previous') }}">«</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <button class="join-item btn btn-sm btn-disabled" disabled>{{ $element }}</button>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button class="join-item btn btn-sm btn-active" aria-current="page">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="join-item btn btn-sm" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="join-item btn btn-sm" aria-label="{{ __('pagination.next') }}">»</a>
            @else
                <button class="join-item btn btn-sm btn-disabled" disabled aria-label="{{ __('pagination.next') }}">»</button>
            @endif
        </div>
    </nav>
@endif
