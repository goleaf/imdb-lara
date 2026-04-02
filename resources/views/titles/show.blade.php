@extends('layouts.public')

@section('title', $title->meta_title ?: $title->name)
@section('meta_description', $title->meta_description ?: ($title->plot_outline ?: 'Read credits, ratings, and reviews for '.$title->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-detail-hero !max-w-none overflow-hidden p-0" data-slot="title-detail-hero">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-28"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(110deg,rgba(11,10,9,0.92),rgba(11,10,9,0.78),rgba(11,10,9,0.42))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(16,15,13,0.96),rgba(10,10,9,0.98))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[15rem_minmax(0,1fr)]">
                    <div class="sb-poster-frame overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                        @if ($poster)
                            <img
                                src="{{ $poster->url }}"
                                alt="{{ $poster->alt_text ?: $title->name }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="film" class="size-14" />
                            </div>
                        @endif
                    </div>

                    <div class="sb-detail-panel space-y-6 p-5 sm:p-6">
                        <div class="space-y-5">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 space-y-3">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span class="sb-detail-overline">{{ str($title->title_type->value)->headline() }}</span>

                                        @if ($title->release_year)
                                            <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}" class="sb-detail-meta-link">
                                                {{ $title->release_year }}
                                            </a>
                                        @endif

                                        @if ($title->runtime_minutes)
                                            <span class="sb-detail-meta-chip">{{ $title->runtime_minutes }} min</span>
                                        @endif

                                        @if ($title->age_rating)
                                            <span class="sb-detail-meta-chip">{{ $title->age_rating }}</span>
                                        @endif
                                    </div>

                                    <div class="space-y-2">
                                        <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $title->name }}</x-ui.heading>

                                        @if (filled($title->original_name) && $title->original_name !== $title->name)
                                            <x-ui.text class="text-sm text-[#a99f92] dark:text-[#a99f92]">
                                                Original title: {{ $title->original_name }}
                                            </x-ui.text>
                                        @endif

                                        @if (filled($title->tagline))
                                            <x-ui.text class="text-base italic text-[#b8ad9d] dark:text-[#b8ad9d]">
                                                {{ $title->tagline }}
                                            </x-ui.text>
                                        @endif

                                        <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                            {{ $title->plot_outline ?: 'A full plot outline has not been published yet.' }}
                                        </x-ui.text>
                                    </div>
                                </div>

                                <div class="sb-detail-rating-shell">
                                    <div class="sb-detail-rating-label">Screenbase rating</div>
                                    <div class="sb-detail-rating-value">
                                        {{ $title->statistic?->average_rating ? number_format((float) $title->statistic->average_rating, 1) : 'N/A' }}
                                    </div>
                                    <div class="sb-detail-rating-copy">
                                        {{ number_format($ratingCount) }} member ratings
                                    </div>
                                </div>
                            </div>

                            @if ($title->genres->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($title->genres as $genre)
                                        <a href="{{ route('public.genres.show', $genre) }}" class="sb-detail-chip">
                                            {{ $genre->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if ($countries->isNotEmpty() || $languages->isNotEmpty())
                                @if ($isSeriesLike)
                                    <div class="sb-series-hero-facts">
                                        @if ($countries->isNotEmpty())
                                            <div class="sb-series-hero-fact">
                                                <span class="sb-series-hero-fact-label">Origin</span>
                                                <span class="sb-series-hero-fact-value flex flex-wrap gap-3">
                                                    @foreach ($countries as $country)
                                                        <span class="inline-flex items-center gap-2">
                                                            <x-ui.flag type="country" :code="$country" class="size-4" />
                                                            <span>{{ $country }}</span>
                                                        </span>
                                                    @endforeach
                                                </span>
                                            </div>
                                        @endif

                                        @if ($languages->isNotEmpty())
                                            <div class="sb-series-hero-fact">
                                                <span class="sb-series-hero-fact-label">Language</span>
                                                <span class="sb-series-hero-fact-value flex flex-wrap gap-3">
                                                    @foreach ($languages as $language)
                                                        <span class="inline-flex items-center gap-2">
                                                            <x-ui.flag type="language" :code="$language" class="size-4" />
                                                            <span>{{ $language }}</span>
                                                        </span>
                                                    @endforeach
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @if ($countries->isNotEmpty())
                                            <div class="sb-title-fact-row">
                                                <div class="sb-title-fact-label">Origin</div>
                                                <div class="sb-title-fact-value flex flex-wrap gap-3">
                                                    @foreach ($countries as $country)
                                                        <span class="inline-flex items-center gap-2">
                                                            <x-ui.flag type="country" :code="$country" class="size-4" />
                                                            <span>{{ $country }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if ($languages->isNotEmpty())
                                            <div class="sb-title-fact-row">
                                                <div class="sb-title-fact-label">Language</div>
                                                <div class="sb-title-fact-value flex flex-wrap gap-3">
                                                    @foreach ($languages as $language)
                                                        <span class="inline-flex items-center gap-2">
                                                            <x-ui.flag type="language" :code="$language" class="size-4" />
                                                            <span>{{ $language }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4{{ $isSeriesLike ? ' sb-detail-stat-grid--series' : '' }}">
                            @foreach ($heroStats as $heroStat)
                                <div class="sb-detail-stat{{ $isSeriesLike ? ' sb-detail-stat--series' : '' }} p-4">
                                    <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">{{ $heroStat['label'] }}</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ $heroStat['value'] }}</div>
                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $heroStat['copy'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex flex-col gap-3">
                            <div class="flex flex-wrap gap-3">
                                <x-ui.button as="a" href="#title-rating" icon="star" color="amber" class="sb-detail-primary-action">
                                    Rate this title
                                </x-ui.button>
                                <x-ui.button as="a" href="#title-watchlist" variant="outline" color="amber" icon="bookmark" class="sb-detail-secondary-action">
                                    Add to watchlist
                                </x-ui.button>
                                <x-ui.button as="a" href="#title-watch-state" variant="outline" color="amber" icon="check-circle" class="sb-detail-secondary-action">
                                    Mark watched
                                </x-ui.button>
                                <x-ui.button as="a" href="#title-reviews" variant="outline" color="amber" icon="chat-bubble-left-right" class="sb-detail-secondary-action">
                                    Write review
                                </x-ui.button>
                                <x-ui.button as="a" href="#title-lists" variant="outline" color="amber" icon="queue-list" class="sb-detail-secondary-action">
                                    Add to custom list
                                </x-ui.button>
                                <x-ui.modal.trigger :id="$shareModalId">
                                    <x-ui.button type="button" variant="outline" color="amber" icon="share" class="sb-detail-secondary-action">
                                        Share
                                    </x-ui.button>
                                </x-ui.modal.trigger>
                            </div>

                            <div class="flex flex-wrap gap-x-4 gap-y-2">
                                <a href="{{ route('public.titles.cast', $title) }}" class="sb-detail-utility-link">
                                    Full cast
                                </a>
                                <a href="{{ route('public.titles.media', $title) }}" class="sb-detail-utility-link">
                                    Media gallery
                                </a>
                                @can('update', $title)
                                    <a href="{{ route('admin.titles.edit', $title) }}" class="sb-detail-utility-link">
                                        Edit title
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            <div id="title-watchlist">
                <livewire:titles.watchlist-toggle :title="$title" :key="'watchlist-'.$title->id" />
            </div>
            <div id="title-watch-state">
                <livewire:titles.watch-state-panel :title="$title" :key="'watch-state-'.$title->id" />
            </div>
        </div>

        <x-ui.card class="sb-detail-section sb-title-directory-shell !max-w-none">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="space-y-2">
                    <div class="sb-title-directory-kicker">Title dossier</div>
                    <x-ui.heading level="h2" size="lg" class="sb-title-directory-title">Deep discovery map</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-[#b8ad9d] dark:text-[#b8ad9d]">
                        Jump straight to the sections that matter: editorial overview, credits, awards signal, parent guidance, release context, technical data, and connected titles.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($titleDirectory as $directoryItem)
                        <a href="{{ $directoryItem['href'] }}" class="sb-title-directory-link">
                            {{ $directoryItem['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.75fr)]">
        <div class="space-y-6">
            <x-ui.card id="title-storyline" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Storyline</x-ui.heading>
                        @if ($title->synopsis)
                            <x-ui.badge variant="outline" color="neutral" icon="document-text">Long plot available</x-ui.badge>
                        @endif
                    </div>

                    <x-ui.text class="text-neutral-700 dark:text-neutral-200">
                        {{ $title->plot_outline ?: 'A short plot outline has not been published yet.' }}
                    </x-ui.text>

                    @if (filled($title->synopsis))
                        <x-ui.text class="text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                            {{ $title->synopsis }}
                        </x-ui.text>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card id="title-credits" class="sb-detail-section !max-w-none">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Cast</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Principal on-screen performers credited to this title.
                            </x-ui.text>
                        </div>

                        <x-ui.link :href="route('public.titles.cast', $title)" variant="ghost" iconAfter="arrow-right">
                            View full cast
                        </x-ui.link>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($castPreview as $credit)
                            <div class="flex items-start justify-between gap-3 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div>
                                    <div class="font-medium">
                                        <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                            {{ $credit->person->name }}
                                        </a>
                                    </div>
                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $credit->character_name ?: ($credit->credited_as ?: 'Cast credit') }}
                                    </div>
                                </div>

                                @if ($credit->billing_order)
                                    <x-ui.badge variant="outline" color="neutral" icon="hashtag">
                                        #{{ $credit->billing_order }}
                                    </x-ui.badge>
                                @endif
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No cast has been published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h3" size="md">Key crew</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-group">{{ number_format($crewPreview->count()) }} role groups</x-ui.badge>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            @forelse ($crewPreview as $group)
                                <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-medium">{{ $group['role'] }}</div>
                                        @if ($group['department'])
                                            <x-ui.badge variant="outline" color="slate" icon="briefcase">{{ $group['department'] }}</x-ui.badge>
                                        @endif
                                    </div>

                                    <div class="mt-3 space-y-2 text-sm text-neutral-600 dark:text-neutral-300">
                                        @foreach ($group['credits'] as $credit)
                                            <div>
                                                <a href="{{ route('public.people.show', $credit->person) }}" class="font-medium text-neutral-800 hover:opacity-80 dark:text-neutral-100">
                                                    {{ $credit->person->name }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10 md:col-span-2">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="briefcase" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No crew credits are available yet.</x-ui.heading>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-ui.card>

            @if ($isSeriesLike)
                <x-ui.card class="sb-detail-section sb-series-guide-shell !max-w-none" data-slot="series-guide-shell">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Series guide</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Follow the season order, inspect the most recent run, and jump straight to standout episodes.
                                </x-ui.text>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="tv">
                                {{ number_format($title->seasons->count()) }} published seasons
                            </x-ui.badge>
                        </div>

                        @if ($title->seasons->isNotEmpty())
                            <div class="space-y-3" data-slot="series-guide-navigation">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="sb-cast-section-label">Season navigation</div>
                                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                            Jump directly to any season and keep the latest run visually prioritized.
                                        </x-ui.text>
                                    </div>

                                    @if ($latestSeason)
                                        <x-ui.badge variant="outline" color="amber" icon="sparkles">Latest run</x-ui.badge>
                                    @endif
                                </div>

                                <div class="sb-series-guide-nav">
                                @foreach ($title->seasons as $season)
                                    <a
                                        href="{{ route('public.seasons.show', ['series' => $title, 'season' => $season]) }}"
                                        class="sb-series-guide-pill{{ $latestSeason && $season->is($latestSeason) ? ' sb-series-guide-pill--active' : '' }}"
                                    >
                                        <span class="sb-series-guide-pill-title">Season {{ $season->season_number }}</span>
                                        <span class="sb-series-guide-pill-meta">
                                            {{ number_format($season->episodes_count) }} episodes
                                            @if ($season->release_year)
                                                · {{ $season->release_year }}
                                            @endif
                                        </span>
                                        @if ($latestSeason && $season->is($latestSeason))
                                            <span class="sb-series-guide-pill-flag">Latest</span>
                                        @endif
                                    </a>
                                @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($latestSeason)
                            <div class="sb-series-latest-shell" data-slot="series-latest-preview">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <div class="sb-cast-section-label">Latest season overview</div>
                                        <div class="mt-2 text-lg font-semibold sb-series-latest-title">
                                            <a href="{{ route('public.seasons.show', ['series' => $title, 'season' => $latestSeason]) }}" class="hover:opacity-80">
                                                {{ $latestSeason->name }}
                                            </a>
                                        </div>
                                        <div class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ number_format($latestSeason->episodes_count) }} episodes
                                            @if ($latestSeason->release_year)
                                                · {{ $latestSeason->release_year }}
                                            @endif
                                        </div>
                                        @if (filled($latestSeason->summary))
                                            <x-ui.text class="mt-3 max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                                                {{ $latestSeason->summary }}
                                            </x-ui.text>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                                            {{ number_format($latestSeasonEpisodes->count()) }} preview episodes
                                        </x-ui.badge>
                                        <x-ui.link :href="route('public.seasons.show', ['series' => $title, 'season' => $latestSeason])" variant="ghost" iconAfter="arrow-right">
                                            View season
                                        </x-ui.link>
                                    </div>
                                </div>

                                @if ($latestSeasonEpisodes->isNotEmpty())
                                    <div class="sb-series-episode-grid">
                                        @foreach ($latestSeasonEpisodes as $episodeMeta)
                                            <article class="sb-series-episode-card">
                                                <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => $latestSeason, 'episode' => $episodeMeta->title]) }}" class="sb-series-episode-card-media">
                                                    @if ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)
                                                        <img
                                                            src="{{ ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)->url }}"
                                                            alt="{{ ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)->alt_text ?: $episodeMeta->title->name }}"
                                                            class="sb-series-episode-card-image"
                                                            loading="lazy"
                                                        >
                                                    @else
                                                        <div class="sb-series-episode-card-empty">
                                                            <x-ui.icon name="photo" class="size-10" />
                                                        </div>
                                                    @endif
                                                </a>

                                                <div class="space-y-3 p-4">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="sb-episode-meta-pill">Episode {{ $episodeMeta->episode_number }}</span>
                                                        @if ($episodeMeta->aired_at)
                                                            <span class="sb-episode-meta-pill">{{ $episodeMeta->aired_at->format('M j, Y') }}</span>
                                                        @endif
                                                        @if ($episodeMeta->title->runtime_minutes)
                                                            <span class="sb-episode-meta-pill">{{ $episodeMeta->title->runtime_minutes }} min</span>
                                                        @endif
                                                        @if ($episodeMeta->title->statistic?->average_rating)
                                                            <span class="sb-episode-meta-pill sb-episode-meta-pill--rating">
                                                                <x-ui.icon name="star" class="size-4" />
                                                                {{ number_format((float) $episodeMeta->title->statistic->average_rating, 1) }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="space-y-2">
                                                        <x-ui.heading level="h3" size="md" class="sb-series-latest-title">
                                                            <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => $latestSeason, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                                {{ $episodeMeta->title->name }}
                                                            </a>
                                                        </x-ui.heading>
                                                        <x-ui.text class="sb-series-episode-card-copy">
                                                            {{ str($episodeMeta->title->plot_outline ?: 'No public synopsis is available for this episode yet.')->limit(150) }}
                                                        </x-ui.text>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="sb-detail-section sb-series-ranked-shell !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Top-rated episodes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Highest-rated episodes across the whole run, arranged for quick binge decisions.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="star">{{ number_format($topRatedEpisodes->count()) }} ranked</x-ui.badge>
                        </div>

                        @if ($topRatedEpisodes->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($topRatedEpisodes as $episodeMeta)
                                    <article class="sb-series-ranked-episode">
                                        <div class="sb-series-ranked-rank">#{{ $loop->iteration }}</div>

                                        <a
                                            href="{{ route('public.episodes.show', ['series' => $title, 'season' => $episodeMeta->season, 'episode' => $episodeMeta->title]) }}"
                                            class="sb-series-ranked-episode-media"
                                        >
                                            @if ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)
                                                <img
                                                    src="{{ ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)->url }}"
                                                    alt="{{ ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)->alt_text ?: $episodeMeta->title->name }}"
                                                    class="sb-series-ranked-episode-image"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="sb-series-ranked-episode-empty">
                                                    <x-ui.icon name="photo" class="size-8" />
                                                </div>
                                            @endif
                                        </a>

                                        <div class="min-w-0 space-y-2">
                                            <div class="font-medium">
                                                <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => $episodeMeta->season, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                    {{ $episodeMeta->title->name }}
                                                </a>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                <span>Season {{ $episodeMeta->season_number }}, episode {{ $episodeMeta->episode_number }}</span>
                                                @if ($episodeMeta->season)
                                                    <span>{{ $episodeMeta->season->name }}</span>
                                                @endif
                                                @if ($episodeMeta->aired_at)
                                                    <span>{{ $episodeMeta->aired_at->format('M j, Y') }}</span>
                                                @endif
                                            </div>
                                            <x-ui.text class="sb-series-ranked-episode-copy">
                                                {{ str($episodeMeta->title->plot_outline ?: 'No public synopsis is available for this episode yet.')->limit(120) }}
                                            </x-ui.text>
                                        </div>

                                        <x-ui.badge icon="star" color="amber">
                                            {{ number_format((float) ($episodeMeta->title->statistic?->average_rating ?? 0), 1) }}
                                        </x-ui.badge>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="star" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Episode rankings are still coming together.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Scores will appear here once members start rating individual episodes.
                                </x-ui.text>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card id="title-awards" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Awards</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Recognitions, nominations, and credited award moments connected to this title.
                            </x-ui.text>
                        </div>

                        <x-ui.badge variant="outline" color="neutral" icon="trophy">
                            {{ number_format((int) ($title->statistic?->awards_won_count ?? 0)) }} wins
                        </x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($awardHighlights as $awardNomination)
                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">
                                            {{ $awardNomination->awardEvent->award->name }}
                                            @if ($awardNomination->awardEvent->year)
                                                {{ $awardNomination->awardEvent->year }}
                                            @endif
                                        </div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $awardNomination->awardCategory->name }}
                                            @if ($awardNomination->credited_name)
                                                · {{ $awardNomination->credited_name }}
                                            @elseif ($awardNomination->person)
                                                · {{ $awardNomination->person->name }}
                                            @elseif ($awardNomination->episode?->title)
                                                · {{ $awardNomination->episode->title->name }}
                                            @endif
                                        </div>
                                    </div>

                                    <x-ui.badge :color="$awardNomination->is_winner ? 'green' : 'neutral'" variant="outline" :icon="$awardNomination->is_winner ? 'trophy' : 'bookmark'">
                                        {{ $awardNomination->is_winner ? 'Winner' : 'Nominee' }}
                                    </x-ui.badge>
                                </div>

                                @if (filled($awardNomination->details))
                                    <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $awardNomination->details }}
                                    </x-ui.text>
                                @endif
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="trophy" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No awards have been linked yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    The awards record is ready for future import passes and manual curation.
                                </x-ui.text>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card id="title-related" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Related titles</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Sequels, connected stories, shared universes, and editorially linked entries from the wider catalog.
                            </x-ui.text>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-ui.badge variant="outline" color="neutral" icon="film">
                                {{ number_format($relatedTitles->count()) }} links
                            </x-ui.badge>
                            <x-ui.link :href="route('public.titles.metadata', $title)" variant="ghost" iconAfter="arrow-right">
                                Open metadata map
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($relatedTitles->isNotEmpty())
                        <div class="grid gap-4">
                            @foreach ($relatedTitles as $relatedItem)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                            {{ $relatedItem['label'] }}
                                        </div>
                                        @if ($relatedItem['relationship']->weight)
                                            <x-ui.badge variant="outline" color="slate" icon="scale">
                                                Weight {{ $relatedItem['relationship']->weight }}
                                            </x-ui.badge>
                                        @endif
                                    </div>

                                    <x-catalog.title-card :title="$relatedItem['title']" :showSummary="false" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No related titles are linked yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Relationship data can expand here as the title graph deepens.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Media gallery</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Posters, stills, backdrops, and trailer records in a dedicated gallery layout.
                            </x-ui.text>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($galleryAssets->count()) }} assets</x-ui.badge>
                            <x-ui.link :href="route('public.titles.media', $title)" variant="ghost" iconAfter="arrow-right">
                                Open gallery
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($galleryAssets->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($galleryAssets as $mediaAsset)
                                <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                    <img
                                        src="{{ $mediaAsset->url }}"
                                        alt="{{ $mediaAsset->alt_text ?: $title->name }}"
                                        class="aspect-video w-full object-cover"
                                        loading="lazy"
                                    >
                                    @if (filled($mediaAsset->caption))
                                        <div class="border-t border-black/5 px-3 py-2 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                                            {{ $mediaAsset->caption }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No gallery images are published yet.</x-ui.heading>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Videos & trailers</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Trailer, clip, and featurette records attached directly to this title.
                            </x-ui.text>
                        </div>

                        <x-ui.link :href="route('public.trailers.latest')" variant="ghost" iconAfter="arrow-right">
                            Latest trailers
                        </x-ui.link>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($title->titleVideos as $video)
                            <div class="flex flex-wrap items-start justify-between gap-3 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div>
                                    <div class="font-medium">{{ $video->caption ?: str($video->kind->value)->headline() }}</div>
                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ str($video->kind->value)->headline() }}
                                        @if ($video->provider)
                                            · {{ str($video->provider)->headline() }}
                                        @endif
                                        @if ($video->published_at)
                                            · {{ $video->published_at->format('M j, Y') }}
                                        @endif
                                    </div>
                                </div>

                                @if (filled($video->url))
                                    <x-ui.button as="a" :href="$video->url" variant="outline" icon="play" target="_blank" rel="noreferrer">
                                        Open video
                                    </x-ui.button>
                                @endif
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="play-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No videos are published yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Trailer links can appear here as soon as the media feed is connected.
                                </x-ui.text>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <livewire:titles.rating-panel :title="$title" anchorId="title-rating" :key="'rating-'.$title->id" />

            <div id="title-lists">
                <livewire:titles.custom-list-picker :title="$title" :key="'custom-lists-'.$title->id" />
            </div>

            <livewire:contributions.suggestion-form
                contributableType="title"
                :contributableId="$title->id"
                :contributableLabel="$title->name"
                :key="'title-contribution-'.$title->id"
            />

            <x-ui.card id="title-where-to-watch" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Where to watch</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Streaming and purchase availability can land here later without changing the title-page architecture.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="play-circle">
                            Placeholder ready
                        </x-ui.badge>
                    </div>

                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10" data-slot="title-watch-placeholder">
                        <x-ui.empty.media>
                            <x-ui.icon name="play-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">Provider availability is not connected yet.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            The block is in place for future watch-provider data, regional availability, and deep-link actions.
                        </x-ui.text>
                    </x-ui.empty>
                </div>
            </x-ui.card>

            <x-ui.card class="sb-detail-section !max-w-none" data-slot="title-archive-extensions">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Quotes & soundtrack</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Trivia and goofs are already modeled. Quotes and soundtrack records have a reserved extension point here for later feed support.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="musical-note">
                            Future-ready
                        </x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        <div class="sb-title-fact-row">
                            <div class="sb-title-fact-label">Quotes</div>
                            <div class="sb-title-fact-value text-neutral-500 dark:text-neutral-400">Reserved for future import support</div>
                        </div>
                        <div class="sb-title-fact-row">
                            <div class="sb-title-fact-label">Soundtrack</div>
                            <div class="sb-title-fact-value text-neutral-500 dark:text-neutral-400">Reserved for future import support</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Quick facts</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Core identity, release context, origin markers, and production metadata.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="information-circle">
                            {{ number_format($detailItems->count()) }} facts
                        </x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($detailItems as $item)
                            <div class="sb-title-fact-row">
                                <div class="sb-title-fact-label">{{ $item['label'] }}</div>
                                <div class="sb-title-fact-value">{{ $item['value'] }}</div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="information-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Detailed metadata is still being curated.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card id="title-keywords" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Keywords</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Search cues, thematic hooks, and discovery language attached to this title.
                            </x-ui.text>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.badge variant="outline" color="neutral" icon="tag">
                                {{ number_format($keywordItems->count()) }} cues
                            </x-ui.badge>
                            <x-ui.link :href="route('public.titles.metadata', $title)" variant="ghost" iconAfter="arrow-right">
                                Open metadata map
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($keywordItems->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($keywordItems as $keyword)
                                <a href="{{ route('public.search', ['q' => $keyword]) }}">
                                    <x-ui.badge variant="outline" color="neutral" icon="magnifying-glass">{{ $keyword }}</x-ui.badge>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="tag" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Keyword discovery is still thin.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Search keywords will appear here as the editorial and import layers deepen.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card id="title-release-dates" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Release dates</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Market-by-market launch context and the earliest confirmed release windows on file.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="calendar-days">
                            {{ number_format($releaseDateItems->count()) }} dates
                        </x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($releaseDateItems as $releaseDateItem)
                            <div class="sb-title-fact-row">
                                <div class="sb-title-fact-label">{{ $releaseDateItem['country'] }}</div>
                                <div class="sb-title-fact-value">{{ $releaseDateItem['date'] }}</div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="calendar-days" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No release history is published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card id="title-technical-specs" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Technical specs</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Format, runtime, serialized footprint, and core catalog measurements.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="cog-6-tooth">
                            {{ number_format($technicalSpecItems->count()) }} specs
                        </x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($technicalSpecItems as $item)
                            <div class="sb-title-fact-row">
                                <div class="sb-title-fact-label">{{ $item['label'] }}</div>
                                <div class="sb-title-fact-value">{{ $item['value'] }}</div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="cog-6-tooth" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Technical specs are not available yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card id="title-parents-guide" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Parents guide</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Certification context, advisory signal, and spoiler-aware guidance for families and cautious viewers.
                            </x-ui.text>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($title->age_rating)
                                <x-ui.badge color="amber" icon="shield-check">{{ $title->age_rating }}</x-ui.badge>
                            @endif
                            <x-ui.link :href="route('public.titles.parents-guide', $title)" variant="ghost" iconAfter="arrow-right">
                                Open full guide
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($certificateItems->isNotEmpty())
                        <div class="grid gap-3">
                            @foreach ($certificateItems as $certificateItem)
                                <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="font-medium">{{ $certificateItem['rating'] }}</div>
                                        @if ($certificateItem['country'])
                                            <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ $certificateItem['country'] }}</div>
                                        @endif
                                    </div>
                                    @if ($certificateItem['attributes'])
                                        <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $certificateItem['attributes'] }}
                                        </x-ui.text>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($parentGuideItems->isNotEmpty())
                        <div class="grid gap-3">
                            @foreach ($parentGuideItems as $parentGuideItem)
                                <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="font-medium">{{ $parentGuideItem['category'] }}</div>
                                        @if ($parentGuideItem['severity'])
                                            <x-ui.badge variant="outline" :color="$parentGuideItem['severityColor']" icon="exclamation-triangle">
                                                {{ $parentGuideItem['severity'] }}
                                            </x-ui.badge>
                                        @endif
                                    </div>
                                    <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $parentGuideItem['text'] }}
                                    </x-ui.text>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($parentGuideSpoilers->isNotEmpty())
                        <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Spoiler notes</div>
                            <div class="mt-3 space-y-2">
                                @foreach ($parentGuideSpoilers as $spoiler)
                                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $spoiler }}
                                    </x-ui.text>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($certificateItems->isEmpty() && $parentGuideItems->isEmpty() && $parentGuideSpoilers->isEmpty())
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="shield-check" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Parents guide detail is still pending.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                The page is ready for advisory imports, but this title does not yet carry structured guidance beyond its certification.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card id="title-box-office" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Box office</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Revenue milestones and theatrical run benchmarks when a title carries commercial reporting.
                            </x-ui.text>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.badge variant="outline" color="neutral" icon="banknotes">
                                {{ number_format($boxOfficeItems->count()) }} entries
                            </x-ui.badge>

                            <x-ui.link :href="route('public.titles.box-office', $title)" variant="ghost" iconAfter="arrow-right">
                                Open full report
                            </x-ui.link>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($boxOfficeItems as $boxOfficeItem)
                            <div class="sb-title-fact-row">
                                <div class="sb-title-fact-label">{{ $boxOfficeItem['label'] }}</div>
                                <div class="sb-title-fact-value">{{ $boxOfficeItem['value'] }}</div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="banknotes" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Box office reporting is not published yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Commercial reporting will appear here once title-specific grosses are available in the import feed.
                                </x-ui.text>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Ratings breakdown</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Audience score distribution, presented in a compact vertical scan.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="chart-bar">
                            {{ number_format($ratingCount) }} ratings
                        </x-ui.badge>
                    </div>

                    @if ($ratingCount > 0)
                        <div class="space-y-3">
                            @foreach ($ratingsBreakdown as $bucket)
                                <div class="grid grid-cols-[2.5rem_minmax(0,1fr)_3rem] items-center gap-3">
                                    <div class="text-sm font-medium text-neutral-700 dark:text-neutral-200">{{ $bucket['score'] }}</div>
                                    <div class="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                                        <div
                                            class="h-full rounded-full bg-amber-500 dark:bg-amber-400"
                                            style="width: {{ $bucket['count'] > 0 ? max(8, (int) round(($bucket['count'] / $maxBreakdownCount) * 100)) : 0 }}%;"
                                        ></div>
                                    </div>
                                    <div class="text-right text-sm text-neutral-500 dark:text-neutral-400">{{ $bucket['count'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="chart-bar" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Not enough ratings yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                The score distribution will appear once audience ratings start arriving.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card id="title-trivia" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Trivia</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Behind-the-scenes notes and fan-favorite production facts, kept compact on the title page and expanded on the dedicated archive.
                            </x-ui.text>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">
                                {{ $triviaTotalCount > 0 ? number_format($triviaTotalCount).' notes' : 'Reserved' }}
                            </x-ui.badge>
                            <x-ui.link :href="route('public.titles.trivia', $title)" variant="ghost" iconAfter="arrow-right">
                                Open trivia dossier
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($triviaItems->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($triviaItems->take(3) as $triviaItem)
                                <article class="sb-fact-card sb-fact-card--compact sb-fact-card--trivia">
                                    <div class="sb-fact-card-topline">
                                        <div class="sb-fact-card-kicker">Trivia note {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($triviaItem['scoreLabel'])
                                                <span class="sb-fact-interest sb-fact-interest--{{ $triviaItem['scoreTone'] }}">{{ $triviaItem['scoreLabel'] }}</span>
                                            @endif
                                            @if ($triviaItem['isSpoiler'])
                                                <span class="sb-fact-spoiler">Spoiler</span>
                                            @endif
                                        </div>
                                    </div>

                                    <x-ui.text class="sb-fact-card-copy">{{ $triviaItem['text'] }}</x-ui.text>
                                </article>
                            @endforeach

                            @if ($triviaTotalCount > $triviaItems->take(3)->count())
                                <div class="sb-fact-card-note">
                                    Showing {{ number_format($triviaItems->take(3)->count()) }} of {{ number_format($triviaTotalCount) }} trivia notes. Open the full page for the complete archive.
                                </div>
                            @endif
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Trivia has not been published for this title yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                This module is intentionally present so title-specific trivia can land without changing the page architecture.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card id="title-goofs" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Goofs</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Continuity slips and production mistakes, previewed here with the full archive separated into its own cleaner tab on the dedicated page.
                            </x-ui.text>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.badge variant="outline" color="neutral" icon="exclamation-circle">
                                {{ $goofTotalCount > 0 ? number_format($goofTotalCount).' notes' : 'Reserved' }}
                            </x-ui.badge>
                            <x-ui.link :href="route('public.titles.trivia', $title)" variant="ghost" iconAfter="arrow-right">
                                Open trivia dossier
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($goofItems->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($goofItems->take(3) as $goofItem)
                                <article class="sb-fact-card sb-fact-card--compact sb-fact-card--goof">
                                    <div class="sb-fact-card-topline">
                                        <div class="sb-fact-card-kicker">Goof record {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($goofItem['scoreLabel'])
                                                <span class="sb-fact-interest sb-fact-interest--{{ $goofItem['scoreTone'] }}">{{ $goofItem['scoreLabel'] }}</span>
                                            @endif
                                            @if ($goofItem['isSpoiler'])
                                                <span class="sb-fact-spoiler">Spoiler</span>
                                            @endif
                                        </div>
                                    </div>

                                    <x-ui.text class="sb-fact-card-copy">{{ $goofItem['text'] }}</x-ui.text>
                                </article>
                            @endforeach

                            @if ($goofTotalCount > $goofItems->take(3)->count())
                                <div class="sb-fact-card-note">
                                    Showing {{ number_format($goofItems->take(3)->count()) }} of {{ number_format($goofTotalCount) }} goof notes. Open the full page for the complete archive.
                                </div>
                            @endif
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="exclamation-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Goofs are not available for this title yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                The layout keeps a dedicated place for goof records instead of hiding them behind a generic extension bucket.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </section>

    <section id="title-reviews" class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <x-ui.heading level="h2" size="lg">Reviews</x-ui.heading>
                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                    Audience reactions, spoiler flags, and moderation-linked reporting for this title.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.reviews.latest')" variant="ghost" iconAfter="arrow-right">
                Latest reviews
            </x-ui.link>
        </div>

        <livewire:titles.review-composer :title="$title" anchorId="title-review" :key="'review-'.$title->id" />
        <livewire:reviews.title-review-list :title="$title" :key="'title-reviews-'.$title->id" />
    </section>

    <x-ui.modal
        :id="$shareModalId"
        heading="Share title"
        description="Copy the canonical title URL and send it anywhere."
        width="lg"
    >
        <div class="space-y-4">
            <x-ui.field>
                <x-ui.label>Canonical URL</x-ui.label>
                <x-ui.input :value="$shareUrl" copyable readonly />
            </x-ui.field>

            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                Shared links resolve to the canonical public title page for {{ $title->name }}.
            </x-ui.text>
        </div>
    </x-ui.modal>
@endsection
