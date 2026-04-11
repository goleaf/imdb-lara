@php
    $mobileActionClasses = 'inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold leading-5 transition duration-150';
    $mobileEnabledClasses = $mobileActionClasses.' border-[#322b21] bg-[#151310] text-[#f4eee5] hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25';
    $mobileDisabledClasses = $mobileActionClasses.' cursor-not-allowed border-[#2a251d] bg-[#12100d] text-[#7c7468]';
    $statusCopyClasses = 'text-sm leading-5 text-[#8f877a] dark:text-[#c2b8ab]';
    $statusValueClasses = 'font-semibold text-[#f4eee5] dark:text-[#f4eee5]';
    $pageBaseClasses = 'inline-flex min-w-10 items-center justify-center border px-3.5 py-2 text-sm font-semibold leading-5 transition duration-150';
    $pageEdgeClasses = 'inline-flex items-center justify-center border px-2.5 py-2 text-sm font-semibold leading-5 transition duration-150';
    $pageLinkClasses = $pageBaseClasses.' -ml-px border-[#322b21] bg-[#151310] text-[#c2b8ab] hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-[#f4eee5] focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25';
    $pageCurrentClasses = $pageBaseClasses.' -ml-px border-[#e0c489]/70 bg-[#e0c489] text-[#2d2417] shadow-[0_14px_30px_rgba(107,78,30,0.22)]';
    $pageDotsClasses = $pageBaseClasses.' -ml-px cursor-default border-[#2a251d] bg-[#12100d] text-[#8f877a]';
    $pageEdgeLinkClasses = $pageEdgeClasses.' border-[#322b21] bg-[#151310] text-[#c2b8ab] hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-[#f4eee5] focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25';
    $pageEdgeDisabledClasses = $pageEdgeClasses.' cursor-not-allowed border-[#2a251d] bg-[#12100d] text-[#7c7468]';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        <div class="flex gap-2 items-center justify-between sm:hidden">

            @if ($paginator->onFirstPage())
                <span class="{{ $mobileDisabledClasses }}">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $mobileEnabledClasses }}">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $mobileEnabledClasses }}">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="{{ $mobileDisabledClasses }}">
                    {!! __('pagination.next') !!}
                </span>
            @endif

        </div>

        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">

            <div>
                <p class="{{ $statusCopyClasses }}">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="{{ $statusValueClasses }}">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="{{ $statusValueClasses }}">{{ $paginator->lastItem() }}</span>
                    @else
                        <span class="{{ $statusValueClasses }}">{{ $paginator->count() }}</span>
                    @endif
                    {!! __('of') !!}
                    <span class="{{ $statusValueClasses }}">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex rtl:flex-row-reverse">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="{{ $pageEdgeDisabledClasses }} rounded-l-full" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $pageEdgeLinkClasses }} rounded-l-full" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="{{ $pageDotsClasses }}">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="{{ $pageCurrentClasses }}">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="{{ $pageLinkClasses }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $pageEdgeLinkClasses }} -ml-px rounded-r-full" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="{{ $pageEdgeDisabledClasses }} -ml-px rounded-r-full" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
