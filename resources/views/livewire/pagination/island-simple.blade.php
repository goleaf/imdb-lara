<div>
    @php
        $buttonClasses = 'relative inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold leading-5 transition duration-150';
        $enabledClasses = $buttonClasses.' border-[#322b21] bg-[#151310] text-[#f4eee5] hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25';
        $disabledClasses = $buttonClasses.' cursor-default border-[#2a251d] bg-[#12100d] text-[#7c7468]';
    @endphp

    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
            <span>
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabledClasses }}">
                        {!! __('pagination.previous') !!}
                    </span>
                @else
                    @if (method_exists($paginator, 'getCursorName'))
                        <button
                            type="button"
                            dusk="previousPage"
                            wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}"
                            wire:click="setPage('{{ $paginator->previousCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
                            @if (method_exists($this, 'paginationIslandName') && filled($this->paginationIslandName())) wire:island="{{ $this->paginationIslandName() }}" @endif
                            @if (($scrollTo ?? 'body') !== false) x-on:click="($el.closest('{{ $scrollTo ?? 'body' }}') || document.querySelector('{{ $scrollTo ?? 'body' }}')).scrollIntoView()" @endif
                            wire:loading.attr="disabled"
                            class="{{ $enabledClasses }}"
                        >
                            {!! __('pagination.previous') !!}
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            @if (method_exists($this, 'paginationIslandName') && filled($this->paginationIslandName())) wire:island="{{ $this->paginationIslandName() }}" @endif
                            @if (($scrollTo ?? 'body') !== false) x-on:click="($el.closest('{{ $scrollTo ?? 'body' }}') || document.querySelector('{{ $scrollTo ?? 'body' }}')).scrollIntoView()" @endif
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            class="{{ $enabledClasses }}"
                        >
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif
                @endif
            </span>

            <span>
                @if ($paginator->hasMorePages())
                    @if (method_exists($paginator, 'getCursorName'))
                        <button
                            type="button"
                            dusk="nextPage"
                            wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}"
                            wire:click="setPage('{{ $paginator->nextCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
                            @if (method_exists($this, 'paginationIslandName') && filled($this->paginationIslandName())) wire:island="{{ $this->paginationIslandName() }}" @endif
                            @if (($scrollTo ?? 'body') !== false) x-on:click="($el.closest('{{ $scrollTo ?? 'body' }}') || document.querySelector('{{ $scrollTo ?? 'body' }}')).scrollIntoView()" @endif
                            wire:loading.attr="disabled"
                            class="{{ $enabledClasses }}"
                        >
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            @if (method_exists($this, 'paginationIslandName') && filled($this->paginationIslandName())) wire:island="{{ $this->paginationIslandName() }}" @endif
                            @if (($scrollTo ?? 'body') !== false) x-on:click="($el.closest('{{ $scrollTo ?? 'body' }}') || document.querySelector('{{ $scrollTo ?? 'body' }}')).scrollIntoView()" @endif
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            class="{{ $enabledClasses }}"
                        >
                            {!! __('pagination.next') !!}
                        </button>
                    @endif
                @else
                    <span class="{{ $disabledClasses }}">
                        {!! __('pagination.next') !!}
                    </span>
                @endif
            </span>
        </nav>
    @endif
</div>
