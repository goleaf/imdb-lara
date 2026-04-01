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
        <x-ui.card class="!max-w-none relative overflow-hidden border-black/5 bg-[linear-gradient(140deg,rgba(15,23,42,0.98),rgba(23,23,23,0.96))] text-white dark:border-white/10 dark:bg-[linear-gradient(140deg,rgba(250,250,250,0.08),rgba(23,23,23,0.96))]">
            @if ($heroBackdrop)
                <div class="absolute inset-0">
                    <img
                        src="{{ $heroBackdrop->url }}"
                        alt="{{ $heroBackdrop->alt_text ?: ($heroSpotlight?->name ?? 'Screenbase spotlight') }}"
                        class="h-full w-full object-cover opacity-20"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(120deg,rgba(15,23,42,0.92),rgba(15,23,42,0.82),rgba(23,23,23,0.92))]"></div>
                </div>
            @endif

            <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_15rem]">
                <div class="space-y-4">
                    <x-ui.badge color="amber" icon="sparkles">Hero Spotlight</x-ui.badge>

                    @if ($heroSpotlight)
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge variant="outline">{{ str($heroSpotlight->title_type->value)->headline() }}</x-ui.badge>
                            @if ($heroSpotlight->release_year)
                                <a href="{{ route('public.years.show', ['year' => $heroSpotlight->release_year]) }}">
                                    <x-ui.badge variant="outline" color="slate">{{ $heroSpotlight->release_year }}</x-ui.badge>
                                </a>
                            @endif
                            @if ($heroStatistic?->average_rating)
                                <x-ui.badge icon="star" color="amber">
                                    {{ number_format((float) $heroStatistic->average_rating, 1) }}
                                </x-ui.badge>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="text-white">
                                <a href="{{ route('public.titles.show', $heroSpotlight) }}" class="hover:opacity-80">
                                    {{ $heroSpotlight->name }}
                                </a>
                            </x-ui.heading>

                            <x-ui.text class="max-w-3xl text-base text-white/80">
                                {{ $heroSpotlight->tagline ?: $heroSpotlight->plot_outline ?: 'A featured title from the public Screenbase catalog.' }}
                            </x-ui.text>

                            @if (filled($heroSpotlight->synopsis))
                                <x-ui.text class="max-w-3xl text-sm text-white/70">
                                    {{ str($heroSpotlight->synopsis)->limit(280) }}
                                </x-ui.text>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($heroGenres as $genre)
                                <a href="{{ route('public.genres.show', $genre) }}">
                                    <x-ui.badge variant="outline" color="neutral">{{ $genre->name }}</x-ui.badge>
                                </a>
                            @endforeach
                        </div>

                        @if ($heroCast->isNotEmpty())
                            <div class="space-y-2">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/50">Principal Cast & Crew</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($heroCast as $person)
                                        <a href="{{ route('public.people.show', $person) }}">
                                            <x-ui.badge variant="outline" color="slate">{{ $person->name }}</x-ui.badge>
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
                        <x-ui.heading level="h1" size="xl" class="text-white">
                            Serious title discovery, ratings, reviews, and people pages.
                        </x-ui.heading>
                        <x-ui.text class="max-w-2xl text-base text-white/80">
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
                    <div class="overflow-hidden rounded-box border border-white/10 bg-white/5">
                        @if ($heroPoster)
                            <img
                                src="{{ $heroPoster->url }}"
                                alt="{{ $heroPoster->alt_text ?: ($heroSpotlight?->name ?? 'Screenbase spotlight') }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center bg-white/5 text-white/60">
                                <x-ui.icon name="film" class="size-12" />
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                        <div class="rounded-box border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-white/50">Ratings</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($heroStatistic?->rating_count ?? 0)) }}</div>
                        </div>
                        <div class="rounded-box border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-white/50">Reviews</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($heroStatistic?->review_count ?? 0)) }}</div>
                        </div>
                        <div class="rounded-box border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-white/50">Watchlists</div>
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
                        <x-ui.heading level="h2" size="lg">Start Anywhere</x-ui.heading>
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
                    <x-ui.heading level="h2" size="lg">Live Signals</x-ui.heading>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="font-medium">Trending, not static</div>
                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Watchlists and review volume already influence the homepage ranking rails.
                            </div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="font-medium">People and lists matter</div>
                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Public profiles, curated lists, and latest writing sit beside the title catalog.
                            </div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="font-medium">SEO-friendly routes</div>
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
