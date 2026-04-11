@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        <div class="flex items-center justify-between gap-2 sm:hidden">

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

        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-3">

            <div>
                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-neutral-950 dark:text-neutral-50">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-semibold text-neutral-950 dark:text-neutral-50">{{ $paginator->lastItem() }}</span>
                    @else
                        <span class="font-semibold text-neutral-950 dark:text-neutral-50">{{ $paginator->count() }}</span>
                    @endif
                    {!! __('of') !!}
                    <span class="font-semibold text-neutral-950 dark:text-neutral-50">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </x-ui.text>
            </div>

            <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">

                @if ($paginator->onFirstPage())
                    <x-ui.pagination.control disabled icon="chevron-left" aria-disabled="true">
                        {!! __('pagination.previous') !!}
                    </x-ui.pagination.control>
                @else
                    <x-ui.pagination.control :href="$paginator->previousPageUrl()" rel="prev" icon="chevron-left" aria-label="{{ __('pagination.previous') }}">
                        {!! __('pagination.previous') !!}
                    </x-ui.pagination.control>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <x-ui.pagination.control disabled aria-hidden="true">
                            {{ $element }}
                        </x-ui.pagination.control>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <x-ui.pagination.control current aria-label="{{ __('Current page, page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </x-ui.pagination.control>
                                </span>
                            @else
                                <x-ui.pagination.control :href="$url" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </x-ui.pagination.control>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <x-ui.pagination.control :href="$paginator->nextPageUrl()" rel="next" iconAfter="chevron-right" aria-label="{{ __('pagination.next') }}">
                        {!! __('pagination.next') !!}
                    </x-ui.pagination.control>
                @else
                    <x-ui.pagination.control disabled iconAfter="chevron-right" aria-disabled="true">
                        {!! __('pagination.next') !!}
                    </x-ui.pagination.control>
                @endif
            </div>
        </div>
    </nav>
@endif
