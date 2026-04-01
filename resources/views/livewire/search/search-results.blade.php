@php
    $titleTypeIcons = [
        'movie' => 'film',
        'series' => 'tv',
        'mini-series' => 'tv',
        'documentary' => 'camera',
        'short' => 'film',
        'special' => 'sparkles',
        'episode' => 'rectangle-stack',
    ];

    $sortIcons = [
        'popular' => 'fire',
        'trending' => 'bolt',
        'rating' => 'star',
        'latest' => 'clock',
        'year' => 'calendar-days',
        'name' => 'bars-arrow-down',
    ];

    $searchLoadingTargets = 'query,type,genre,yearFrom,yearTo,ratingMin,ratingMax,votesMin,language,country,runtime,status,sort';
@endphp

<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="space-y-4">
            <div class="space-y-3">
                <x-ui.heading level="h1" size="xl">Search</x-ui.heading>
                <x-ui.text class="max-w-3xl text-base text-neutral-600 dark:text-neutral-300">
                    Search titles, people, and public curation with database-backed filters for year ranges, ratings, languages, runtime, and TV status.
                </x-ui.text>
            </div>

            <div class="grid gap-4 xl:grid-cols-4">
                <x-ui.field class="xl:col-span-2">
                    <x-ui.label>Keyword</x-ui.label>
                    <x-ui.input
                        wire:model.live.debounce.300ms="query"
                        name="search_query"
                        placeholder="Search titles, translated titles, people, or lists"
                        left-icon="magnifying-glass"
                        clearable
                    />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Type</x-ui.label>
                    <x-ui.combobox wire:model.live="type" class="w-full" placeholder="All types" clearable>
                        @foreach ($filterOptions['titleTypes'] as $typeOption)
                            <x-ui.combobox.option
                                wire:key="search-type-{{ $typeOption->value }}"
                                value="{{ $typeOption->value }}"
                                :icon="$titleTypeIcons[$typeOption->value] ?? 'film'"
                            >
                                {{ str($typeOption->value)->headline() }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Genre</x-ui.label>
                    <x-ui.combobox wire:model.live="genre" class="w-full" placeholder="All genres" clearable>
                        @foreach ($filterOptions['genres'] as $genreOption)
                            <x-ui.combobox.option wire:key="search-genre-{{ $genreOption->id }}" value="{{ $genreOption->slug }}" icon="tag">
                                {{ $genreOption->name }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Year from</x-ui.label>
                    <x-ui.combobox wire:model.live="yearFrom" class="w-full" placeholder="Any year" clearable>
                        @foreach ($filterOptions['years'] as $yearOption)
                            <x-ui.combobox.option wire:key="search-year-from-{{ $yearOption }}" value="{{ $yearOption }}" icon="calendar-days">
                                {{ $yearOption }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Year to</x-ui.label>
                    <x-ui.combobox wire:model.live="yearTo" class="w-full" placeholder="Any year" clearable>
                        @foreach ($filterOptions['years'] as $yearOption)
                            <x-ui.combobox.option wire:key="search-year-to-{{ $yearOption }}" value="{{ $yearOption }}" icon="calendar-days">
                                {{ $yearOption }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Rating from</x-ui.label>
                    <x-ui.input wire:model.live.debounce.300ms="ratingMin" name="rating_min" type="number" min="0" max="10" step="0.1" placeholder="0.0" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Rating to</x-ui.label>
                    <x-ui.input wire:model.live.debounce.300ms="ratingMax" name="rating_max" type="number" min="0" max="10" step="0.1" placeholder="10.0" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Votes</x-ui.label>
                    <x-ui.combobox wire:model.live="votesMin" class="w-full" placeholder="Any volume" clearable>
                        @foreach ($filterOptions['voteThresholdOptions'] as $voteThresholdOption)
                            <x-ui.combobox.option wire:key="search-votes-{{ $voteThresholdOption['value'] }}" value="{{ $voteThresholdOption['value'] }}" icon="users">
                                {{ $voteThresholdOption['label'] }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Language</x-ui.label>
                    <x-ui.combobox wire:model.live="language" class="w-full" placeholder="Any language" clearable>
                        @foreach ($filterOptions['languages'] as $languageOption)
                            <x-ui.combobox.option wire:key="search-language-{{ $languageOption['value'] }}" value="{{ $languageOption['value'] }}" icon="language">
                                {{ $languageOption['label'] }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Country</x-ui.label>
                    <x-ui.combobox wire:model.live="country" class="w-full" placeholder="Any country" clearable>
                        @foreach ($filterOptions['countries'] as $countryOption)
                            <x-ui.combobox.option wire:key="search-country-{{ $countryOption['value'] }}" value="{{ $countryOption['value'] }}" icon="globe-alt">
                                {{ $countryOption['label'] }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Runtime</x-ui.label>
                    <x-ui.combobox wire:model.live="runtime" class="w-full" placeholder="Any runtime" clearable>
                        @foreach ($filterOptions['runtimeOptions'] as $runtimeOption)
                            <x-ui.combobox.option wire:key="search-runtime-{{ $runtimeOption['value'] }}" value="{{ $runtimeOption['value'] }}" icon="clock">
                                {{ $runtimeOption['label'] }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>TV status</x-ui.label>
                        <x-ui.combobox wire:model.live="status" class="w-full" placeholder="Any status" clearable>
                            @foreach ($filterOptions['statusOptions'] as $statusOption)
                                <x-ui.combobox.option wire:key="search-status-{{ $statusOption['value'] }}" value="{{ $statusOption['value'] }}" icon="tv">
                                    {{ $statusOption['label'] }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                    </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Sort</x-ui.label>
                    <x-ui.combobox wire:model.live="sort" class="w-full" placeholder="Sort titles">
                        @foreach ($filterOptions['sortOptions'] as $sortOption)
                            <x-ui.combobox.option wire:key="search-sort-{{ $sortOption['value'] }}" value="{{ $sortOption['value'] }}" :icon="$sortIcons[$sortOption['value']] ?? 'bars-arrow-down'">
                                {{ $sortOption['label'] }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($titleResultsCount) }} titles</x-ui.badge>
                    @if ($showGroupedMatches)
                        <x-ui.badge variant="outline" color="slate">{{ number_format($peopleCount) }} people</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate">{{ number_format($listsCount) }} lists</x-ui.badge>
                    @endif
                </div>

                @if ($activeFilterCount > 0)
                    <x-ui.button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearTitleFilters">
                        Clear {{ $activeFilterCount }} title filters
                    </x-ui.button>
                @endif
            </div>
        </div>
    </x-ui.card>

    <div wire:loading.delay wire:target="{{ $searchLoadingTargets }}" class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="flex items-center gap-3">
                <x-ui.icon name="magnifying-glass" class="size-5 text-neutral-400 dark:text-neutral-500" />
                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                    Refreshing grouped search results and title matches.
                </x-ui.text>
            </div>
        </x-ui.card>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (range(1, 6) as $index)
                <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="search-results-skeleton-{{ $index }}">
                    <div class="space-y-4">
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                        <x-ui.skeleton.text class="w-1/3" />
                        <x-ui.skeleton.text class="w-4/5" />
                        <x-ui.skeleton.text class="w-2/3" />
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>

    <div wire:loading.remove wire:target="{{ $searchLoadingTargets }}" class="space-y-4">
        @if ($showGroupedMatches && ! $hasAnyResults)
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="magnifying-glass" class="size-8 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No search results match the current query and filters.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Broaden the keyword, remove a few title filters, or try another language, country, or year range.
                </x-ui.text>
            </x-ui.empty>
        @endif

        @if ($showGroupedMatches && $people->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <x-ui.heading level="h2" size="lg">People</x-ui.heading>
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($peopleCount) }} matches</x-ui.badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($people as $person)
                        <x-catalog.person-card :person="$person" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($showGroupedMatches && $lists->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <x-ui.heading level="h2" size="lg">Lists</x-ui.heading>
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($listsCount) }} public matches</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($lists as $list)
                        <x-ui.card class="!max-w-none h-full">
                            <div class="flex h-full flex-col gap-3">
                                <div class="space-y-2">
                                    <x-ui.heading level="h3" size="md">
                                        <a href="{{ route('public.lists.show', [$list->user, $list]) }}" class="hover:opacity-80">
                                            {{ $list->name }}
                                        </a>
                                    </x-ui.heading>
                                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $list->description ?: 'A public Screenbase list.' }}
                                    </x-ui.text>
                                </div>

                                <div class="mt-auto flex items-center justify-between gap-3">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.badge variant="outline" color="neutral">{{ number_format($list->published_items_count) }} titles</x-ui.badge>
                                        <x-ui.badge variant="outline" color="slate">{{ '@'.$list->user->username }}</x-ui.badge>
                                    </div>

                                    <x-ui.link :href="route('public.lists.show', [$list->user, $list])" variant="ghost" iconAfter="arrow-right">
                                        View list
                                    </x-ui.link>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <x-ui.heading level="h2" size="lg">Title Results</x-ui.heading>
                <x-ui.badge variant="outline" color="slate">{{ number_format($titleResultsCount) }} matches</x-ui.badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($titles as $title)
                    <x-catalog.title-card :title="$title" />
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No titles match the current title filters.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Adjust the type, year range, language, country, rating, or runtime filters to widen title matches.
                            </x-ui.text>
                        </x-ui.empty>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $titles->links() }}
            </div>
        </section>
    </div>
</div>
