<div>
@island(name: 'search-results-page')
    @php
        $view = $this->viewData;
    @endphp

<div class="space-y-6" data-slot="search-results-island">
    <x-ui.card class="sb-search-page-hero !max-w-none p-6 sm:p-7">
        <div class="space-y-5">
            <div class="space-y-3">
                <div class="sb-page-kicker">Search Results</div>
                <x-ui.heading level="h1" size="xl" class="sb-page-title">{{ $view['queryHeadline'] }}</x-ui.heading>
                <x-ui.text class="sb-page-copy max-w-3xl text-base">
                    {{ $view['queryCopy'] }}
                </x-ui.text>
            </div>

            <x-ui.field class="max-w-3xl">
                <x-ui.label>Query</x-ui.label>
                <x-ui.input
                    wire:model.live.debounce.300ms="query"
                    name="search_query"
                    placeholder="Search titles and people"
                    left-icon="magnifying-glass"
                    clearable
                    class="sb-search-page-input"
                />
            </x-ui.field>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="outline" color="neutral">{{ number_format($view['titleResultsCount']) }} titles</x-ui.badge>
                <x-ui.badge variant="outline" color="slate">{{ number_format($view['peopleCount']) }} people</x-ui.badge>
                @if ($view['activeFilterCount'] > 0)
                    <x-ui.badge variant="outline" color="amber">{{ $view['activeFilterCount'] }} title filters active</x-ui.badge>
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

            @if ($view['activeFilterCount'] > 0)
                <x-ui.button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearTitleFilters">
                    Clear
                </x-ui.button>
            @endif
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.field>
                <x-ui.label>Type</x-ui.label>
                <x-ui.combobox wire:model.live="type" class="sb-filter-control w-full" placeholder="All types" clearable>
                    @foreach ($view['filterOptions']['titleTypes'] as $typeOption)
                        <x-ui.combobox.option value="{{ $typeOption->value }}" :icon="$typeOption->icon()">
                            {{ $typeOption->label() }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Genre</x-ui.label>
                <x-ui.combobox wire:model.live="genre" class="sb-filter-control w-full" placeholder="All genres" clearable>
                    @foreach ($view['filterOptions']['genres'] as $genreOption)
                        <x-ui.combobox.option value="{{ $genreOption->slug }}" icon="tag">
                            {{ $genreOption->name }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>From year</x-ui.label>
                <x-ui.combobox wire:model.live="yearFrom" class="sb-filter-control w-full" placeholder="Any year" clearable>
                    @foreach ($view['filterOptions']['years'] as $yearOption)
                        <x-ui.combobox.option value="{{ $yearOption }}" icon="calendar-days">
                            {{ $yearOption }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>To year</x-ui.label>
                <x-ui.combobox wire:model.live="yearTo" class="sb-filter-control w-full" placeholder="Any year" clearable>
                    @foreach ($view['filterOptions']['years'] as $yearOption)
                        <x-ui.combobox.option value="{{ $yearOption }}" icon="calendar-days">
                            {{ $yearOption }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Sort</x-ui.label>
                <x-ui.combobox wire:model.live="sort" class="sb-filter-control w-full" placeholder="Sort titles">
                    @foreach ($view['filterOptions']['sortOptions'] as $sortOption)
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
                    @foreach ($view['filterOptions']['voteThresholdOptions'] as $voteOption)
                        <x-ui.combobox.option value="{{ $voteOption['value'] }}" icon="users">
                            {{ $voteOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Runtime</x-ui.label>
                <x-ui.combobox wire:model.live="runtime" class="sb-filter-control w-full" placeholder="Any runtime" clearable>
                    @foreach ($view['filterOptions']['runtimeOptions'] as $runtimeOption)
                        <x-ui.combobox.option value="{{ $runtimeOption['value'] }}" icon="clock">
                            {{ $runtimeOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Language</x-ui.label>
                <x-ui.combobox wire:model.live="language" class="sb-filter-control w-full" placeholder="Any language" clearable>
                    @foreach ($view['filterOptions']['languages'] as $languageOption)
                        <x-ui.combobox.option value="{{ $languageOption['value'] }}" icon="language">
                            {{ $languageOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Country</x-ui.label>
                <x-ui.combobox wire:model.live="country" class="sb-filter-control w-full" placeholder="Any country" clearable>
                    @foreach ($view['filterOptions']['countries'] as $countryOption)
                        <x-ui.combobox.option value="{{ $countryOption['value'] }}" icon="globe-alt">
                            {{ $countryOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Series status</x-ui.label>
                <x-ui.combobox wire:model.live="status" class="sb-filter-control w-full" placeholder="Any status" clearable>
                    @foreach ($view['filterOptions']['statusOptions'] as $statusOption)
                        <x-ui.combobox.option value="{{ $statusOption['value'] }}" icon="tv">
                            {{ $statusOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div wire:loading.delay wire:target="{{ $view['searchLoadingTargets'] }}">
        <x-ui.card class="!max-w-none rounded-[1.6rem] p-5">
            <div class="flex items-center gap-3">
                <x-ui.icon name="magnifying-glass" class="size-5 text-neutral-400 dark:text-neutral-500" />
                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                    Refreshing title and people matches.
                </x-ui.text>
            </div>
        </x-ui.card>
    </div>

    <div wire:loading.remove wire:target="{{ $view['searchLoadingTargets'] }}" class="space-y-6">
        @if ($view['topMatch']['record'])
            <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="sb-page-kicker">Top Match</div>
                            <x-ui.heading level="h2" size="lg">
                                {{ $view['topMatch']['type'] === 'title' ? 'Best title match' : 'Best people match' }}
                            </x-ui.heading>
                        </div>

                        <x-ui.badge variant="outline" :color="$view['topMatch']['type'] === 'title' ? 'amber' : 'slate'">
                            {{ $view['topMatch']['type'] === 'title' ? 'Title' : 'Person' }}
                        </x-ui.badge>
                    </div>

                    @if ($view['topMatch']['type'] === 'title')
                        <div class="grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-[1.2rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                @if ($view['topMatch']['record']->preferredPoster())
                                    <img
                                        src="{{ $view['topMatch']['record']->preferredPoster()->url }}"
                                        alt="{{ $view['topMatch']['record']->preferredPoster()->alt_text ?: $view['topMatch']['record']->name }}"
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
                                    <a href="{{ route('public.titles.show', $view['topMatch']['record']) }}" class="hover:opacity-80">
                                        {{ $view['topMatch']['record']->name }}
                                    </a>
                                </x-ui.heading>

                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge variant="outline" icon="{{ $view['topMatch']['record']->typeIcon() }}">{{ $view['topMatch']['record']->typeLabel() }}</x-ui.badge>
                                    @if ($view['topMatch']['record']->release_year)
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $view['topMatch']['record']->release_year }}</x-ui.badge>
                                    @endif
                                    @if ($view['topMatch']['record']->displayAverageRating())
                                        <x-ui.badge color="amber" icon="star">{{ number_format($view['topMatch']['record']->displayAverageRating(), 1) }}</x-ui.badge>
                                    @endif
                                </div>

                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $view['topMatch']['record']->summaryText() ?: 'A leading catalog match surfaced from the imported title index.' }}
                                </x-ui.text>

                                <x-ui.link :href="route('public.titles.show', $view['topMatch']['record'])" variant="ghost" iconAfter="arrow-right">
                                    View title
                                </x-ui.link>
                            </div>
                        </div>
                    @else
                        @php
                            $topMatchPopularityRankLabel = $view['topMatch']['record']->popularityRankBadgeLabel();
                            $topMatchAwardNominationsLabel = $view['topMatch']['record']->awardNominationsBadgeLabel();
                        @endphp

                        <div class="grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
                            <x-ui.avatar
                                :src="$view['topMatch']['record']->preferredHeadshot()?->url"
                                :alt="$view['topMatch']['record']->preferredHeadshot()?->alt_text ?: $view['topMatch']['record']->name"
                                :name="$view['topMatch']['record']->name"
                                color="auto"
                                class="!h-40 !w-full rounded-[1.2rem] border border-black/5 shadow-sm dark:border-white/10"
                            />

                            <div class="space-y-3">
                                <x-ui.heading level="h3" size="lg">
                                    <a href="{{ route('public.people.show', $view['topMatch']['record']) }}" class="hover:opacity-80">
                                        {{ $view['topMatch']['record']->name }}
                                    </a>
                                </x-ui.heading>

                                <div class="flex flex-wrap gap-2">
                                    @foreach ($view['topMatch']['record']->professionLabels() as $professionLabel)
                                        <x-ui.badge variant="outline" color="slate" icon="briefcase">{{ $professionLabel }}</x-ui.badge>
                                    @endforeach
                                    @if ($view['topMatch']['record']->nationality)
                                        <x-ui.badge variant="outline" color="neutral" icon="globe-alt">{{ $view['topMatch']['record']->nationality }}</x-ui.badge>
                                    @endif
                                </div>

                                <div data-slot="search-top-match-person-metrics" class="flex flex-wrap gap-2">
                                    @if ($topMatchPopularityRankLabel)
                                        <x-ui.badge variant="outline" color="amber" icon="fire">
                                            {{ $topMatchPopularityRankLabel }}
                                        </x-ui.badge>
                                    @endif

                                    @if ($topMatchAwardNominationsLabel)
                                        <x-ui.badge variant="outline" color="slate" icon="trophy">
                                            {{ $topMatchAwardNominationsLabel }}
                                        </x-ui.badge>
                                    @endif

                                    <x-ui.badge variant="outline" color="neutral" icon="film">
                                        {{ $view['topMatch']['record']->creditsBadgeLabel() }}
                                    </x-ui.badge>
                                </div>

                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $view['topMatch']['record']->summaryText() ?: 'A leading people match surfaced from the imported catalog.' }}
                                </x-ui.text>

                                <x-ui.link :href="route('public.people.show', $view['topMatch']['record'])" variant="ghost" iconAfter="arrow-right">
                                    View profile
                                </x-ui.link>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @endif

        @if ($view['titles']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">Titles</div>
                        <x-ui.heading level="h2" size="lg">Title matches</x-ui.heading>
                    </div>
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($view['titleResultsCount']) }} results</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($view['titles'] as $title)
                        <x-catalog.title-card :title="$title" />
                    @endforeach
                </div>

                <div>
                    {{ $view['titles']->links() }}
                </div>
            </section>
        @endif

        @if ($view['people']->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">People</div>
                        <x-ui.heading level="h2" size="lg">People matches</x-ui.heading>
                    </div>
                    <x-ui.badge variant="outline" color="slate">{{ number_format($view['peopleCount']) }} results</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($view['people'] as $person)
                        <x-catalog.person-card :person="$person" />
                    @endforeach
                </div>
            </section>
        @endif

        @unless ($view['hasAnyResults'])
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
@endisland
</div>
