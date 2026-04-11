@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-2">

        @if ($paginator->onFirstPage())
            <x-ui.pagination.control disabled aria-disabled="true">
                {!! __('pagination.previous') !!}
            </x-ui.pagination.control>
        @else
            <x-ui.pagination.control :href="$paginator->previousPageUrl()" rel="prev" aria-label="{{ __('pagination.previous') }}">
                {!! __('pagination.previous') !!}
            </x-ui.pagination.control>
        @endif

        @if ($paginator->hasMorePages())
            <x-ui.pagination.control :href="$paginator->nextPageUrl()" rel="next" aria-label="{{ __('pagination.next') }}">
                {!! __('pagination.next') !!}
            </x-ui.pagination.control>
        @else
            <x-ui.pagination.control disabled aria-disabled="true">
                {!! __('pagination.next') !!}
            </x-ui.pagination.control>
        @endif

    </nav>
@endif
