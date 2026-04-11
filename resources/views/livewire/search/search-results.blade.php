<div>
@island(name: 'search-results-page')
<div class="space-y-6" data-slot="search-results-island">
    <x-ui.card class="sb-search-page-hero !max-w-none p-6 sm:p-7">
        <div class="space-y-5">
            <div class="space-y-3">
                <div class="sb-page-kicker">Search Results</div>
                <x-ui.heading level="h1" size="xl" class="sb-page-title">{{ $this->viewData['queryHeadline'] }}</x-ui.heading>
                <x-ui.text class="sb-page-copy max-w-3xl text-base">
                    {{ $this->viewData['queryCopy'] }}
                </x-ui.text>
            </div>

            <x-ui.field class="max-w-3xl">
                <x-ui.label>Query</x-ui.label>
                <x-ui.input
                    wire:model.live.debounce.300ms="query"
                    name="search_query"
                    placeholder="Search titles, people, and themes"
                    left-icon="magnifying-glass"
                    clearable
                    class="sb-search-page-input"
                />
            </x-ui.field>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="outline" color="neutral">{{ number_format($this->viewData['titleResultsCount']) }} titles</x-ui.badge>
                <x-ui.badge variant="outline" color="slate">{{ number_format($this->viewData['peopleCount']) }} people</x-ui.badge>
                <x-ui.badge variant="outline" color="amber">{{ number_format($this->viewData['interestCategoryCount']) }} themes</x-ui.badge>
                @if ($this->viewData['activeFilterCount'] > 0)
                    <x-ui.badge variant="outline" color="amber">{{ $this->viewData['activeFilterCount'] }} title filters active</x-ui.badge>
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="sb-filter-shell !max-w-none rounded-[1.6rem] p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="sb-page-kicker">Title Filters</div>
                <x-ui.heading level="h2" size="lg">Refine title matches</x-ui.heading>
            </div>

            @if ($this->viewData['activeFilterCount'] > 0)
                <x-ui.button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearTitleFilters">
                    Clear
                </x-ui.button>
            @endif
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.field>
                <x-ui.label>Type</x-ui.label>
                <x-ui.combobox wire:model.live="type" class="sb-filter-control w-full" placeholder="All types" clearable>
                    @foreach ($this->viewData['filterOptions']['titleTypes'] as $typeOption)
                        <x-ui.combobox.option value="{{ $typeOption->value }}" :icon="$typeOption->icon()">
                            {{ $typeOption->label() }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Genre</x-ui.label>
                <x-ui.combobox wire:model.live="genre" class="sb-filter-control w-full" placeholder="All genres" clearable>
                    @foreach ($this->viewData['filterOptions']['genres'] as $genreOption)
                        <x-ui.combobox.option value="{{ $genreOption->slug }}" icon="tag">
                            {{ $genreOption->name }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Theme</x-ui.label>
                <x-ui.combobox wire:model.live="theme" class="sb-filter-control w-full" placeholder="All themes" clearable>
                    @foreach ($this->viewData['filterOptions']['interestCategories'] as $interestCategoryOption)
                        <x-ui.combobox.option value="{{ $interestCategoryOption->slug }}" icon="squares-2x2">
                            {{ $interestCategoryOption->name }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>From year</x-ui.label>
                <x-ui.combobox wire:model.live="yearFrom" class="sb-filter-control w-full" placeholder="Any year" clearable>
                    @foreach ($this->viewData['filterOptions']['years'] as $yearOption)
                        <x-ui.combobox.option value="{{ $yearOption }}" icon="calendar-days">
                            {{ $yearOption }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>To year</x-ui.label>
                <x-ui.combobox wire:model.live="yearTo" class="sb-filter-control w-full" placeholder="Any year" clearable>
                    @foreach ($this->viewData['filterOptions']['years'] as $yearOption)
                        <x-ui.combobox.option value="{{ $yearOption }}" icon="calendar-days">
                            {{ $yearOption }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Sort</x-ui.label>
                <x-ui.combobox wire:model.live="sort" class="sb-filter-control w-full" placeholder="Sort titles">
                    @foreach ($this->viewData['filterOptions']['sortOptions'] as $sortOption)
                        <x-ui.combobox.option value="{{ $sortOption['value'] }}" icon="bars-arrow-down">
                            {{ $sortOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Minimum rating</x-ui.label>
                <x-ui.combobox wire:model.live="ratingMin" class="sb-filter-control w-full" placeholder="Any score" clearable>
                    @foreach (range(10, 1) as $ratingFloor)
                        <x-ui.combobox.option value="{{ $ratingFloor }}" icon="star">
                            {{ $ratingFloor }}+
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Votes</x-ui.label>
                <x-ui.combobox wire:model.live="votesMin" class="sb-filter-control w-full" placeholder="Any volume" clearable>
                    @foreach ($this->viewData['filterOptions']['voteThresholdOptions'] as $voteOption)
                        <x-ui.combobox.option value="{{ $voteOption['value'] }}" icon="users">
                            {{ $voteOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Runtime</x-ui.label>
                <x-ui.combobox wire:model.live="runtime" class="sb-filter-control w-full" placeholder="Any runtime" clearable>
                    @foreach ($this->viewData['filterOptions']['runtimeOptions'] as $runtimeOption)
                        <x-ui.combobox.option value="{{ $runtimeOption['value'] }}" icon="clock">
                            {{ $runtimeOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Language</x-ui.label>
                <x-ui.combobox wire:model.live="language" class="sb-filter-control w-full" placeholder="Any language" clearable>
                    @foreach ($this->viewData['filterOptions']['languages'] as $languageOption)
                        <x-ui.combobox.option value="{{ $languageOption['value'] }}" icon="language">
                            {{ $languageOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Country</x-ui.label>
                <x-ui.combobox wire:model.live="country" class="sb-filter-control w-full" placeholder="Any country" clearable>
                    @foreach ($this->viewData['filterOptions']['countries'] as $countryOption)
                        <x-ui.combobox.option value="{{ $countryOption['value'] }}" icon="globe-alt">
                            {{ $countryOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Series status</x-ui.label>
                <x-ui.combobox wire:model.live="status" class="sb-filter-control w-full" placeholder="Any status" clearable>
                    @foreach ($this->viewData['filterOptions']['statusOptions'] as $statusOption)
                        <x-ui.combobox.option value="{{ $statusOption['value'] }}" icon="tv">
                            {{ $statusOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div wire:loading.delay.attr="data-loading" wire:target="{{ $this->viewData['searchLoadingTargets'] }}" class="space-y-6">
        <div class="not-data-loading:hidden">
            <x-ui.card class="!max-w-none rounded-[1.6rem] p-5">
                <div class="flex items-center gap-3">
                    <x-ui.icon name="magnifying-glass" class="size-5 text-neutral-400 dark:text-neutral-500" />
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        Refreshing title and people matches.
                    </x-ui.text>
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6 in-data-loading:hidden">
        @if ($this->viewData['topMatch']['record'])
            <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="sb-page-kicker">Top Match</div>
                            <x-ui.heading level="h2" size="lg">
                                {{ $this->viewData['topMatch']['type'] === 'title' ? 'Best title match' : 'Best people match' }}
                            </x-ui.heading>
                        </div>

                        <x-ui.badge variant="outline" :color="$this->viewData['topMatch']['type'] === 'title' ? 'amber' : 'slate'">
                            {{ $this->viewData['topMatch']['type'] === 'title' ? 'Title' : 'Person' }}
                        </x-ui.badge>
                    </div>

                    @if ($this->viewData['topMatch']['type'] === 'title')
                        <div class="grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-[1.2rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                @if ($this->viewData['topMatch']['record']->preferredPoster())
                                    <img
                                        src="{{ $this->viewData['topMatch']['record']->preferredPoster()->url }}"
                                        alt="{{ $this->viewData['topMatch']['record']->preferredPoster()->alt_text ?: $this->viewData['topMatch']['record']->name }}"
                                        class="aspect-[2/3] w-full object-cover"
                                    >
                                @else
                                    <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="film" class="size-10" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <x-ui.heading level="h3" size="lg">
                                    <a href="{{ route('public.titles.show', $this->viewData['topMatch']['record']) }}" class="hover:opacity-80">
                                        {{ $this->viewData['topMatch']['record']->name }}
                                    </a>
                                </x-ui.heading>

                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge variant="outline" icon="{{ $this->viewData['topMatch']['record']->typeIcon() }}">{{ $this->viewData['topMatch']['record']->typeLabel() }}</x-ui.badge>
                                    @if ($this->viewData['topMatch']['record']->release_year)
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $this->viewData['topMatch']['record']->release_year }}</x-ui.badge>
                                    @endif
                                    @if ($this->viewData['topMatch']['record']->displayAverageRating())
                                        <x-ui.badge color="amber" icon="star">{{ number_format($this->viewData['topMatch']['record']->displayAverageRating(), 1) }}</x-ui.badge>
                                    @endif
                                </div>

                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $this->viewData['topMatch']['record']->summaryText() ?: 'A leading catalog match surfaced from the imported title index.' }}
                                </x-ui.text>

                                <x-ui.button.light-action :href="route('public.titles.show', $this->viewData['topMatch']['record'])" icon="film">
                                    View title
                                </x-ui.button.light-action>
                            </div>
                        </div>
                    @else
                        <div class="grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
                            <x-ui.avatar
                                :src="$this->viewData['topMatch']['record']->preferredHeadshot()?->url"
                                :alt="$this->viewData['topMatch']['record']->preferredHeadshot()?->alt_text ?: $this->viewData['topMatch']['record']->name"
                                :name="$this->viewData['topMatch']['record']->name"
                                color="auto"
                                class="!h-40 !w-full rounded-[1.2rem] border border-black/5 shadow-sm dark:border-white/10"
                            />

                            <div class="space-y-3">
                                <x-ui.heading level="h3" size="lg">
                                    <a href="{{ route('public.people.show', $this->viewData['topMatch']['record']) }}" class="hover:opacity-80">
                                        {{ $this->viewData['topMatch']['record']->name }}
                                    </a>
                                </x-ui.heading>

                                <div class="flex flex-wrap gap-2">
                                    @foreach ($this->viewData['topMatch']['record']->professionLabels() as $professionLabel)
                                        <x-ui.badge variant="outline" color="slate" icon="briefcase">{{ $professionLabel }}</x-ui.badge>
                                    @endforeach
                                    @if ($this->viewData['topMatch']['record']->nationality)
                                        <x-ui.badge variant="outline" color="neutral" icon="globe-alt">{{ $this->viewData['topMatch']['record']->nationality }}</x-ui.badge>
                                    @endif
                                </div>

                                <div data-slot="search-top-match-person-metrics" class="flex flex-wrap gap-2">
                                    @if ($this->viewData['topMatch']['popularityRankLabel'] ?? null)
                                        <x-ui.badge variant="outline" color="amber" icon="fire">
                                            {{ $this->viewData['topMatch']['popularityRankLabel'] }}
                                        </x-ui.badge>
                                    @endif

                                    @if ($this->viewData['topMatch']['awardNominationsLabel'] ?? null)
                                        <x-ui.badge variant="outline" color="slate" icon="trophy">
                                            {{ $this->viewData['topMatch']['awardNominationsLabel'] }}
                                        </x-ui.badge>
                                    @endif

                                    <x-ui.badge variant="outline" color="neutral" icon="film">
                                        {{ $this->viewData['topMatch']['record']->creditsBadgeLabel() }}
                                    </x-ui.badge>
                                </div>

                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $this->viewData['topMatch']['record']->summaryText() ?: 'A leading people match surfaced from the imported catalog.' }}
                                </x-ui.text>

                                <x-ui.link :href="route('public.people.show', $this->viewData['topMatch']['record'])" variant="ghost" iconAfter="arrow-right">
                                    View profile
                                </x-ui.link>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @endif

        @if ($this->viewData['titles']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">Titles</div>
                        <x-ui.heading level="h2" size="lg">Title matches</x-ui.heading>
                    </div>
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($this->viewData['titleResultsCount']) }} results</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->viewData['titles'] as $title)
                        <x-catalog.title-card :title="$title" />
                    @endforeach
                </div>

                <div>
                    {{ $this->viewData['titles']->links() }}
                </div>
            </section>
        @endif

        @if ($this->viewData['people']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">People</div>
                        <x-ui.heading level="h2" size="lg">People matches</x-ui.heading>
                    </div>
                    <x-ui.badge variant="outline" color="slate">{{ number_format($this->viewData['peopleCount']) }} results</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->viewData['people'] as $person)
                        <x-catalog.person-card :person="$person" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($this->viewData['interestCategories']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">Themes</div>
                        <x-ui.heading level="h2" size="lg">Theme matches</x-ui.heading>
                    </div>
                    <x-ui.badge variant="outline" color="amber">{{ number_format($this->viewData['interestCategoryCount']) }} results</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->viewData['interestCategories'] as $interestCategory)
                        <x-catalog.interest-category-card :interest-category="$interestCategory">
                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">
                                Discovery lane
                            </x-ui.badge>
                        </x-catalog.interest-category-card>
                    @endforeach
                </div>
            </section>
        @endif

        @unless ($this->viewData['hasAnyResults'])
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="magnifying-glass" class="size-8 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No matches yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Try a broader title or person query, or clear some title filters.
                </x-ui.text>
            </x-ui.empty>
        @endunless
        </div>
    </div>
</div>
@endisland
</div>
