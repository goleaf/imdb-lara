<div x-data="{ activeTab: @js($initialActiveTab) }" class="space-y-6">
    <x-ui.card class="sb-search-page-hero !max-w-none p-6 sm:p-7" data-slot="search-surface">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)] xl:items-end">
            <div class="space-y-5">
                <div class="space-y-3">
                    <div class="sb-page-kicker">Search Results</div>
                    <x-ui.heading level="h1" size="xl" class="sb-page-title">{{ $queryHeadline }}</x-ui.heading>
                    <x-ui.text class="sb-page-copy max-w-3xl text-base">
                        {{ $queryCopy }}
                    </x-ui.text>
                </div>

                <x-ui.field class="max-w-3xl">
                    <x-ui.label>Query</x-ui.label>
                    <x-ui.input
                        wire:model.live.debounce.300ms="query"
                        name="search_query"
                        placeholder="Search titles, people, and lists"
                        left-icon="magnifying-glass"
                        clearable
                        class="sb-search-page-input"
                    />
                </x-ui.field>

                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($titleResultsCount) }} titles</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate">{{ number_format($peopleCount) }} people</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($listsCount) }} lists</x-ui.badge>
                    @if ($activeFilterCount > 0)
                        <x-ui.badge variant="outline" color="amber">{{ $activeFilterCount }} title filters active</x-ui.badge>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                <div class="sb-search-stat">
                    <div class="sb-search-stat-label">Top lane</div>
                    <div class="sb-search-stat-value">Titles</div>
                    <div class="sb-search-stat-copy">Poster-led results stay primary, with people separated into their own tab.</div>
                </div>

                <div class="sb-search-stat">
                    <div class="sb-search-stat-label">People search</div>
                    <div class="sb-search-stat-value">{{ $showGroupedMatches ? number_format($peopleCount) : 'Ready' }}</div>
                    <div class="sb-search-stat-copy">
                        {{ $showGroupedMatches ? 'Portrait-first matches are ready to browse.' : 'People results appear after 2 characters.' }}
                    </div>
                </div>

                <div class="sb-search-stat">
                    <div class="sb-search-stat-label">List search</div>
                    <div class="sb-search-stat-value">{{ $showGroupedMatches ? number_format($listsCount) : 'Ready' }}</div>
                    <div class="sb-search-stat-copy">
                        {{ $showGroupedMatches ? 'Public curated lists surface in their own lane.' : 'Lists results appear after 2 characters.' }}
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div wire:loading.delay wire:target="{{ $searchLoadingTargets }}" class="space-y-4">
        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
            <div class="flex items-center gap-3">
                <x-ui.icon name="magnifying-glass" class="size-5 text-neutral-400 dark:text-neutral-500" />
                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                    Refreshing search lanes, top matches, and title filters.
                </x-ui.text>
            </div>
        </x-ui.card>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (range(1, 6) as $index)
                <div class="sb-search-result-card sb-search-result-card--title" wire:key="search-results-skeleton-{{ $index }}">
                    <div class="grid gap-4 sm:grid-cols-[5.5rem_minmax(0,1fr)]">
                        <x-ui.skeleton class="h-32 w-full rounded-[1.2rem]" />
                        <div class="space-y-3">
                            <x-ui.skeleton.text class="w-1/3" />
                            <x-ui.skeleton.text class="w-4/5" />
                            <x-ui.skeleton.text class="w-2/3" />
                            <x-ui.skeleton.text class="w-full" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div wire:loading.remove wire:target="{{ $searchLoadingTargets }}" class="space-y-6">
        @if ($showGroupedMatches && $topMatch)
            <section class="sb-results-shell sb-search-top-match sb-search-top-match--featured space-y-4 rounded-[1.7rem] p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="sb-page-kicker">Top Match</div>
                            <span class="sb-search-top-match-badge">Primary result</span>
                        </div>
                        <x-ui.heading level="h2" size="lg" class="sb-home-section-heading">
                            {{ $topMatchType === 'title' ? 'Best title match' : ($topMatchType === 'person' ? 'Best people match' : 'Best list match') }}
                        </x-ui.heading>
                    </div>

                    <x-ui.badge variant="outline" :color="$topMatchType === 'title' ? 'amber' : ($topMatchType === 'person' ? 'slate' : 'neutral')">
                        {{ $topMatchType === 'title' ? 'Title match' : ($topMatchType === 'person' ? 'People match' : 'List match') }}
                    </x-ui.badge>
                </div>

                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.16fr)_minmax(18rem,0.84fr)]">
                    @if ($topMatchType === 'title')
                        <article class="sb-search-spotlight-card sb-search-spotlight-card--title">
                            <div class="grid gap-4 sm:grid-cols-[8.5rem_minmax(0,1fr)] sm:items-start">
                                <a href="{{ route('public.titles.show', $topMatch) }}" class="sb-search-spotlight-media sb-search-spotlight-media--title">
                                    @if ($topMatch->preferredPoster())
                                        <img
                                            src="{{ $topMatch->preferredPoster()->url }}"
                                            alt="{{ $topMatch->preferredPoster()->alt_text ?: $topMatch->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full min-h-48 w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                            <x-ui.icon name="film" class="size-10" />
                                        </div>
                                    @endif
                                </a>

                                <div class="flex h-full flex-col gap-4">
                                    <div class="space-y-2">
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                                {{ $topMatch->typeLabel() }}
                                            </span>
                                            @if ($topMatch->release_year)
                                                <span class="sb-search-chip sb-search-chip--tight">{{ $topMatch->release_year }}</span>
                                            @endif
                                            @if ($topMatch->displayAverageRating())
                                                <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                                    <x-ui.icon name="star" class="size-3" />
                                                    {{ number_format($topMatch->displayAverageRating(), 1) }}
                                                </span>
                                            @endif
                                        </div>

                                        <x-ui.heading level="h3" size="lg" class="sb-search-spotlight-title">
                                            <a href="{{ route('public.titles.show', $topMatch) }}" class="hover:opacity-80">
                                                {{ $topMatch->name }}
                                            </a>
                                        </x-ui.heading>

                                        <x-ui.text class="sb-search-spotlight-copy">
                                            {{ filled($topMatch->plot_outline) ? str($topMatch->plot_outline)->limit(220) : 'A leading search result surfaced from Screenbase title discovery.' }}
                                        </x-ui.text>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-sm text-[#a89d8d]">
                                        @if ($topMatch->previewGenres()->isNotEmpty())
                                            <span>{{ $topMatch->previewGenres()->pluck('name')->join(' · ') }}</span>
                                        @endif
                                        @if ($topMatch->runtime_minutes)
                                            <span>{{ $topMatch->runtime_minutes }} min</span>
                                        @endif
                                        @if (filled($topMatch->origin_country))
                                            <span>{{ $topMatch->origin_country }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 text-sm text-[#9f9486]">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="sb-search-result-stat">{{ number_format($topMatch->displayReviewCount()) }} reviews</span>
                                            @if ($topMatch->displayRatingCount() > 0)
                                                <span class="sb-search-result-stat">{{ number_format($topMatch->displayRatingCount()) }} votes</span>
                                            @endif
                                        </div>

                                        <x-ui.link :href="route('public.titles.show', $topMatch)" variant="ghost" iconAfter="arrow-right">
                                            View title
                                        </x-ui.link>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @elseif ($topMatchType === 'person')
                        <article class="sb-search-spotlight-card sb-search-spotlight-card--person">
                            <div class="grid gap-4 sm:grid-cols-[7.5rem_minmax(0,1fr)] sm:items-start">
                                <a href="{{ route('public.people.show', $topMatch) }}" class="sb-search-spotlight-media sb-search-spotlight-media--person">
                                    @if ($topMatch->preferredHeadshot())
                                        <img
                                            src="{{ $topMatch->preferredHeadshot()->url }}"
                                            alt="{{ $topMatch->preferredHeadshot()->alt_text ?: $topMatch->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full min-h-44 w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                            <x-ui.icon name="user" class="size-10" />
                                        </div>
                                    @endif
                                </a>

                                <div class="flex h-full flex-col gap-4">
                                    <div class="space-y-2">
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">
                                                {{ $topMatch->primaryProfessionLabel() }}
                                            </span>
                                            @if (filled($topMatch->nationality))
                                                <span class="sb-search-chip sb-search-chip--tight">{{ $topMatch->nationality }}</span>
                                            @endif
                                        </div>

                                        <x-ui.heading level="h3" size="lg" class="sb-search-spotlight-title">
                                            <a href="{{ route('public.people.show', $topMatch) }}" class="hover:opacity-80">
                                                {{ $topMatch->name }}
                                            </a>
                                        </x-ui.heading>

                                        <x-ui.text class="sb-search-spotlight-copy">
                                            {{ filled($topMatch->summaryText()) ? str($topMatch->summaryText())->limit(220) : 'A leading people match surfaced from Screenbase profile search.' }}
                                        </x-ui.text>
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 text-sm text-[#9f9486]">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="sb-search-result-stat">{{ number_format((int) $topMatch->credits_count) }} credits</span>
                                            <span class="sb-search-result-stat">{{ number_format((int) $topMatch->award_nominations_count) }} award mentions</span>
                                        </div>

                                        <x-ui.link :href="route('public.people.show', $topMatch)" variant="ghost" iconAfter="arrow-right">
                                            View profile
                                        </x-ui.link>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @else
                        <article class="sb-search-spotlight-card sb-search-spotlight-card--list">
                            <div class="grid gap-4 sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-start">
                                <a href="{{ route('public.lists.show', [$topMatch->user, $topMatch]) }}" class="sb-search-spotlight-media sb-search-spotlight-media--title">
                                    @if ($topMatch->previewPoster())
                                        <img
                                            src="{{ $topMatch->previewPoster()->url }}"
                                            alt="{{ $topMatch->previewPoster()->alt_text ?: $topMatch->previewTitle()?->name ?: $topMatch->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full min-h-44 w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                            <x-ui.icon name="queue-list" class="size-10" />
                                        </div>
                                    @endif
                                </a>

                                <div class="flex h-full flex-col gap-4">
                                    <div class="space-y-2">
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--lists sb-search-chip--tight">
                                                Public list
                                            </span>
                                            <span class="sb-search-chip sb-search-chip--tight">
                                                {{ number_format($topMatch->published_items_count) }} titles
                                            </span>
                                        </div>

                                        <x-ui.heading level="h3" size="lg" class="sb-search-spotlight-title">
                                            <a href="{{ route('public.lists.show', [$topMatch->user, $topMatch]) }}" class="hover:opacity-80">
                                                {{ $topMatch->name }}
                                            </a>
                                        </x-ui.heading>

                                        <x-ui.text class="sb-search-spotlight-copy">
                                            {{ filled($topMatch->description) ? str($topMatch->description)->limit(220) : 'A public Screenbase list surfaced from curator search.' }}
                                        </x-ui.text>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-sm text-[#a89d8d]">
                                        <span>{{ '@'.$topMatch->user->username }}</span>
                                        <span>Updated {{ $topMatch->updated_at?->diffForHumans() }}</span>
                                        @if ($topMatch->previewTitle())
                                            <span>Preview: {{ $topMatch->previewTitle()?->name }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 text-sm text-[#9f9486]">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="sb-search-result-stat">{{ number_format($topMatch->published_items_count) }} published titles</span>
                                        </div>

                                        <x-ui.link :href="route('public.lists.show', [$topMatch->user, $topMatch])" variant="ghost" iconAfter="arrow-right">
                                            View list
                                        </x-ui.link>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endif

                    <aside class="sb-search-spotlight-side">
                        @if ($secondaryMatch && $secondaryMatchType === 'title')
                            <div class="sb-search-spotlight-mini">
                                <div class="sb-search-spotlight-mini-kicker">Also matching title</div>
                                <a href="{{ route('public.titles.show', $secondaryMatch) }}" class="sb-search-spotlight-mini-card">
                                    <div class="sb-search-spotlight-mini-media">
                                        @if ($secondaryMatch->preferredPoster())
                                            <img
                                                src="{{ $secondaryMatch->preferredPoster()->url }}"
                                                alt="{{ $secondaryMatch->preferredPoster()->alt_text ?: $secondaryMatch->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                                <x-ui.icon name="film" class="size-5" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 space-y-2">
                                        <div class="truncate text-sm font-semibold text-[#f4eee5]">{{ $secondaryMatch->name }}</div>
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--tight sb-search-chip--accent">
                                                {{ $secondaryMatch->typeLabel() }}
                                            </span>
                                            @if ($secondaryMatch->release_year)
                                                <span class="sb-search-chip sb-search-chip--tight">{{ $secondaryMatch->release_year }}</span>
                                            @endif
                                            @if ($secondaryMatch->displayAverageRating())
                                                <span class="sb-search-chip sb-search-chip--tight sb-search-chip--accent">
                                                    {{ number_format($secondaryMatch->displayAverageRating(), 1) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @elseif ($secondaryMatch && $secondaryMatchType === 'person')
                            <div class="sb-search-spotlight-mini">
                                <div class="sb-search-spotlight-mini-kicker">Also matching person</div>
                                <a href="{{ route('public.people.show', $secondaryMatch) }}" class="sb-search-spotlight-mini-card">
                                    <div class="sb-search-spotlight-mini-media sb-search-spotlight-mini-media--person">
                                        @if ($secondaryMatch->preferredHeadshot())
                                            <img
                                                src="{{ $secondaryMatch->preferredHeadshot()->url }}"
                                                alt="{{ $secondaryMatch->preferredHeadshot()->alt_text ?: $secondaryMatch->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                                <x-ui.icon name="user" class="size-5" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 space-y-2">
                                        <div class="truncate text-sm font-semibold text-[#f4eee5]">{{ $secondaryMatch->name }}</div>
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">{{ $secondaryMatch->primaryProfessionLabel() }}</span>
                                            @if (filled($secondaryMatch->nationality))
                                                <span class="sb-search-chip sb-search-chip--tight">{{ $secondaryMatch->nationality }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @elseif ($secondaryMatch && $secondaryMatchType === 'list')
                            <div class="sb-search-spotlight-mini">
                                <div class="sb-search-spotlight-mini-kicker">Also matching list</div>
                                <a href="{{ route('public.lists.show', [$secondaryMatch->user, $secondaryMatch]) }}" class="sb-search-spotlight-mini-card">
                                    <div class="sb-search-spotlight-mini-media">
                                        @if ($secondaryMatch->previewPoster())
                                            <img
                                                src="{{ $secondaryMatch->previewPoster()->url }}"
                                                alt="{{ $secondaryMatch->previewPoster()->alt_text ?: $secondaryMatch->previewTitle()?->name ?: $secondaryMatch->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                                <x-ui.icon name="queue-list" class="size-5" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 space-y-2">
                                        <div class="truncate text-sm font-semibold text-[#f4eee5]">{{ $secondaryMatch->name }}</div>
                                        <div class="sb-search-meta">
                                            <span class="sb-search-chip sb-search-chip--lists sb-search-chip--tight">
                                                {{ number_format($secondaryMatch->published_items_count) }} titles
                                            </span>
                                            <span class="sb-search-meta-copy truncate">{{ '@'.$secondaryMatch->user->username }}</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif

                        <div class="sb-search-spotlight-note">
                            <div class="sb-search-spotlight-mini-kicker">Search structure</div>
                            <p class="text-sm leading-6 text-[#b3a898]">
                                Titles stay poster-first and filterable. People stay portrait-first. Public lists sit in their own lane so curator search stays useful without polluting title results.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        @endif

        <section class="sb-results-shell space-y-5 rounded-[1.7rem] p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg" class="sb-home-section-heading">Search Lanes</x-ui.heading>
                    <x-ui.text class="text-sm text-[#a89d8d] dark:text-[#a89d8d]">
                        Switch between title discovery, people matches, and public lists without losing the query context.
                    </x-ui.text>
                </div>

                <div class="sb-search-tabs" role="tablist" aria-label="Search result types">
                    <button
                        type="button"
                        class="sb-search-tab"
                        x-bind:data-active="activeTab === 'titles'"
                        x-on:click="activeTab = 'titles'"
                    >
                        <span>Titles</span>
                        <span class="sb-search-tab-count">{{ number_format($titleResultsCount) }}</span>
                    </button>

                    <button
                        type="button"
                        class="sb-search-tab"
                        x-bind:data-active="activeTab === 'people'"
                        x-on:click="activeTab = 'people'"
                    >
                        <span>People</span>
                        <span class="sb-search-tab-count">{{ number_format($peopleCount) }}</span>
                    </button>

                    <button
                        type="button"
                        class="sb-search-tab"
                        x-bind:data-active="activeTab === 'lists'"
                        x-on:click="activeTab = 'lists'"
                    >
                        <span>Lists</span>
                        <span class="sb-search-tab-count">{{ number_format($listsCount) }}</span>
                    </button>
                </div>
            </div>

            @if ($showGroupedMatches && ! $hasAnyResults)
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white/0 dark:border-white/10 dark:bg-transparent">
                    <x-ui.empty.media>
                        <x-ui.icon name="magnifying-glass" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No search results match the current query and filters.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Broaden the keyword, remove a few title filters, or try another language, country, year range, or curator term.
                    </x-ui.text>
                </x-ui.empty>
            @endif

            <div x-cloak x-show="activeTab === 'titles'" class="space-y-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="neutral">{{ number_format($titleResultsCount) }} title matches</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" :icon="collect($filterOptions['sortOptions'])->firstWhere('value', $sort)['icon'] ?? 'bars-arrow-down'">
                            {{ collect($filterOptions['sortOptions'])->firstWhere('value', $sort)['label'] ?? 'Sorted titles' }}
                        </x-ui.badge>
                    </div>

                    @if ($activeFilterCount > 0)
                        <x-ui.button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="clearTitleFilters">
                            Clear {{ $activeFilterCount }} title filters
                        </x-ui.button>
                    @endif
                </div>

                <details class="sb-filter-shell rounded-[1.35rem] p-4 sm:p-5" @if ($activeFilterCount > 0) open @endif>
                    <summary class="sb-search-filter-toggle">
                        <span>Refine title results</span>
                        <span>{{ $activeFilterCount > 0 ? $activeFilterCount.' active' : 'Optional' }}</span>
                    </summary>

                    <div class="mt-4 grid gap-4 xl:grid-cols-4">
                        <x-ui.field>
                            <x-ui.label>Type</x-ui.label>
                            <x-ui.combobox wire:model.live="type" class="sb-filter-control w-full" placeholder="All types" clearable>
                                @foreach ($filterOptions['titleTypes'] as $typeOption)
                                    <x-ui.combobox.option
                                        wire:key="search-type-{{ $typeOption->value }}"
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
                                @foreach ($filterOptions['genres'] as $genreOption)
                                    <x-ui.combobox.option wire:key="search-genre-{{ $genreOption->id }}" value="{{ $genreOption->slug }}" icon="tag">
                                        {{ $genreOption->name }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Year from</x-ui.label>
                            <x-ui.combobox wire:model.live="yearFrom" class="sb-filter-control w-full" placeholder="Any year" clearable>
                                @foreach ($filterOptions['years'] as $yearOption)
                                    <x-ui.combobox.option wire:key="search-year-from-{{ $yearOption }}" value="{{ $yearOption }}" icon="calendar-days">
                                        {{ $yearOption }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Year to</x-ui.label>
                            <x-ui.combobox wire:model.live="yearTo" class="sb-filter-control w-full" placeholder="Any year" clearable>
                                @foreach ($filterOptions['years'] as $yearOption)
                                    <x-ui.combobox.option wire:key="search-year-to-{{ $yearOption }}" value="{{ $yearOption }}" icon="calendar-days">
                                        {{ $yearOption }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Rating from</x-ui.label>
                            <x-ui.input wire:model.live.debounce.300ms="ratingMin" name="rating_min" type="number" min="0" max="10" step="0.1" placeholder="0.0" class="sb-filter-control" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Rating to</x-ui.label>
                            <x-ui.input wire:model.live.debounce.300ms="ratingMax" name="rating_max" type="number" min="0" max="10" step="0.1" placeholder="10.0" class="sb-filter-control" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Votes</x-ui.label>
                            <x-ui.combobox wire:model.live="votesMin" class="sb-filter-control w-full" placeholder="Any volume" clearable>
                                @foreach ($filterOptions['voteThresholdOptions'] as $voteThresholdOption)
                                    <x-ui.combobox.option wire:key="search-votes-{{ $voteThresholdOption['value'] }}" value="{{ $voteThresholdOption['value'] }}" icon="users">
                                        {{ $voteThresholdOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Language</x-ui.label>
                            <x-ui.combobox wire:model.live="language" class="sb-filter-control w-full" placeholder="Any language" clearable>
                                @foreach ($filterOptions['languages'] as $languageOption)
                                    <x-ui.combobox.option wire:key="search-language-{{ $languageOption['value'] }}" value="{{ $languageOption['value'] }}" icon="language">
                                        {{ $languageOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Country</x-ui.label>
                            <x-ui.combobox wire:model.live="country" class="sb-filter-control w-full" placeholder="Any country" clearable>
                                @foreach ($filterOptions['countries'] as $countryOption)
                                    <x-ui.combobox.option wire:key="search-country-{{ $countryOption['value'] }}" value="{{ $countryOption['value'] }}" icon="globe-alt">
                                        {{ $countryOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Runtime</x-ui.label>
                            <x-ui.combobox wire:model.live="runtime" class="sb-filter-control w-full" placeholder="Any runtime" clearable>
                                @foreach ($filterOptions['runtimeOptions'] as $runtimeOption)
                                    <x-ui.combobox.option wire:key="search-runtime-{{ $runtimeOption['value'] }}" value="{{ $runtimeOption['value'] }}" icon="clock">
                                        {{ $runtimeOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>TV status</x-ui.label>
                            <x-ui.combobox wire:model.live="status" class="sb-filter-control w-full" placeholder="Any status" clearable>
                                @foreach ($filterOptions['statusOptions'] as $statusOption)
                                    <x-ui.combobox.option wire:key="search-status-{{ $statusOption['value'] }}" value="{{ $statusOption['value'] }}" icon="tv">
                                        {{ $statusOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Sort</x-ui.label>
                            <x-ui.combobox wire:model.live="sort" class="sb-filter-control w-full" placeholder="Sort titles">
                                @foreach ($filterOptions['sortOptions'] as $sortOption)
                                    <x-ui.combobox.option wire:key="search-sort-{{ $sortOption['value'] }}" value="{{ $sortOption['value'] }}" :icon="$sortOption['icon'] ?? 'bars-arrow-down'">
                                        {{ $sortOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                        </x-ui.field>
                    </div>
                </details>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($titles as $title)
                        <article class="sb-search-result-card sb-search-result-card--title" wire:key="search-title-result-{{ $title->id }}">
                            <a href="{{ route('public.titles.show', $title) }}" class="sb-search-result-media sb-search-result-media--title">
                                @if ($title->preferredPoster())
                                    <img
                                        src="{{ $title->preferredPoster()->url }}"
                                        alt="{{ $title->preferredPoster()->alt_text ?: $title->name }}"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-full min-h-36 w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                        <x-ui.icon name="film" class="size-8" />
                                    </div>
                                @endif
                            </a>

                            <div class="sb-search-result-body">
                                <div class="sb-search-meta">
                                    <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                        {{ $title->typeLabel() }}
                                    </span>
                                    @if ($title->release_year)
                                        <span class="sb-search-chip sb-search-chip--tight">{{ $title->release_year }}</span>
                                    @endif
                                    @if ($title->displayAverageRating())
                                        <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                            <x-ui.icon name="star" class="size-3" />
                                            {{ number_format($title->displayAverageRating(), 1) }}
                                        </span>
                                    @endif
                                </div>

                                <x-ui.heading level="h3" size="md" class="sb-search-result-title">
                                    <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                                        {{ $title->name }}
                                    </a>
                                </x-ui.heading>

                                <div class="sb-search-result-detail">
                                    @if ($title->previewGenres(2)->isNotEmpty())
                                        <span>{{ $title->previewGenres(2)->pluck('name')->join(' · ') }}</span>
                                    @endif
                                    @if ($title->runtime_minutes)
                                        <span>{{ $title->runtime_minutes }} min</span>
                                    @endif
                                    @if (filled($title->origin_country))
                                        <span>{{ $title->origin_country }}</span>
                                    @endif
                                </div>

                                <div class="mt-auto flex flex-wrap items-center justify-between gap-3 text-sm text-[#9f9486]">
                                    <span class="sb-search-result-stat">{{ number_format($title->displayReviewCount()) }} reviews</span>

                                    <x-ui.link :href="route('public.titles.show', $title)" variant="ghost" iconAfter="arrow-right">
                                        View title
                                    </x-ui.link>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3">
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white/0 dark:border-white/10 dark:bg-transparent">
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
            </div>

            <div x-cloak x-show="activeTab === 'people'" class="space-y-5">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($peopleCount) }} people matches</x-ui.badge>
                    @if (! $showGroupedMatches)
                        <x-ui.badge variant="outline" color="slate">Type at least 2 characters</x-ui.badge>
                    @endif
                </div>

                @if (! $showGroupedMatches)
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white/0 dark:border-white/10 dark:bg-transparent">
                        <x-ui.empty.media>
                            <x-ui.icon name="user" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">People results appear once the query is specific enough.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Enter at least two characters to search the people index and unlock portrait-first matches.
                        </x-ui.text>
                    </x-ui.empty>
                @elseif ($people->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($people as $person)
                            <article class="sb-search-result-card sb-search-result-card--person" wire:key="search-person-result-{{ $person->id }}">
                                <a href="{{ route('public.people.show', $person) }}" class="sb-search-result-media sb-search-result-media--person">
                                    @if ($person->preferredHeadshot())
                                        <img
                                            src="{{ $person->preferredHeadshot()->url }}"
                                            alt="{{ $person->preferredHeadshot()->alt_text ?: $person->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full min-h-32 w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                            <x-ui.icon name="user" class="size-8" />
                                        </div>
                                    @endif
                                </a>

                                <div class="sb-search-result-body">
                                    <div class="sb-search-meta">
                                        <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">{{ $person->primaryProfessionLabel() }}</span>
                                        @if (filled($person->nationality))
                                            <span class="sb-search-chip sb-search-chip--tight">{{ $person->nationality }}</span>
                                        @endif
                                    </div>

                                    <x-ui.heading level="h3" size="md" class="sb-search-result-title">
                                        <a href="{{ route('public.people.show', $person) }}" class="hover:opacity-80">
                                            {{ $person->name }}
                                        </a>
                                    </x-ui.heading>

                                    <div class="sb-search-result-detail">
                                        @if ($person->secondaryProfessionLabel() !== '')
                                            <span>{{ $person->secondaryProfessionLabel() }}</span>
                                        @endif
                                        @if ($person->birth_date)
                                            <span>{{ $person->birth_date->format('Y') }}</span>
                                        @endif
                                        @if (filled($person->birth_place))
                                            <span>{{ $person->birth_place }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 text-sm text-[#9f9486]">
                                        <span class="sb-search-result-stat">{{ number_format((int) $person->credits_count) }} credits</span>

                                        <x-ui.link :href="route('public.people.show', $person)" variant="ghost" iconAfter="arrow-right">
                                            View profile
                                        </x-ui.link>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white/0 dark:border-white/10 dark:bg-transparent">
                        <x-ui.empty.media>
                            <x-ui.icon name="user" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No people match the current search.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Try a performer, creator, or alternate name to broaden people results.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </div>

            <div x-cloak x-show="activeTab === 'lists'" class="space-y-5">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral">{{ number_format($listsCount) }} public lists</x-ui.badge>
                    @if (! $showGroupedMatches)
                        <x-ui.badge variant="outline" color="slate">Type at least 2 characters</x-ui.badge>
                    @endif
                </div>

                @if (! $showGroupedMatches)
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white/0 dark:border-white/10 dark:bg-transparent">
                        <x-ui.empty.media>
                            <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">List results appear once the query is specific enough.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Enter at least two characters to search public curated lists and curator usernames.
                        </x-ui.text>
                    </x-ui.empty>
                @elseif ($lists->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($lists as $list)
                            <article class="sb-search-result-card sb-search-result-card--list" wire:key="search-list-result-{{ $list->id }}">
                                <a href="{{ route('public.lists.show', [$list->user, $list]) }}" class="sb-search-result-media sb-search-result-media--list">
                                    @if ($list->previewPoster())
                                        <img
                                            src="{{ $list->previewPoster()->url }}"
                                            alt="{{ $list->previewPoster()->alt_text ?: $list->previewTitle()?->name ?: $list->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full min-h-36 w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                            <x-ui.icon name="queue-list" class="size-8" />
                                        </div>
                                    @endif
                                </a>

                                <div class="sb-search-result-body">
                                    <div class="sb-search-meta">
                                        <span class="sb-search-chip sb-search-chip--lists sb-search-chip--tight">Public list</span>
                                        <span class="sb-search-chip sb-search-chip--tight">{{ number_format($list->published_items_count) }} titles</span>
                                    </div>

                                    <x-ui.heading level="h3" size="md" class="sb-search-result-title">
                                        <a href="{{ route('public.lists.show', [$list->user, $list]) }}" class="hover:opacity-80">
                                            {{ $list->name }}
                                        </a>
                                    </x-ui.heading>

                                    <x-ui.text class="sb-search-result-copy">
                                        {{ filled($list->description) ? str($list->description)->limit(150) : 'A public Screenbase list curated for browsing and discovery.' }}
                                    </x-ui.text>

                                    <div class="sb-search-result-detail">
                                        <span>{{ '@'.$list->user->username }}</span>
                                        <span>Updated {{ $list->updated_at?->diffForHumans() }}</span>
                                        @if ($list->previewTitle())
                                            <span>Preview: {{ $list->previewTitle()?->name }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 text-sm text-[#9f9486]">
                                        <span class="sb-search-result-stat">{{ number_format($list->published_items_count) }} published titles</span>

                                        <x-ui.link :href="route('public.lists.show', [$list->user, $list])" variant="ghost" iconAfter="arrow-right">
                                            View list
                                        </x-ui.link>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white/0 dark:border-white/10 dark:bg-transparent">
                        <x-ui.empty.media>
                            <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No public lists match the current search.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Try a list title, description term, or curator username to broaden public list matches.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </div>
        </section>
    </div>
</div>
