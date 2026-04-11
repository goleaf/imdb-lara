@extends('layouts.public')

@section('title', 'Home')
@section('meta_description', 'Browse the imported IMDb catalog through trending titles, top rated movies and series, featured genres, and people pages.')

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-home-hero-card !max-w-none overflow-hidden p-0">
            <div class="relative">
                @if ($heroBackdrop)
                    <img
                        src="{{ $heroBackdrop->url }}"
                        alt="{{ $heroBackdrop->alt_text ?: ($heroSpotlight?->name ?? 'Screenbase spotlight') }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-30"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(110deg,rgba(10,10,9,0.95),rgba(10,10,9,0.82),rgba(10,10,9,0.54))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(16,15,13,0.96),rgba(10,10,9,0.98))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[15rem_minmax(0,1fr)]">
                    <div class="overflow-hidden rounded-[1.4rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                        @if ($heroPoster)
                            <img
                                src="{{ $heroPoster->url }}"
                                alt="{{ $heroPoster->alt_text ?: ($heroSpotlight?->name ?? 'Screenbase spotlight') }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="film" class="size-14" />
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge color="amber" icon="sparkles">Catalog Spotlight</x-ui.badge>
                                @if ($heroSpotlight)
                                    <x-ui.badge variant="outline" icon="{{ $heroSpotlight->typeIcon() }}">{{ $heroSpotlight->typeLabel() }}</x-ui.badge>
                                    @if ($heroSpotlight->release_year)
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $heroSpotlight->release_year }}</x-ui.badge>
                                    @endif
                                    @if ($heroStatistic?->average_rating)
                                        <x-ui.badge color="amber" icon="star">{{ number_format((float) $heroStatistic->average_rating, 1) }}</x-ui.badge>
                                    @endif
                                @endif
                            </div>

                            @if ($heroSpotlight)
                                <div class="space-y-3">
                                    <x-ui.heading level="h1" size="xl" class="sb-page-title">
                                        <a href="{{ route('public.titles.show', $heroSpotlight) }}" class="hover:opacity-80">
                                            {{ $heroSpotlight->name }}
                                        </a>
                                    </x-ui.heading>

                                    <x-ui.text class="sb-page-copy max-w-4xl text-base">
                                        {{ $heroSpotlight->summaryText() ?: 'A featured title from the imported catalog.' }}
                                    </x-ui.text>
                                </div>

                                @if ($heroGenres->isNotEmpty())
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($heroGenres as $genre)
                                            <a href="{{ route('public.genres.show', $genre) }}">
                                                <x-ui.badge variant="outline" color="neutral" icon="tag">{{ $genre->name }}</x-ui.badge>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($heroCast->isNotEmpty())
                                    <div class="space-y-2">
                                        <div class="sb-page-kicker">Featured cast and crew</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($heroCast as $person)
                                                <a href="{{ route('public.people.show', $person) }}">
                                                    <x-ui.badge variant="outline" color="slate" icon="user">{{ $person->name }}</x-ui.badge>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-3">
                                    <x-ui.button as="a" :href="route('public.titles.show', $heroSpotlight)" icon="film">
                                        View title page
                                    </x-ui.button>
                                    @if ($heroTrailer?->url)
                                        <x-ui.button as="a" :href="$heroTrailer->url" variant="outline" icon="play">
                                            Watch trailer
                                        </x-ui.button>
                                    @endif
                                    <x-ui.button as="a" :href="route('public.discover')" variant="ghost" icon="sparkles">
                                        Open discovery
                                    </x-ui.button>
                                </div>
                            @else
                                <div class="space-y-3">
                                    <x-ui.heading level="h1" size="xl" class="sb-page-title">
                                        Imported catalog, adapted to the current app.
                                    </x-ui.heading>
                                    <x-ui.text class="sb-page-copy max-w-3xl text-base">
                                        The public surface now reads directly from the remote MySQL IMDb-style database, with catalog pages for titles, people, seasons, and episodes.
                                    </x-ui.text>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <x-ui.button as="a" :href="route('public.discover')" icon="sparkles">
                                        Start discovering
                                    </x-ui.button>
                                    <x-ui.button as="a" :href="route('public.search')" variant="outline" icon="magnifying-glass">
                                        Search the catalog
                                    </x-ui.button>
                                </div>
                            @endif
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sb-home-stat rounded-[1.2rem] p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">Votes</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($heroStatistic?->rating_count ?? 0)) }}</div>
                            </div>
                            <div class="sb-home-stat rounded-[1.2rem] p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">Genres</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) $featuredGenres->count()) }}</div>
                            </div>
                            <div class="sb-home-stat rounded-[1.2rem] p-4">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">People</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) $popularPeople->count()) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-3">
            <x-ui.card class="!max-w-none p-5">
                <div class="space-y-3">
                    <div class="sb-page-kicker">Browse</div>
                    <x-ui.heading level="h2" size="lg">Start anywhere</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        The public catalog is split into focused browse routes instead of one overloaded index.
                    </x-ui.text>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button as="a" :href="route('public.movies.index')" variant="outline" icon="film">Movies</x-ui.button>
                        <x-ui.button as="a" :href="route('public.series.index')" variant="outline" icon="tv">TV Shows</x-ui.button>
                        <x-ui.button as="a" :href="route('public.people.index')" variant="outline" icon="users">People</x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none p-5">
                <div class="space-y-3">
                    <div class="sb-page-kicker">Featured genres</div>
                    <x-ui.heading level="h2" size="lg">Genre hubs</x-ui.heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($featuredGenres as $genre)
                            <a href="{{ route('public.genres.show', $genre) }}">
                                <x-ui.badge variant="outline" color="neutral" icon="tag">{{ $genre->name }}</x-ui.badge>
                            </a>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none p-5">
                <div class="space-y-3">
                    <div class="sb-page-kicker">Fast routes</div>
                    <x-ui.heading level="h2" size="lg">Search and charts</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        Jump directly into trending, top rated pages, or a full-text catalog search.
                    </x-ui.text>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button as="a" :href="route('public.trending')" variant="outline" icon="bolt">Trending</x-ui.button>
                        <x-ui.button as="a" :href="route('public.rankings.movies')" variant="outline" icon="star">Top Movies</x-ui.button>
                        <x-ui.button as="a" :href="route('public.trailers.latest')" variant="outline" icon="play">Trailers</x-ui.button>
                        <x-ui.button as="a" :href="route('public.search')" variant="outline" icon="magnifying-glass">Search</x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </div>

        @php
            $featuredAwardEntry = $awardsSpotlightEntries->first();
            $supportingAwardEntries = $awardsSpotlightEntries->slice(1)->values();
            $featuredTrailerTitle = $latestTrailerTitles->first();
            $supportingTrailerTitles = $latestTrailerTitles->slice(1)->values();
        @endphp

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)]">
            <x-ui.card class="!max-w-none overflow-hidden p-0" data-slot="home-awards-spotlight">
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="sb-page-kicker">Awards archive</div>
                            <x-ui.heading level="h2" size="lg">Awards Spotlight</x-ui.heading>
                            <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                A quick pass through award-recognized titles already linked to the imported event, category, and nominee tables.
                            </x-ui.text>
                        </div>
                        <x-ui.link :href="route('public.awards.index')" variant="ghost" iconAfter="arrow-right">
                            Open awards
                        </x-ui.link>
                    </div>

                    @if ($featuredAwardEntry)
                        <div class="grid gap-4 lg:grid-cols-[10rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-[1.25rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                @if ($featuredAwardEntry['title']->preferredPoster())
                                    <img
                                        src="{{ $featuredAwardEntry['title']->preferredPoster()->url }}"
                                        alt="{{ $featuredAwardEntry['title']->preferredPoster()->alt_text ?: $featuredAwardEntry['title']->name }}"
                                        class="aspect-[2/3] w-full object-cover"
                                    >
                                @else
                                    <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="trophy" class="size-12" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-4">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :color="$featuredAwardEntry['nomination']->is_winner ? 'amber' : 'neutral'" :icon="$featuredAwardEntry['nomination']->is_winner ? 'trophy' : 'bookmark'">
                                        {{ $featuredAwardEntry['statusLabel'] }}
                                    </x-ui.badge>
                                    @if ($featuredAwardEntry['nomination']->award_year)
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">
                                            {{ $featuredAwardEntry['nomination']->award_year }}
                                        </x-ui.badge>
                                    @endif
                                    <x-ui.badge variant="outline" color="neutral" icon="sparkles">
                                        {{ $featuredAwardEntry['categoryLabel'] }}
                                    </x-ui.badge>
                                </div>

                                <div class="space-y-2">
                                    <x-ui.heading level="h3" size="lg">
                                        <a href="{{ route('public.titles.show', $featuredAwardEntry['title']) }}" class="hover:opacity-80">
                                            {{ $featuredAwardEntry['title']->name }}
                                        </a>
                                    </x-ui.heading>
                                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $featuredAwardEntry['eventLabel'] }}
                                        @if ($featuredAwardEntry['honoreeLabel'])
                                            · {{ $featuredAwardEntry['honoreeLabel'] }}
                                        @endif
                                    </x-ui.text>
                                    <x-ui.text class="text-sm leading-7 text-neutral-700 dark:text-neutral-200">
                                        {{ str($featuredAwardEntry['title']->summaryText() ?: 'Award-linked catalog title with connected event, category, and nominee records.')->limit(220) }}
                                    </x-ui.text>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <x-ui.button as="a" :href="route('public.titles.show', $featuredAwardEntry['title'])" variant="outline" icon="film">
                                        View title page
                                    </x-ui.button>
                                    <x-ui.button as="a" :href="route('public.awards.index')" variant="ghost" icon="trophy">
                                        Browse archive
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>

                        @if ($supportingAwardEntries->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($supportingAwardEntries as $entry)
                                    <a href="{{ route('public.titles.show', $entry['title']) }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $entry['title']->name }}</div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $entry['categoryLabel'] }}</div>
                                            </div>
                                            <x-ui.badge variant="outline" color="{{ $entry['nomination']->is_winner ? 'amber' : 'slate' }}" icon="{{ $entry['nomination']->is_winner ? 'trophy' : 'bookmark' }}">
                                                {{ $entry['statusLabel'] }}
                                            </x-ui.badge>
                                        </div>
                                        <div class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $entry['eventLabel'] }}
                                            @if ($entry['nomination']->award_year)
                                                · {{ $entry['nomination']->award_year }}
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="trophy" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No award-linked titles are published yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                This section fills automatically once the imported awards graph exposes title-linked nominations.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none overflow-hidden p-0" data-slot="home-trailers-preview">
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="sb-page-kicker">Trailer archive</div>
                            <x-ui.heading level="h2" size="lg">Latest Trailers</x-ui.heading>
                            <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                Trailer-linked titles pulled directly from the imported video rows, with fast paths into the title page or the full trailer archive.
                            </x-ui.text>
                        </div>
                        <x-ui.link :href="route('public.trailers.latest')" variant="ghost" iconAfter="arrow-right">
                            Open trailers
                        </x-ui.link>
                    </div>

                    @if ($featuredTrailerTitle)
                        <div class="space-y-4">
                            <div class="overflow-hidden rounded-[1.25rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                @if ($featuredTrailerTitle->preferredBackdrop() || $featuredTrailerTitle->preferredPoster())
                                    @php
                                        $featuredTrailerAsset = $featuredTrailerTitle->preferredBackdrop() ?: $featuredTrailerTitle->preferredPoster();
                                    @endphp
                                    <img
                                        src="{{ $featuredTrailerAsset->url }}"
                                        alt="{{ $featuredTrailerAsset->alt_text ?: $featuredTrailerTitle->name }}"
                                        class="aspect-[16/9] w-full object-cover"
                                    >
                                @else
                                    <div class="flex aspect-[16/9] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="play" class="size-12" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge color="amber" icon="play">Trailer-ready</x-ui.badge>
                                    <x-ui.badge variant="outline" color="neutral" :icon="$featuredTrailerTitle->typeIcon()">{{ $featuredTrailerTitle->typeLabel() }}</x-ui.badge>
                                    @if ($featuredTrailerTitle->release_year)
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $featuredTrailerTitle->release_year }}</x-ui.badge>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    <x-ui.heading level="h3" size="lg">
                                        <a href="{{ route('public.titles.show', $featuredTrailerTitle) }}" class="hover:opacity-80">
                                            {{ $featuredTrailerTitle->name }}
                                        </a>
                                    </x-ui.heading>
                                    <x-ui.text class="text-sm leading-7 text-neutral-700 dark:text-neutral-200">
                                        {{ str($featuredTrailerTitle->summaryText() ?: 'Trailer-linked catalog title with synced poster, backdrop, and video metadata.')->limit(220) }}
                                    </x-ui.text>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    @if ($featuredTrailerTitle->preferredVideo()?->url)
                                        <x-ui.button as="a" :href="$featuredTrailerTitle->preferredVideo()->url" icon="play">
                                            Watch trailer
                                        </x-ui.button>
                                    @endif
                                    <x-ui.button as="a" :href="route('public.titles.show', $featuredTrailerTitle)" variant="outline" icon="film">
                                        View title page
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>

                        @if ($supportingTrailerTitles->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($supportingTrailerTitles as $title)
                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                                    <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                                                        {{ $title->name }}
                                                    </a>
                                                </div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $title->typeLabel() }}@if ($title->release_year) · {{ $title->release_year }} @endif
                                                </div>
                                            </div>
                                            <x-ui.badge variant="outline" color="amber" icon="play">Trailer</x-ui.badge>
                                        </div>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($title->preferredVideo()?->url)
                                                <x-ui.button as="a" :href="$title->preferredVideo()->url" variant="ghost" icon="play">
                                                    Watch
                                                </x-ui.button>
                                            @endif
                                            <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="ghost" icon="film">
                                                Title
                                            </x-ui.button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="play" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No trailer-linked titles are available yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                This section updates as soon as the imported catalog publishes linked video rows.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </section>

    <section class="mt-12 space-y-10">
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="sb-page-kicker">Live chart</div>
                    <x-ui.heading level="h2" size="lg">Trending titles</x-ui.heading>
                </div>
                <x-ui.link :href="route('public.trending')" variant="ghost" iconAfter="arrow-right">
                    Open trending
                </x-ui.link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($trendingTitles as $title)
                    <x-catalog.title-card :title="$title" />
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="sb-page-kicker">Top rated</div>
                    <x-ui.heading level="h2" size="lg">Movies</x-ui.heading>
                </div>
                <x-ui.link :href="route('public.rankings.movies')" variant="ghost" iconAfter="arrow-right">
                    Full movie chart
                </x-ui.link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($topMovieTitles as $title)
                    <x-catalog.title-card :title="$title" />
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="sb-page-kicker">Top rated</div>
                    <x-ui.heading level="h2" size="lg">Series</x-ui.heading>
                </div>
                <x-ui.link :href="route('public.rankings.series')" variant="ghost" iconAfter="arrow-right">
                    Full series chart
                </x-ui.link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($topSeriesTitles as $title)
                    <x-catalog.title-card :title="$title" />
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="sb-page-kicker">Editors' picks</div>
                    <x-ui.heading level="h2" size="lg">Featured titles</x-ui.heading>
                </div>
                <x-ui.link :href="route('public.discover')" variant="ghost" iconAfter="arrow-right">
                    Discovery
                </x-ui.link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($featuredTitles as $title)
                    <x-catalog.title-card :title="$title" />
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="sb-page-kicker">Profiles</div>
                    <x-ui.heading level="h2" size="lg">Popular people</x-ui.heading>
                </div>
                <x-ui.link :href="route('public.people.index')" variant="ghost" iconAfter="arrow-right">
                    Browse people
                </x-ui.link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($popularPeople as $person)
                    <x-catalog.person-card :person="$person" />
                @endforeach
            </div>
        </div>
    </section>
@endsection
