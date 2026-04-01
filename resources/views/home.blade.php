@extends('layouts.public')

@section('title', 'Home')
@section('meta_description', 'Discover trending titles, top rated movies and TV shows, popular people, fresh trailers, and community lists on Screenbase.')

@section('content')
    @php
        $heroBackdrop = $heroSpotlight?->titleImages?->firstWhere('kind', \App\Enums\MediaKind::Backdrop);
        $heroPoster = $heroSpotlight?->mediaAssets?->first() ?? $heroSpotlight?->titleImages?->firstWhere('kind', \App\Enums\MediaKind::Poster);
        $heroStatistic = $heroSpotlight?->statistic;
        $heroGenres = $heroSpotlight?->genres?->take(4) ?? collect();
        $heroCast = $heroSpotlight?->credits?->pluck('person')->filter()->unique('id')->take(4) ?? collect();
        $heroTrailer = $heroSpotlight?->titleVideos?->first();
    @endphp

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.75fr)]">
        <x-ui.card
            data-slot="home-hero"
            class="!max-w-none relative overflow-hidden border-black/5 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.18),transparent_32%),linear-gradient(145deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] text-neutral-950 shadow-sm dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.16),transparent_28%),linear-gradient(145deg,rgba(23,23,23,0.98),rgba(10,10,10,0.98))] dark:text-white"
        >
            @if ($heroBackdrop)
                <div class="absolute inset-0">
                    <img
                        src="{{ $heroBackdrop->url }}"
                        alt="{{ $heroBackdrop->alt_text ?: ($heroSpotlight?->name ?? 'Screenbase spotlight') }}"
                        class="h-full w-full object-cover opacity-14 dark:opacity-20"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(120deg,rgba(255,255,255,0.94),rgba(255,255,255,0.84),rgba(248,250,252,0.92))] dark:bg-[linear-gradient(120deg,rgba(15,23,42,0.92),rgba(15,23,42,0.82),rgba(23,23,23,0.92))]"></div>
                </div>
            @endif

            <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_15rem]">
                <div class="space-y-4 rounded-box bg-white/90 p-5 shadow-md ring-1 ring-black/5 backdrop-blur-sm dark:bg-neutral-950/70 dark:ring-white/10">
                    <x-ui.badge color="amber" icon="sparkles">Hero Spotlight</x-ui.badge>

                    @if ($heroSpotlight)
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge variant="outline" icon="film">{{ str($heroSpotlight->title_type->value)->headline() }}</x-ui.badge>
                            @if ($heroSpotlight->release_year)
                                <a href="{{ route('public.years.show', ['year' => $heroSpotlight->release_year]) }}">
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $heroSpotlight->release_year }}</x-ui.badge>
                                </a>
                            @endif
                            @if ($heroStatistic?->average_rating)
                                <x-ui.badge icon="star" color="amber">
                                    {{ number_format((float) $heroStatistic->average_rating, 1) }}
                                </x-ui.badge>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="text-neutral-950 dark:text-white">
                                <a href="{{ route('public.titles.show', $heroSpotlight) }}" class="hover:opacity-80">
                                    {{ $heroSpotlight->name }}
                                </a>
                            </x-ui.heading>

                            <x-ui.text class="max-w-3xl text-base text-neutral-700 dark:text-white/80">
                                {{ $heroSpotlight->tagline ?: $heroSpotlight->plot_outline ?: 'A featured title from the public Screenbase catalog.' }}
                            </x-ui.text>

                            @if (filled($heroSpotlight->synopsis))
                                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-white/70">
                                    {{ str($heroSpotlight->synopsis)->limit(280) }}
                                </x-ui.text>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($heroGenres as $genre)
                                <a href="{{ route('public.genres.show', $genre) }}">
                                    <x-ui.badge variant="outline" color="neutral" icon="tag">{{ $genre->name }}</x-ui.badge>
                                </a>
                            @endforeach
                        </div>

                        @if ($heroCast->isNotEmpty())
                            <div class="space-y-2">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">Principal Cast & Crew</div>
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
                                View Title Page
                            </x-ui.button>
                            @if (filled($heroTrailer?->url))
                                <x-ui.button as="a" :href="$heroTrailer->url" variant="outline" icon="play">
                                    Watch Trailer
                                </x-ui.button>
                            @elseif ($hasPublicLatestTrailersRoute)
                                <x-ui.button as="a" :href="route('public.trailers.latest')" variant="outline" icon="play">
                                    Latest Trailers
                                </x-ui.button>
                            @endif
                            <x-ui.button as="a" :href="route('public.discover')" variant="ghost" icon="sparkles">
                                Open Discovery
                            </x-ui.button>
                        </div>
                    @else
                        <x-ui.heading level="h1" size="xl" class="text-neutral-950 dark:text-white">
                            Serious title discovery, ratings, reviews, and people pages.
                        </x-ui.heading>
                        <x-ui.text class="max-w-2xl text-base text-neutral-700 dark:text-white/80">
                            Screenbase is ready for movies, TV shows, people, community reviews, and watchlist-driven discovery as soon as public titles are published.
                        </x-ui.text>

                        <div class="flex flex-wrap gap-3">
                            <x-ui.button as="a" :href="route('public.discover')" icon="sparkles">
                                Start Discovering
                            </x-ui.button>
                            <x-ui.button as="a" :href="route('public.search')" variant="outline" icon="magnifying-glass">
                                Advanced Search
                            </x-ui.button>
                        </div>
                    @endif
                </div>

                <div class="grid gap-3">
                    <div class="overflow-hidden rounded-box border border-black/5 bg-white/75 shadow-sm dark:border-white/10 dark:bg-white/5">
                        @if ($heroPoster)
                            <img
                                src="{{ $heroPoster->url }}"
                                alt="{{ $heroPoster->alt_text ?: ($heroSpotlight?->name ?? 'Screenbase spotlight') }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center bg-white/70 text-neutral-500 dark:bg-white/5 dark:text-white/60">
                                <x-ui.icon name="film" class="size-12" />
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                        <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">
                                <x-ui.icon name="star" class="size-4 text-neutral-500 dark:text-white/60" />
                                <span>Ratings</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($heroStatistic?->rating_count ?? 0)) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">
                                <x-ui.icon name="chat-bubble-left-right" class="size-4 text-neutral-500 dark:text-white/60" />
                                <span>Reviews</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($heroStatistic?->review_count ?? 0)) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-white/50">
                                <x-ui.icon name="bookmark" class="size-4 text-neutral-500 dark:text-white/60" />
                                <span>Watchlists</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($heroStatistic?->watchlist_count ?? 0)) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div>
                        <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                            <x-ui.icon name="squares-2x2" class="size-5 text-neutral-500 dark:text-neutral-400" />
                            <span>Start Anywhere</span>
                        </x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            The public surface is split into focused browse routes, not one overloaded index.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        @if ($hasPublicMoviesRoute)
                            <x-ui.button as="a" :href="route('public.movies.index')" variant="outline" icon="film">
                                Browse Movies
                            </x-ui.button>
                        @endif
                        @if ($hasPublicSeriesRoute)
                            <x-ui.button as="a" :href="route('public.series.index')" variant="outline" icon="tv">
                                Browse TV Shows
                            </x-ui.button>
                        @endif
                        <x-ui.button as="a" :href="route('public.people.index')" variant="outline" icon="users">
                            Browse People
                        </x-ui.button>
                        <x-ui.button as="a" :href="route('public.search')" variant="outline" icon="magnifying-glass">
                            Search Everything
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-3">
                    <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                        <x-ui.icon name="chart-bar" class="size-5 text-neutral-500 dark:text-neutral-400" />
                        <span>Live Signals</span>
                    </x-ui.heading>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 font-medium">
                                <x-ui.icon name="fire" class="size-4 text-neutral-500 dark:text-neutral-400" />
                                <span>Trending, not static</span>
                            </div>
                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Watchlists and review volume already influence the homepage ranking rails.
                            </div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 font-medium">
                                <x-ui.icon name="users" class="size-4 text-neutral-500 dark:text-neutral-400" />
                                <span>People and lists matter</span>
                            </div>
                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Public profiles, curated lists, and latest writing sit beside the title catalog.
                            </div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 font-medium">
                                <x-ui.icon name="globe-alt" class="size-4 text-neutral-500 dark:text-neutral-400" />
                                <span>SEO-friendly routes</span>
                            </div>
                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Every card routes into slug-based title, person, year, genre, and list pages.
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </section>

    <div class="mt-10 space-y-10">
        <livewire:home.title-rail rail="trending" lazy.bundle />
        <livewire:home.title-rail rail="top-rated-movies" lazy.bundle />
        <livewire:home.title-rail rail="top-rated-series" lazy.bundle />
        <livewire:home.title-rail rail="coming-soon" lazy.bundle />
        <livewire:home.title-rail rail="recently-added" lazy.bundle />
        <livewire:home.people-grid lazy.bundle />
        <livewire:home.trailer-grid lazy.bundle />
        <livewire:home.review-grid lazy.bundle />
        <livewire:home.featured-lists-grid lazy.bundle />
        <livewire:home.genre-grid lazy.bundle />
        <livewire:home.year-grid lazy.bundle />
    </div>
@endsection
