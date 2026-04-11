<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
            <span>
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex cursor-default items-center rounded-full border border-[#2a251d] bg-[#12100d] px-4 py-2 text-sm font-semibold leading-5 text-[#7c7468] transition duration-150">
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
                            class="relative inline-flex items-center rounded-full border border-[#322b21] bg-[#151310] px-4 py-2 text-sm font-semibold leading-5 text-[#f4eee5] transition duration-150 hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25 data-loading:pointer-events-none data-loading:cursor-default data-loading:opacity-70"
                        >
                            {!! __('pagination.previous') !!}
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            @if (method_exists($this, 'paginationIslandName') && filled($this->paginationIslandName())) wire:island="{{ $this->paginationIslandName() }}" @endif
                            @if (($scrollTo ?? 'body') !== false) x-on:click="($el.closest('{{ $scrollTo ?? 'body' }}') || document.querySelector('{{ $scrollTo ?? 'body' }}')).scrollIntoView()" @endif
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            class="relative inline-flex items-center rounded-full border border-[#322b21] bg-[#151310] px-4 py-2 text-sm font-semibold leading-5 text-[#f4eee5] transition duration-150 hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25 data-loading:pointer-events-none data-loading:cursor-default data-loading:opacity-70"
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
                            class="relative inline-flex items-center rounded-full border border-[#322b21] bg-[#151310] px-4 py-2 text-sm font-semibold leading-5 text-[#f4eee5] transition duration-150 hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25 data-loading:pointer-events-none data-loading:cursor-default data-loading:opacity-70"
                        >
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            @if (method_exists($this, 'paginationIslandName') && filled($this->paginationIslandName())) wire:island="{{ $this->paginationIslandName() }}" @endif
                            @if (($scrollTo ?? 'body') !== false) x-on:click="($el.closest('{{ $scrollTo ?? 'body' }}') || document.querySelector('{{ $scrollTo ?? 'body' }}')).scrollIntoView()" @endif
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            class="relative inline-flex items-center rounded-full border border-[#322b21] bg-[#151310] px-4 py-2 text-sm font-semibold leading-5 text-[#f4eee5] transition duration-150 hover:border-[#5b4b33] hover:bg-[#1d1914] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#d6b574]/25 data-loading:pointer-events-none data-loading:cursor-default data-loading:opacity-70"
                        >
                            {!! __('pagination.next') !!}
                        </button>
                    @endif
                @else
                    <span class="relative inline-flex cursor-default items-center rounded-full border border-[#2a251d] bg-[#12100d] px-4 py-2 text-sm font-semibold leading-5 text-[#7c7468] transition duration-150">
                        {!! __('pagination.next') !!}
                    </span>
                @endif
            </span>
        </nav>
    @endif
</div>
