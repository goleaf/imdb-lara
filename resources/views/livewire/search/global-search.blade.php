<div
    data-slot="global-search"
    x-data="globalSearchOverlay({ searchRoute: @js($searchRoute) })"
    x-on:keydown.escape.window="open = false"
    class="relative w-full [&:has(input[data-loading])_[data-slot=global-search-loading]]:block [&:has(input[data-loading])_[data-slot=global-search-results]]:hidden"
>
    <form wire:submit="submitSearch" x-on:submit="storeRecent($wire.query)">
        <x-ui.input
            wire:model.live.debounce.180ms="query"
            name="global_search"
            placeholder="Search titles, people, and themes"
            left-icon="magnifying-glass"
            clearable
            kbd="/"
            class="sb-shell-search"
            x-on:focus="open = true"
            x-on:click="open = true"
            x-on:input="open = true"
        />
    </form>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.duration.150ms
        x-on:click.outside="open = false"
        class="absolute left-0 right-0 top-full z-50 mt-3"
    >
        <div class="sb-search-overlay rounded-[1.6rem] p-4 sm:p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <div class="sb-auth-kicker">Global Search</div>
                    <div class="sb-search-group-title">Find titles, people, and themes fast</div>
                    <div class="sb-search-group-copy">
                        Live grouped suggestions from the imported catalog, with posters, portraits, and theme lanes.
                    </div>
                </div>

                <x-ui.button
                    type="button"
                    variant="none"
                    size="sm"
                    icon="x-mark"
                    x-on:click="open = false"
                    class="!h-9 !w-9 !rounded-full border border-white/8 bg-white/[0.03] text-[#c0b5a5] transition hover:bg-white/[0.06] hover:text-[#f4eee5]"
                    data-slot="global-search-close"
                    aria-label="Close search"
                />
            </div>

            <div data-slot="global-search-loading" class="sb-search-panel hidden rounded-[1.35rem] border border-white/8 bg-white/[0.03] p-4">
                <div class="space-y-3">
                    <div>
                        <div class="sb-search-group-title inline-flex items-center gap-2">
                            <x-ui.icon name="magnifying-glass" class="size-4 text-[#d6b574]" />
                            <span>Searching the catalog</span>
                        </div>
                        <div class="sb-search-group-copy">
                            Titles, people, and theme lanes are updating live.
                        </div>
                    </div>

                    @foreach (range(1, 4) as $index)
                        <div class="flex items-center gap-3" wire:key="global-search-skeleton-row-{{ $index }}">
                            <x-ui.skeleton class="h-16 w-16 rounded-[1rem]" />
                            <div class="min-w-0 flex-1 space-y-2">
                                <x-ui.skeleton.text class="w-2/3" />
                                <x-ui.skeleton.text class="w-1/2" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div data-slot="global-search-results" class="space-y-4">
                @if ($hasSearchTerm)
                    @if ($topSuggestion['record'])
                        <div data-slot="global-search-top-suggestion" class="sb-search-panel rounded-[1.35rem] border border-white/8 bg-white/[0.03] p-4">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <div class="sb-search-group-title inline-flex items-center gap-2">
                                        <x-ui.icon name="{{ $topSuggestion['type'] === 'title' ? 'film' : 'user' }}" class="size-4 {{ $topSuggestion['type'] === 'title' ? 'text-[#d6b574]' : 'text-[#a8bfd7]' }}" />
                                        <span>Top suggestion</span>
                                    </div>
                                    <div class="sb-search-group-copy">
                                        {{ $topSuggestion['type'] === 'title'
                                            ? 'The strongest title match from the imported catalog.'
                                            : 'The strongest profile match from the imported catalog.' }}
                                    </div>
                                </div>
                                <span class="sb-search-chip {{ $topSuggestion['type'] === 'title' ? 'sb-search-chip--accent' : 'sb-search-chip--people' }}">
                                    {{ $topSuggestion['type'] === 'title' ? 'Title' : 'Person' }}
                                </span>
                            </div>

                            @if ($topSuggestion['type'] === 'title')
                                <a
                                    href="{{ route('public.titles.show', $topSuggestion['record']) }}"
                                    x-on:click="storeRecent(@js($trimmedQuery))"
                                    class="sb-search-item sb-search-item--title flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                >
                                    <div class="sb-search-item-media sb-search-item-media--title h-20 w-14 shrink-0 overflow-hidden rounded-[1rem]">
                                        @if ($topSuggestion['record']->preferredPoster())
                                            <img
                                                src="{{ $topSuggestion['record']->preferredPoster()->url }}"
                                                alt="{{ $topSuggestion['record']->preferredPoster()->alt_text ?: $topSuggestion['record']->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                <x-ui.icon name="film" class="size-5" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                            {{ $topSuggestion['record']->name }}
                                        </div>
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                                {{ $topSuggestion['record']->typeLabel() }}
                                            </span>
                                            @if ($topSuggestion['record']->release_year)
                                                <span class="sb-search-chip sb-search-chip--tight">
                                                    {{ $topSuggestion['record']->release_year }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                </a>
                            @else
                                <a
                                    href="{{ route('public.people.show', $topSuggestion['record']) }}"
                                    x-on:click="storeRecent(@js($trimmedQuery))"
                                    class="sb-search-item sb-search-item--person flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                >
                                    <div class="sb-search-item-media sb-search-item-media--person h-16 w-16 shrink-0 overflow-hidden rounded-[1rem]">
                                        @if ($topSuggestion['record']->preferredHeadshot())
                                            <img
                                                src="{{ $topSuggestion['record']->preferredHeadshot()->url }}"
                                                alt="{{ $topSuggestion['record']->preferredHeadshot()->alt_text ?: $topSuggestion['record']->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                <x-ui.icon name="user" class="size-5" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                            {{ $topSuggestion['record']->name }}
                                        </div>
                                        <div data-slot="global-search-person-suggestion-metrics" class="sb-search-meta">
                                            @if ($topSuggestion['record']->popularityRankBadgeLabel())
                                                <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">
                                                    {{ $topSuggestion['record']->popularityRankBadgeLabel() }}
                                                </span>
                                            @endif
                                            <span class="sb-search-chip sb-search-chip--tight">
                                                {{ $topSuggestion['record']->creditsBadgeLabel() }}
                                            </span>
                                        </div>
                                    </div>

                                    <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($hasSuggestions)
                        <div class="grid gap-3 xl:grid-cols-3">
                            @foreach ($visibleSections as $section)
                                <section
                                    wire:key="global-search-section-{{ $section['key'] }}"
                                    class="sb-search-panel {{ $section['panelClass'] }} rounded-[1.35rem] p-4"
                                >
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <div class="sb-search-group-title inline-flex items-center gap-2">
                                                <x-ui.icon
                                                    :name="$section['icon']"
                                                    class="size-4 {{ $section['key'] === 'titles' ? 'text-[#d6b574]' : ($section['key'] === 'people' ? 'text-[#a8bfd7]' : 'text-[#cdb790]') }}"
                                                />
                                                <span>{{ $section['label'] }}</span>
                                            </div>
                                            <div class="sb-search-group-copy">{{ $section['copy'] }}</div>
                                        </div>
                                        <span class="sb-search-chip {{ $section['chipClass'] }}">
                                            {{ $section['items']->count() }} shown
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach ($section['items'] as $suggestion)
                                            @if ($section['key'] === 'titles')
                                                <a
                                                    wire:key="global-search-{{ $section['key'] }}-{{ $suggestion->id }}"
                                                    href="{{ route('public.titles.show', $suggestion) }}"
                                                    x-on:click="storeRecent(@js($trimmedQuery))"
                                                    class="sb-search-item sb-search-item--title flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                                >
                                                    <div class="sb-search-item-media sb-search-item-media--title h-20 w-14 shrink-0 overflow-hidden rounded-[1rem]">
                                                        @if ($suggestion->preferredPoster())
                                                            <img
                                                                src="{{ $suggestion->preferredPoster()->url }}"
                                                                alt="{{ $suggestion->preferredPoster()->alt_text ?: $suggestion->name }}"
                                                                class="h-full w-full object-cover"
                                                                loading="lazy"
                                                            >
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                                <x-ui.icon name="film" class="size-5" />
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="min-w-0 flex-1 space-y-1">
                                                        <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                                            {{ $suggestion->name }}
                                                        </div>
                                                        <div class="sb-search-meta">
                                                            <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                                                {{ $suggestion->typeLabel() }}
                                                            </span>
                                                            @if ($suggestion->release_year)
                                                                <span class="sb-search-chip sb-search-chip--tight">
                                                                    {{ $suggestion->release_year }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                                </a>
                                            @elseif ($section['key'] === 'people')
                                                <a
                                                    wire:key="global-search-{{ $section['key'] }}-{{ $suggestion->id }}"
                                                    href="{{ route('public.people.show', $suggestion) }}"
                                                    x-on:click="storeRecent(@js($trimmedQuery))"
                                                    class="sb-search-item sb-search-item--person flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                                >
                                                    <div class="sb-search-item-media sb-search-item-media--person h-16 w-16 shrink-0 overflow-hidden rounded-[1rem]">
                                                        @if ($suggestion->preferredHeadshot())
                                                            <img
                                                                src="{{ $suggestion->preferredHeadshot()->url }}"
                                                                alt="{{ $suggestion->preferredHeadshot()->alt_text ?: $suggestion->name }}"
                                                                class="h-full w-full object-cover"
                                                                loading="lazy"
                                                            >
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                                <x-ui.icon name="user" class="size-5" />
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="min-w-0 flex-1 space-y-1">
                                                        <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                                            {{ $suggestion->name }}
                                                        </div>
                                                        <div class="sb-search-meta">
                                                            <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">
                                                                {{ $suggestion->primaryProfessionLabel() }}
                                                            </span>
                                                            @if ($suggestion->secondaryProfessionLabel() !== '')
                                                                <span class="sb-search-meta-copy truncate">
                                                                    {{ $suggestion->secondaryProfessionLabel() }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div data-slot="global-search-person-suggestion-metrics" class="sb-search-meta">
                                                            @if ($suggestion->popularityRankBadgeLabel())
                                                                <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">
                                                                    {{ $suggestion->popularityRankBadgeLabel() }}
                                                                </span>
                                                            @endif
                                                            <span class="sb-search-chip sb-search-chip--tight">
                                                                {{ $suggestion->creditsBadgeLabel() }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                                </a>
                                            @else
                                                <a
                                                    wire:key="global-search-{{ $section['key'] }}-{{ $suggestion->id }}"
                                                    href="{{ route('public.interest-categories.show', $suggestion) }}"
                                                    x-on:click="storeRecent(@js($trimmedQuery))"
                                                    class="sb-search-item sb-search-item--person flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                                >
                                                    <div class="sb-search-item-media sb-search-item-media--person flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[1rem] bg-white/[0.03] text-[#cdb790]">
                                                        <x-ui.icon name="squares-2x2" class="size-5" />
                                                    </div>

                                                    <div class="min-w-0 flex-1 space-y-1">
                                                        <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                                            {{ $suggestion->name }}
                                                        </div>
                                                        <div data-slot="global-search-theme-suggestion-metrics" class="sb-search-meta">
                                                            <span class="sb-search-chip sb-search-chip--tight">
                                                                {{ $suggestion->interestCountBadgeLabel() }}
                                                            </span>
                                                            @if ($suggestion->titleLinkedInterestCount() > 0)
                                                                <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">
                                                                    {{ $suggestion->titleLinkedInterestCountBadgeLabel() }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty
                            data-slot="global-search-no-matches"
                            class="sb-search-panel !rounded-[1.35rem] !border !border-dashed !border-white/10 !bg-white/[0.02] !p-6"
                        >
                            <x-ui.heading level="h3" class="sb-search-group-title">No quick matches</x-ui.heading>
                            <x-ui.text class="sb-search-group-copy mx-auto mt-2 max-w-md !text-[#9e9384]">
                                Try a broader title, actor, creator, or theme keyword. The full search page will still let you widen the query and filter further.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif

                    <div class="border-t border-white/8 pt-4">
                        <x-ui.button
                            type="button"
                            variant="none"
                            size="sm"
                            iconAfter="arrow-right"
                            x-on:click="goToSearch(@js($trimmedQuery))"
                            class="!h-auto !px-0 !py-0 text-sm font-medium text-[#dcc38d] transition hover:text-[#f5ddaa]"
                            data-slot="global-search-view-all"
                        >
                            View all results for "{{ $trimmedQuery }}"
                        </x-ui.button>
                    </div>
                @else
                    <section class="sb-search-panel rounded-[1.35rem] p-4">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <div class="sb-search-group-title">Recent Searches</div>
                                <div class="sb-search-group-copy">Jump back into titles and people you looked up recently on this device.</div>
                            </div>
                            <span class="sb-search-chip" x-show="recent.length > 0" x-text="`${recent.length} saved`"></span>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2" x-show="recent.length > 0">
                            <template x-for="item in recent" :key="item">
                                <div class="sb-search-item flex items-center justify-between gap-3 border border-white/6 bg-white/[0.025] px-3 py-3">
                                    <x-ui.button
                                        type="button"
                                        variant="none"
                                        size="sm"
                                        class="min-w-0 flex-1 !h-auto !justify-start !px-0 !py-0 text-left text-sm font-medium text-[#f4eee5]"
                                        x-on:click="goToSearch(item)"
                                        data-slot="global-search-recent-link"
                                    >
                                        <span class="truncate" x-text="item"></span>
                                    </x-ui.button>

                                    <x-ui.button
                                        type="button"
                                        variant="none"
                                        size="sm"
                                        icon="x-mark"
                                        class="!h-8 !w-8 !rounded-full border border-white/8 bg-white/[0.03] text-[#9e9384] transition hover:bg-white/[0.06] hover:text-[#f4eee5]"
                                        x-on:click="removeRecent(item)"
                                        data-slot="global-search-recent-remove"
                                        aria-label="Remove recent search"
                                    />
                                </div>
                            </template>
                        </div>

                        <x-ui.empty
                            x-show="recent.length === 0"
                            data-slot="global-search-recent-empty"
                            class="!rounded-[1rem] !border !border-dashed !border-white/10 !px-4 !py-6 !text-sm"
                        >
                            <x-ui.text class="!text-sm !text-[#9e9384]">
                                Start typing to surface fast title and people matches.
                            </x-ui.text>
                        </x-ui.empty>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
