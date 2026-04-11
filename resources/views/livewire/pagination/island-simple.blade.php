@php
    $paginationIslandName = isset($__livewire) && method_exists($__livewire, 'paginationIslandName')
        ? $__livewire->paginationIslandName()
        : null;
    $scrollTarget = $scrollTo ?? 'body';
    $scrollClick = $scrollTarget !== false
        ? "(\$el.closest('{$scrollTarget}') || document.querySelector('{$scrollTarget}')).scrollIntoView()"
        : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
            <span>
                @if ($paginator->onFirstPage())
                    <x-ui.pagination.control disabled aria-disabled="true">
                        {!! __('pagination.previous') !!}
                    </x-ui.pagination.control>
                @else
                    @if (method_exists($paginator, 'getCursorName'))
                        @if (filled($paginationIslandName))
                            <x-ui.pagination.control
                                dusk="previousPage"
                                wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}"
                                wire:click="setPage('{{ $paginator->previousCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
                                wire:island="{{ $paginationIslandName }}"
                                x-on:click="{{ $scrollClick }}"
                            >
                                {!! __('pagination.previous') !!}
                            </x-ui.pagination.control>
                        @else
                            <x-ui.pagination.control
                                dusk="previousPage"
                                wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}"
                                wire:click="setPage('{{ $paginator->previousCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
                                x-on:click="{{ $scrollClick }}"
                            >
                                {!! __('pagination.previous') !!}
                            </x-ui.pagination.control>
                        @endif
                    @else
                        @if (filled($paginationIslandName))
                            <x-ui.pagination.control
                                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                wire:island="{{ $paginationIslandName }}"
                                x-on:click="{{ $scrollClick }}"
                                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            >
                                {!! __('pagination.previous') !!}
                            </x-ui.pagination.control>
                        @else
                            <x-ui.pagination.control
                                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollClick }}"
                                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            >
                                {!! __('pagination.previous') !!}
                            </x-ui.pagination.control>
                        @endif
                    @endif
                @endif
            </span>

            <span>
                @if ($paginator->hasMorePages())
                    @if (method_exists($paginator, 'getCursorName'))
                        @if (filled($paginationIslandName))
                            <x-ui.pagination.control
                                dusk="nextPage"
                                wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}"
                                wire:click="setPage('{{ $paginator->nextCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
                                wire:island="{{ $paginationIslandName }}"
                                x-on:click="{{ $scrollClick }}"
                            >
                                {!! __('pagination.next') !!}
                            </x-ui.pagination.control>
                        @else
                            <x-ui.pagination.control
                                dusk="nextPage"
                                wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}"
                                wire:click="setPage('{{ $paginator->nextCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
                                x-on:click="{{ $scrollClick }}"
                            >
                                {!! __('pagination.next') !!}
                            </x-ui.pagination.control>
                        @endif
                    @else
                        @if (filled($paginationIslandName))
                            <x-ui.pagination.control
                                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                                wire:island="{{ $paginationIslandName }}"
                                x-on:click="{{ $scrollClick }}"
                                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            >
                                {!! __('pagination.next') !!}
                            </x-ui.pagination.control>
                        @else
                            <x-ui.pagination.control
                                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollClick }}"
                                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            >
                                {!! __('pagination.next') !!}
                            </x-ui.pagination.control>
                        @endif
                    @endif
                @else
                    <x-ui.pagination.control disabled aria-disabled="true">
                        {!! __('pagination.next') !!}
                    </x-ui.pagination.control>
                @endif
            </span>
        </nav>
    @endif
</div>
