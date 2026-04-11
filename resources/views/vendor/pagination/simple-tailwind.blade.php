@php
    $actionClasses = 'inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold leading-5 transition duration-150';
    $enabledClasses = $actionClasses.' border-[#322b21] bg-[#151310] text-[#f4eee5] hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25';
    $disabledClasses = $actionClasses.' cursor-not-allowed border-[#2a251d] bg-[#12100d] text-[#7c7468]';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between">

        @if ($paginator->onFirstPage())
            <span class="{{ $disabledClasses }}">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $enabledClasses }}">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $enabledClasses }}">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="{{ $disabledClasses }}">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
