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
        <x-ui.card data-slot="season-browser-hero" class="sb-detail-hero !max-w-none overflow-hidden p-0">
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
                    <div class="overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
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

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="sb-detail-overline">Season browser</span>
                                <x-ui.badge variant="outline" color="neutral" icon="tv">{{ $series->name }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">{{ $season->name }}</x-ui.badge>
                                @if ($season->release_year)
                                    <x-ui.badge variant="outline" color="neutral" icon="calendar-days">{{ $season->release_year }}</x-ui.badge>
                                @endif
                                @if ($airedRangeLabel)
                                    <x-ui.badge variant="outline" color="neutral" icon="clock">{{ $airedRangeLabel }}</x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $series->name }} — {{ $season->name }}</x-ui.heading>
                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    {{ $season->summary ?: 'Episode order, release chronology, and audience signals for this season.' }}
                                </x-ui.text>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sb-season-browser-stat">
                                <div class="sb-cast-summary-label">Episodes</div>
                                <div class="sb-cast-summary-value">{{ number_format($episodeCount) }}</div>
                                <div class="sb-cast-summary-copy">Published in this season guide.</div>
                            </div>
                            <div class="sb-season-browser-stat">
                                <div class="sb-cast-summary-label">Series score</div>
                                <div class="sb-cast-summary-value">
                                    {{ $series->statistic?->average_rating ? number_format((float) $series->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="sb-cast-summary-copy">{{ number_format((int) ($series->statistic?->rating_count ?? 0)) }} total votes</div>
                            </div>
                            <div class="sb-season-browser-stat">
                                <div class="sb-cast-summary-label">Average runtime</div>
                                <div class="sb-cast-summary-value">
                                    {{ $currentSeasonRuntimeAverage ? number_format((float) $currentSeasonRuntimeAverage, 0) : 'N/A' }}
                                </div>
                                <div class="sb-cast-summary-copy">Minutes per episode.</div>
                            </div>
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
                </div>
            </div>
        </x-ui.card>

        @if ($seasonNavigation->isNotEmpty())
            <x-ui.card data-slot="season-browser-navigation" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Season navigation</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Jump between seasons without leaving the current series.
                        </x-ui.text>
                    </div>

                    <div class="sb-season-nav-row">
                        @foreach ($seasonNavigation as $navigationSeason)
                            <a
                                href="{{ route('public.seasons.show', ['series' => $series, 'season' => $navigationSeason]) }}"
                                class="sb-season-nav-pill{{ $navigationSeason->season_number === $season->season_number ? ' sb-season-nav-pill--active' : '' }}"
                            >
                                <span class="sb-season-nav-pill-title">Season {{ $navigationSeason->season_number }}</span>
                                <span class="sb-season-nav-pill-meta">{{ number_format((int) $navigationSeason->episodes_count) }} episodes</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div class="space-y-6">
                <x-ui.card data-slot="season-browser-episodes" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Episode browser</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Canonical episode pages built from the imported season and episode index.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($episodeCount) }} episodes</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($episodeRows as $episodeMeta)
                                <article class="rounded-[1.2rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                    <div class="grid gap-4 sm:grid-cols-[9rem_minmax(0,1fr)]">
                                        <a
                                            href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title]) }}"
                                            class="overflow-hidden rounded-[1rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800"
                                        >
                                            @if ($episodeMeta->title->preferredDisplayImage())
                                                <img
                                                    src="{{ $episodeMeta->title->preferredDisplayImage()->url }}"
                                                    alt="{{ $episodeMeta->title->preferredDisplayImage()->alt_text ?: $episodeMeta->title->name }}"
                                                    class="aspect-video w-full object-cover"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex aspect-video items-center justify-center text-neutral-500 dark:text-neutral-400">
                                                    <x-ui.icon name="photo" class="size-8" />
                                                </div>
                                            @endif
                                        </a>

                                        <div class="space-y-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">Episode {{ $episodeMeta->episode_number }}</x-ui.badge>
                                                @if ($episodeMeta->aired_at)
                                                    <x-ui.badge variant="outline" color="neutral" icon="calendar-days">{{ $episodeMeta->aired_at->format('M j, Y') }}</x-ui.badge>
                                                @endif
                                                @if ($episodeMeta->title->displayAverageRating())
                                                    <x-ui.badge color="amber" icon="star">{{ number_format($episodeMeta->title->displayAverageRating(), 1) }}</x-ui.badge>
                                                @endif
                                            </div>

                                            <div class="space-y-2">
                                                <x-ui.heading level="h3" size="md">
                                                    <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                        {{ $episodeMeta->title->name }}
                                                    </a>
                                                </x-ui.heading>
                                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                    {{ $episodeMeta->title->summaryText() ?: 'No public synopsis is available for this episode yet.' }}
                                                </x-ui.text>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                                                <span>{{ number_format((int) ($episodeMeta->title->statistic?->rating_count ?? 0)) }} votes</span>
                                                <x-ui.link :href="route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title])" variant="ghost" iconAfter="arrow-right">
                                                    View episode
                                                </x-ui.link>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="rectangle-stack" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No episodes are linked to this season yet.</x-ui.heading>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                @if ($topRatedEpisodes->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Top-rated episodes this season</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Fast access to the best-rated episodes in this season.
                                </x-ui.text>
                            </div>

                            <div class="space-y-3">
                                @foreach ($topRatedEpisodes as $episodeMeta)
                                    <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeMeta->title]) }}" class="flex items-center justify-between gap-3 rounded-[1rem] border border-black/5 bg-white/70 px-4 py-3 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $episodeMeta->title->name }}</div>
                                            <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Episode {{ $episodeMeta->episode_number }}</div>
                                        </div>
                                        <x-ui.badge color="amber" icon="star">
                                            {{ number_format((float) ($episodeMeta->title->statistic?->average_rating ?? 0), 1) }}
                                        </x-ui.badge>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif
            </div>
        </div>
    </section>
@endsection
