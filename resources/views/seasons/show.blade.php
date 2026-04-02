@extends('layouts.public')

@section('title', $season->meta_title ?: ($season->name.' · '.$series->name))
@section('meta_description', $season->meta_description ?: ($season->summary ?: 'Browse episode records for '.$season->name.' of '.$series->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.series.index')">TV Shows</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $series)">{{ $series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $season->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-detail-hero sb-season-browser-hero !max-w-none overflow-hidden p-0" data-slot="season-browser-hero">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $series->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-24"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(112deg,rgba(10,10,9,0.95),rgba(10,10,9,0.84),rgba(10,10,9,0.54))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(12,11,10,0.98),rgba(10,10,9,0.96))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[14rem_minmax(0,1fr)]">
                    <div class="sb-season-browser-poster overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                        @if ($poster)
                            <img
                                src="{{ $poster->url }}"
                                alt="{{ $poster->alt_text ?: $series->name }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="tv" class="size-14" />
                            </div>
                        @endif
                    </div>

                    <div class="sb-season-browser-panel space-y-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="sb-season-browser-kicker">Season browser</span>
                                <span class="sb-cast-meta-item">{{ $series->name }}</span>
                                <span class="sb-cast-meta-item">Season {{ $season->season_number }}</span>
                                <span class="sb-cast-meta-item">{{ number_format($episodeCount) }} episodes</span>
                                @if ($season->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $season->release_year]) }}" class="sb-cast-meta-item">
                                        {{ $season->release_year }}
                                    </a>
                                @endif
                                @if ($airedRangeLabel)
                                    <span class="sb-cast-meta-item">{{ $airedRangeLabel }}</span>
                                @endif
                                @if ($series->statistic?->average_rating)
                                    <span class="sb-cast-meta-item sb-cast-meta-item--rating">
                                        <x-ui.icon name="star" class="size-4" />
                                        {{ number_format((float) $series->statistic->average_rating, 1) }}
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $series->name }} — {{ $season->name }}</x-ui.heading>
                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    {{ $season->summary ?: 'Episode order, release chronology, and audience signals for this season.' }}
                                </x-ui.text>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <x-ui.button as="a" :href="route('public.titles.show', $series)" variant="outline" icon="tv">
                                    Series page
                                </x-ui.button>
                                @if ($previousSeason)
                                    <x-ui.button as="a" :href="route('public.seasons.show', ['series' => $series, 'season' => $previousSeason])" variant="ghost" icon="arrow-left">
                                        Previous season
                                    </x-ui.button>
                                @endif
                                @if ($nextSeason)
                                    <x-ui.button as="a" :href="route('public.seasons.show', ['series' => $series, 'season' => $nextSeason])" variant="ghost" icon="arrow-right">
                                        Next season
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sb-season-browser-stat">
                                <div class="sb-cast-summary-label">Season episodes</div>
                                <div class="sb-cast-summary-value">{{ number_format($episodeCount) }}</div>
                                <div class="sb-cast-summary-copy">Published in this binge guide.</div>
                            </div>
                            <div class="sb-season-browser-stat">
                                <div class="sb-cast-summary-label">Series audience score</div>
                                <div class="sb-cast-summary-value">
                                    {{ $series->statistic?->average_rating ? number_format((float) $series->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="sb-cast-summary-copy">{{ number_format((int) ($series->statistic?->rating_count ?? 0)) }} total series ratings</div>
                            </div>
                            <div class="sb-season-browser-stat">
                                <div class="sb-cast-summary-label">Average runtime</div>
                                <div class="sb-cast-summary-value">
                                    {{ $currentSeasonRuntimeAverage ? number_format((float) $currentSeasonRuntimeAverage, 0) : 'N/A' }}
                                </div>
                                <div class="sb-cast-summary-copy">Minutes per episode in this run.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-detail-section sb-season-nav-shell !max-w-none" data-slot="season-browser-navigation">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="sb-cast-section-label">Season navigation</div>
                        <x-ui.heading level="h2" size="lg" class="mt-2">Choose a season</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Jump between seasons without leaving the current series and keep the episode browser anchored below.
                        </x-ui.text>
                    </div>

                    <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($seasonNavigation->count()) }} seasons</x-ui.badge>
                </div>

                <div class="sb-season-nav-row">
                    @foreach ($seasonNavigation as $navigationSeason)
                        <a
                            href="{{ route('public.seasons.show', ['series' => $series, 'season' => $navigationSeason]) }}"
                            class="sb-season-nav-pill{{ $navigationSeason->is($season) ? ' sb-season-nav-pill--active' : '' }}"
                        >
                            <span class="sb-season-nav-pill-title">Season {{ $navigationSeason->season_number }}</span>
                            <span class="sb-season-nav-pill-meta">
                                {{ number_format((int) $navigationSeason->episodes_count) }} episodes
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div class="space-y-6">
                <livewire:seasons.watch-progress-panel :series="$series" :season="$season" :key="'season-progress-'.$season->id" />

                <x-ui.card class="sb-detail-section sb-episode-browser-shell !max-w-none" data-slot="season-browser-episodes">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="sb-cast-section-label">Episode browser</div>
                                <x-ui.heading level="h2" size="lg" class="mt-2">Episode list</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Still images, release timing, ratings, and short synopsis copy arranged for fast binge planning.
                                </x-ui.text>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($episodeCount) }} episodes</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($episodeRows as $episodeMeta)
                                <article class="sb-episode-row">
                                    <a
                                        href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title]) }}"
                                        class="sb-episode-media"
                                    >
                                        @if ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)
                                            <img
                                                src="{{ ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)->url }}"
                                                alt="{{ ($episodeMeta->title->preferredDisplayImage() ?? $backdrop ?? $poster)->alt_text ?: $episodeMeta->title->name }}"
                                                class="sb-episode-media-image"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="sb-episode-media-empty">
                                                <x-ui.icon name="photo" class="size-10" />
                                            </div>
                                        @endif
                                    </a>

                                    <div class="min-w-0 space-y-3">
                                        <div class="sb-episode-primary-bar">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="sb-episode-meta-pill sb-episode-meta-pill--number">
                                                    Episode {{ $episodeMeta->episode_number }}
                                                </span>
                                                @if ($episodeMeta->title->statistic?->average_rating)
                                                    <span class="sb-episode-meta-pill sb-episode-meta-pill--rating">
                                                        <x-ui.icon name="star" class="size-4" />
                                                        {{ number_format((float) $episodeMeta->title->statistic->average_rating, 1) }}
                                                    </span>
                                                @endif
                                                @auth
                                                    @if ($watchStatesByTitle->has($episodeMeta->title_id))
                                                        <x-ui.badge
                                                            variant="outline"
                                                            :color="$watchStatesByTitle->get($episodeMeta->title_id)->color()"
                                                            :icon="$watchStatesByTitle->get($episodeMeta->title_id)->icon()"
                                                        >
                                                            {{ $watchStatesByTitle->get($episodeMeta->title_id)->label() }}
                                                        </x-ui.badge>
                                                    @endif
                                                @endauth
                                            </div>

                                            <x-ui.link
                                                :href="route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title])"
                                                variant="ghost"
                                                iconAfter="arrow-right"
                                                class="sb-episode-link"
                                            >
                                                View episode
                                            </x-ui.link>
                                        </div>

                                        <div class="space-y-2">
                                            <x-ui.heading level="h3" size="md" class="sb-episode-title">
                                                <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                    {{ $episodeMeta->title->name }}
                                                </a>
                                            </x-ui.heading>

                                            <div class="sb-episode-facts">
                                                @if ($episodeMeta->aired_at)
                                                    <span>{{ $episodeMeta->aired_at->format('M j, Y') }}</span>
                                                @endif
                                                @if ($episodeMeta->title->runtime_minutes)
                                                    <span>{{ $episodeMeta->title->runtime_minutes }} min</span>
                                                @endif
                                                <span>{{ number_format((int) ($episodeMeta->title->statistic?->rating_count ?? 0)) }} ratings</span>
                                            </div>

                                            <x-ui.text class="sb-episode-copy">
                                                {{ $episodeMeta->title->plot_outline ?: 'No public synopsis is available for this episode yet.' }}
                                            </x-ui.text>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                                            <div class="flex flex-wrap gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <x-ui.icon name="star" class="size-4" />
                                                    <span>{{ number_format((int) ($episodeMeta->title->statistic?->rating_count ?? 0)) }} ratings</span>
                                                </span>
                                                <span class="inline-flex items-center gap-1.5">
                                                    <x-ui.icon name="chat-bubble-left-right" class="size-4" />
                                                    <span>{{ number_format((int) ($episodeMeta->title->statistic?->review_count ?? 0)) }} reviews</span>
                                                </span>
                                                <span class="inline-flex items-center gap-1.5">
                                                    <x-ui.icon name="bookmark" class="size-4" />
                                                    <span>{{ number_format((int) ($episodeMeta->title->statistic?->watchlist_count ?? 0)) }} watchlists</span>
                                                </span>
                                            </div>

                                            @if ($episodeMeta->title->credits->isNotEmpty())
                                                <div class="sb-episode-guest-cast">
                                                    <span class="font-medium text-neutral-700 dark:text-neutral-200">Guest cast:</span>
                                                    @foreach ($episodeMeta->title->credits->take(3) as $credit)
                                                        <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                            {{ $credit->person->name }}
                                                        </a>@if (! $loop->last), @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="rectangle-stack" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No published episodes are attached to this season yet.</x-ui.heading>
                                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                        The season shell exists, but episode records have not been published.
                                    </x-ui.text>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="star" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Top-rated episodes this season</span>
                            </x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral" icon="star">{{ number_format($topRatedEpisodes->count()) }} ranked</x-ui.badge>
                        </div>

                        @if ($topRatedEpisodes->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($topRatedEpisodes as $episodeMeta)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium">
                                                    <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                        {{ $episodeMeta->title->name }}
                                                    </a>
                                                </div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    Episode {{ $episodeMeta->episode_number }}
                                                    @if ($episodeMeta->aired_at)
                                                        · {{ $episodeMeta->aired_at->format('M j, Y') }}
                                                    @endif
                                                </div>
                                            </div>

                                            <x-ui.badge icon="star" color="amber">
                                                {{ number_format((float) ($episodeMeta->title->statistic?->average_rating ?? 0), 1) }}
                                            </x-ui.badge>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="star" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Episode scores are still building.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Top-rated episode rankings appear as soon as members start scoring the season.
                                </x-ui.text>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                            <x-ui.icon name="information-circle" class="size-5 text-neutral-500 dark:text-neutral-400" />
                            <span>Season context</span>
                        </x-ui.heading>

                        <div class="grid gap-3">
                            <div class="flex items-start justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Series</div>
                                <a href="{{ route('public.titles.show', $series) }}" class="text-right text-sm font-medium text-neutral-800 hover:opacity-80 dark:text-neutral-100">
                                    {{ $series->name }}
                                </a>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Season summary</div>
                                <div class="text-right text-sm text-neutral-800 dark:text-neutral-100">{{ $season->summary ?: 'Editorial summary pending.' }}</div>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Airing window</div>
                                <div class="text-right text-sm text-neutral-800 dark:text-neutral-100">{{ $airedRangeLabel ?: 'Dates still being curated.' }}</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </section>
@endsection
