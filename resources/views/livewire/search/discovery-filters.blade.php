<div>
@island(name: 'discover-results-page')
    <div class="sb-discovery-layout grid gap-6 xl:grid-cols-[19.5rem_minmax(0,1fr)]" data-slot="discover-filters-island">
    <aside class="self-start xl:sticky xl:top-24">
        <x-ui.card class="sb-filter-shell sb-discovery-sidebar !max-w-none rounded-[1.6rem] p-4 sm:p-5" data-slot="discover-advanced-filters">
            <div class="space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="sb-page-kicker">Filter Panel</div>
                        <x-ui.heading level="h2" size="lg" class="sb-home-section-heading">Deep Discovery</x-ui.heading>
                        <x-ui.text class="sb-home-section-copy text-sm">
                            Shape the catalog by release range, awards, vote volume, language, runtime, and origin.
                        </x-ui.text>
                    </div>

                    @if ($this->viewData['activeFilterCount'] > 0)
                        <x-ui.button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">
                            Clear
                        </x-ui.button>
                    @endif
                </div>

                <div
                    class="sb-discovery-active-shell {{ $this->viewData['activeFilters']->isNotEmpty() ? 'sb-discovery-active-shell--active' : '' }}"
                    data-slot="discover-active-filters"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="sb-discovery-active-kicker">{{ $this->viewData['activeFilters']->isNotEmpty() ? 'Current state' : 'Start broad' }}</div>
                            <div class="sb-discovery-section-copy">
                                @if ($this->viewData['activeFilters']->isNotEmpty())
                                    {{ $this->viewData['activeFilterCount'] }} filters are actively shaping the discovery results.
                                @else
                                    Everything is open. Start with a keyword, title type, theme lane, or awards filter.
                                @endif
                            </div>
                        </div>

                        <span class="sb-discovery-active-count">
                            {{ $this->viewData['activeFilters']->isNotEmpty() ? $this->viewData['activeFilterCount'].' active' : 'All titles' }}
                        </span>
                    </div>

                    @if ($this->viewData['activeFilters']->isNotEmpty())
                        <div class="sb-discovery-active-filter-list">
                            @foreach ($this->viewData['activeFilters'] as $activeFilter)
                                <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                    <x-ui.icon :name="$activeFilter['icon']" class="size-3" />
                                    {{ $activeFilter['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="sb-discovery-section-card {{ $this->viewData['keywordActive'] ? 'sb-discovery-section-card--active' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="sb-discovery-section-icon">
                            <x-ui.icon name="magnifying-glass" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="sb-discovery-section-title">Keyword</div>
                            <div class="sb-discovery-section-copy">Search titles, synonyms, or keyword-rich plot notes.</div>
                        </div>

                        @if ($this->viewData['keywordActive'])
                            <span class="sb-discovery-section-state">Active</span>
                        @endif
                    </div>

                    <x-ui.field>
                        <x-ui.label>Keyword</x-ui.label>
                        <x-ui.autocomplete
                            wire:model.live.debounce.300ms="search"
                            name="search"
                            placeholder="Search titles or keywords"
                            left-icon="magnifying-glass"
                            clearable
                            class="sb-filter-control"
                        >
                            @foreach ($this->viewData['searchSuggestions'] as $suggestedTitle)
                                <x-ui.autocomplete.item
                                    wire:key="discover-suggestion-{{ $suggestedTitle->id }}"
                                    :value="$suggestedTitle->name"
                                    :label="$suggestedTitle->name"
                                >
                                    <div class="flex items-center justify-between gap-3 py-1">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                                {{ $suggestedTitle->name }}
                                            </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                {{ $suggestedTitle->typeLabel() }}
                                                @if ($suggestedTitle->release_year)
                                                    · {{ $suggestedTitle->release_year }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </x-ui.autocomplete.item>
                            @endforeach
                        </x-ui.autocomplete>
                    </x-ui.field>
                </div>

                <div class="sb-discovery-section-card {{ $this->viewData['coreFiltersActive'] ? 'sb-discovery-section-card--active' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="sb-discovery-section-icon">
                            <x-ui.icon name="film" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="sb-discovery-section-title">Core Filters</div>
                            <div class="sb-discovery-section-copy">Narrow the title pool by format, genre, theme lane, and awards profile.</div>
                        </div>

                        @if ($this->viewData['coreFiltersActive'])
                            <span class="sb-discovery-section-state">Active</span>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <x-ui.field>
                            <x-ui.label>Title type</x-ui.label>
                            <x-ui.combobox wire:model.live="type" class="sb-filter-control w-full" placeholder="All types" clearable>
                                @foreach ($this->viewData['titleTypes'] as $typeOption)
                                    <x-ui.combobox.option
                                        wire:key="discover-type-{{ $typeOption->value }}"
                                        value="{{ $typeOption->value }}"
                                        :icon="$typeOption->icon()"
                                    >
                                        {{ $typeOption->label() }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Genre</x-ui.label>
                            <x-ui.combobox wire:model.live="genre" class="sb-filter-control w-full" placeholder="All genres" clearable>
                                @foreach ($this->viewData['genres'] as $genreOption)
                                    <x-ui.combobox.option wire:key="discover-genre-{{ $genreOption->id }}" value="{{ $genreOption->slug }}" icon="tag">
                                        {{ $genreOption->name }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Theme</x-ui.label>
                            <x-ui.combobox wire:model.live="theme" class="sb-filter-control w-full" placeholder="All themes" clearable>
                                @foreach ($this->viewData['interestCategories'] as $interestCategoryOption)
                                    <x-ui.combobox.option wire:key="discover-theme-{{ $interestCategoryOption->id }}" value="{{ $interestCategoryOption->slug }}" icon="squares-2x2">
                                        {{ $interestCategoryOption->name }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field class="sm:col-span-2 xl:col-span-1">
                            <x-ui.label>Awards</x-ui.label>
                            <x-ui.combobox wire:model.live="awards" class="sb-filter-control w-full" placeholder="Any awards status" clearable>
                                @foreach ($this->viewData['awardOptions'] as $awardOption)
                                    <x-ui.combobox.option wire:key="discover-awards-{{ $awardOption['value'] }}" value="{{ $awardOption['value'] }}" icon="trophy">
                                        {{ $awardOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>
                    </div>
                </div>

                <div class="sb-discovery-section-card {{ $this->viewData['releaseFiltersActive'] ? 'sb-discovery-section-card--active' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="sb-discovery-section-icon">
                            <x-ui.icon name="calendar-days" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="sb-discovery-section-title">Release Date</div>
                            <div class="sb-discovery-section-copy">Work within a release window instead of a loose popularity browse.</div>
                        </div>

                        @if ($this->viewData['releaseFiltersActive'])
                            <span class="sb-discovery-section-state">Active</span>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <x-ui.field>
                            <x-ui.label>Release from</x-ui.label>
                            <x-ui.combobox wire:model.live="yearFrom" class="sb-filter-control w-full" placeholder="Any year" clearable>
                                @foreach ($this->viewData['years'] as $yearOption)
                                    <x-ui.combobox.option wire:key="discover-year-from-{{ $yearOption }}" value="{{ $yearOption }}" icon="calendar-days">
                                        {{ $yearOption }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Release to</x-ui.label>
                            <x-ui.combobox wire:model.live="yearTo" class="sb-filter-control w-full" placeholder="Any year" clearable>
                                @foreach ($this->viewData['years'] as $yearOption)
                                    <x-ui.combobox.option wire:key="discover-year-to-{{ $yearOption }}" value="{{ $yearOption }}" icon="calendar-days">
                                        {{ $yearOption }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>
                    </div>
                </div>

                <div class="sb-discovery-section-card {{ $this->viewData['signalFiltersActive'] ? 'sb-discovery-section-card--active' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="sb-discovery-section-icon">
                            <x-ui.icon name="star" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="sb-discovery-section-title">Signals</div>
                            <div class="sb-discovery-section-copy">Filter by critical strength, audience volume, and runtime commitment.</div>
                        </div>

                        @if ($this->viewData['signalFiltersActive'])
                            <span class="sb-discovery-section-state">Active</span>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <x-ui.field>
                            <x-ui.label>Rating</x-ui.label>
                            <x-ui.combobox wire:model.live="minimumRating" class="sb-filter-control w-full" placeholder="Any score" clearable>
                                @foreach ($this->viewData['minimumRatings'] as $ratingFloor)
                                    <x-ui.combobox.option wire:key="discover-rating-{{ $ratingFloor }}" value="{{ $ratingFloor }}" icon="star">
                                        {{ $ratingFloor }}+
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Vote count</x-ui.label>
                            <x-ui.combobox wire:model.live="votesMin" class="sb-filter-control w-full" placeholder="Any volume" clearable>
                                @foreach ($this->viewData['voteThresholdOptions'] as $voteThresholdOption)
                                    <x-ui.combobox.option wire:key="discover-votes-{{ $voteThresholdOption['value'] }}" value="{{ $voteThresholdOption['value'] }}" icon="users">
                                        {{ $voteThresholdOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field class="sm:col-span-2 xl:col-span-1">
                            <x-ui.label>Runtime</x-ui.label>
                            <x-ui.combobox wire:model.live="runtime" class="sb-filter-control w-full" placeholder="Any runtime" clearable>
                                @foreach ($this->viewData['runtimeOptions'] as $runtimeOption)
                                    <x-ui.combobox.option wire:key="discover-runtime-{{ $runtimeOption['value'] }}" value="{{ $runtimeOption['value'] }}" icon="clock">
                                        {{ $runtimeOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>
                    </div>
                </div>

                <div class="sb-discovery-section-card {{ $this->viewData['originFiltersActive'] ? 'sb-discovery-section-card--active' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="sb-discovery-section-icon">
                            <x-ui.icon name="globe-alt" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="sb-discovery-section-title">Origin</div>
                            <div class="sb-discovery-section-copy">Surface titles by language and production country.</div>
                        </div>

                        @if ($this->viewData['originFiltersActive'])
                            <span class="sb-discovery-section-state">Active</span>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <x-ui.field>
                            <x-ui.label>Language</x-ui.label>
                            <x-ui.combobox wire:model.live="language" class="sb-filter-control w-full" placeholder="Any language" clearable>
                                @foreach ($this->viewData['languages'] as $languageOption)
                                    <x-ui.combobox.option wire:key="discover-language-{{ $languageOption['value'] }}" value="{{ $languageOption['value'] }}" icon="language">
                                        {{ $languageOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Country</x-ui.label>
                            <x-ui.combobox wire:model.live="country" class="sb-filter-control w-full" placeholder="Any country" clearable>
                                @foreach ($this->viewData['countries'] as $countryOption)
                                    <x-ui.combobox.option wire:key="discover-country-{{ $countryOption['value'] }}" value="{{ $countryOption['value'] }}" icon="globe-alt">
                                        {{ $countryOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>
                    </div>
                </div>

                <div class="sb-discovery-section-card {{ $this->viewData['orderingActive'] ? 'sb-discovery-section-card--active' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="sb-discovery-section-icon">
                            <x-ui.icon name="bars-arrow-down" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="sb-discovery-section-title">Ordering</div>
                            <div class="sb-discovery-section-copy">Re-rank the results area for popularity, ratings, trend, or recency.</div>
                        </div>

                        @if ($this->viewData['orderingActive'])
                            <span class="sb-discovery-section-state">Active</span>
                        @endif
                    </div>

                    <x-ui.field>
                        <x-ui.label>Sort</x-ui.label>
                            <x-ui.combobox wire:model.live="sort" class="sb-filter-control w-full" placeholder="Sort titles">
                                @foreach ($this->viewData['sortOptions'] as $sortOption)
                                    <x-ui.combobox.option wire:key="discover-sort-{{ $sortOption['value'] }}" value="{{ $sortOption['value'] }}" :icon="$sortOption['icon']">
                                        {{ $sortOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                        </x-ui.combobox>
                    </x-ui.field>
                </div>
            </div>
        </x-ui.card>
    </aside>

    <div class="space-y-4">
        <x-ui.card class="sb-results-shell sb-discovery-results-summary !max-w-none rounded-[1.6rem] p-5" data-slot="discover-results-shell">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(0,0.42fr))] xl:items-end">
                <div class="space-y-3">
                    <div class="sb-page-kicker">Results Area</div>
                    <x-ui.heading level="h2" size="lg" class="sb-home-section-heading">Advanced title results</x-ui.heading>
                    <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                        A discovery grid tuned for enthusiasts: poster-first cards, stronger filtering on the left, and cleaner ranking signals across awards, votes, language, and release windows.
                    </x-ui.text>
                </div>

                <div class="sb-discovery-summary-stat">
                    <div class="sb-discovery-summary-label">Showing</div>
                    <div class="sb-discovery-summary-value">{{ number_format($this->viewData['titleResultsCount']) }}</div>
                </div>

                <div class="sb-discovery-summary-stat">
                    <div class="sb-discovery-summary-label">Active filters</div>
                    <div class="sb-discovery-summary-value">{{ number_format($this->viewData['activeFilterCount']) }}</div>
                </div>

                <div class="sb-discovery-summary-stat">
                    <div class="sb-discovery-summary-label">Sort</div>
                    <div class="sb-discovery-summary-value text-[1.05rem]">{{ $this->viewData['sortLabel'] }}</div>
                </div>
            </div>

            @if ($this->viewData['activeFilters']->isNotEmpty())
                <div class="sb-discovery-active-filter-bar">
                    @foreach ($this->viewData['activeFilters'] as $activeFilter)
                        <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                            <x-ui.icon :name="$activeFilter['icon']" class="size-3" />
                            {{ $activeFilter['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <div wire:loading.delay wire:target="{{ $this->viewData['loadingTargets'] }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (range(1, 6) as $index)
                <x-ui.card class="sb-poster-card !max-w-none h-full overflow-hidden rounded-[1.4rem]" wire:key="discover-skeleton-{{ $index }}">
                    <div class="space-y-4">
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                        <x-ui.skeleton.text class="w-1/3" />
                        <x-ui.skeleton.text class="w-3/4" />
                        <x-ui.skeleton.text class="w-5/6" />
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div wire:loading.remove wire:target="{{ $this->viewData['loadingTargets'] }}" class="sb-results-shell space-y-4 rounded-[1.6rem] p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="sb-discovery-section-title">Discovery results</div>
                    <div class="sb-discovery-section-copy">Poster-led results update against the live filter rail without leaving the page.</div>
                </div>

                @if ($this->viewData['activeFilterCount'] > 0)
                    <x-ui.button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">
                        Reset filters
                    </x-ui.button>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($this->viewData['titles'] as $title)
                    <div wire:key="discover-title-{{ $title->id }}">
                        <x-catalog.title-card :title="$title" :show-summary="$this->viewData['showSummary']" />
                    </div>
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="magnifying-glass" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No titles match the current filters.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Broaden the release range, awards profile, or runtime settings to widen discovery.
                            </x-ui.text>
                        </x-ui.empty>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $this->viewData['titles']->links() }}
            </div>
        </div>
    </div>
</div>
@endisland
</div>
