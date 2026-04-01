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
    @php
        $episodeCount = $episodeRows->count();
    @endphp

    <section class="space-y-6">
        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $series->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-20"
                    >
                    <div class="absolute inset-0 bg-linear-to-r from-white via-white/95 to-white/85 dark:from-neutral-950 dark:via-neutral-950/95 dark:to-neutral-950/85"></div>
                @else
                    <div class="absolute inset-0 bg-linear-to-br from-neutral-100 via-white to-neutral-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[14rem_minmax(0,1fr)]">
                    <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
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

                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="outline" icon="tv">{{ $series->name }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">Season {{ $season->season_number }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($episodeCount) }} episodes</x-ui.badge>
                                @if ($season->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $season->release_year]) }}">
                                        <x-ui.badge variant="outline" color="neutral" icon="calendar-days">{{ $season->release_year }}</x-ui.badge>
                                    </a>
                                @endif
                                @if ($airedRangeLabel)
                                    <x-ui.badge variant="outline" color="neutral" icon="calendar-days">{{ $airedRangeLabel }}</x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <x-ui.heading level="h1" size="xl">{{ $season->name }}</x-ui.heading>
                                <x-ui.text class="max-w-4xl text-base text-neutral-700 dark:text-neutral-200">
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
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                    <x-ui.icon name="rectangle-stack" class="size-4" />
                                    <span>Season episodes</span>
                                </div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format($episodeCount) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Published in the public guide</div>
                            </div>
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                    <x-ui.icon name="star" class="size-4" />
                                    <span>Series audience score</span>
                                </div>
                                <div class="mt-2 text-2xl font-semibold">
                                    {{ $series->statistic?->average_rating ? number_format((float) $series->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ number_format((int) ($series->statistic?->rating_count ?? 0)) }} total series ratings
                                </div>
                            </div>
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                    <x-ui.icon name="list-bullet" class="size-4" />
                                    <span>Season navigation</span>
                                </div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format($seasonNavigation->count()) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Published seasons in this run</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                            <x-ui.icon name="list-bullet" class="size-5 text-neutral-500 dark:text-neutral-400" />
                            <span>Season navigation</span>
                        </x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Jump between seasons without losing the current series context.
                        </x-ui.text>
                    </div>

                    <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($seasonNavigation->count()) }} seasons</x-ui.badge>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($seasonNavigation as $navigationSeason)
                        <a href="{{ route('public.seasons.show', ['series' => $series, 'season' => $navigationSeason]) }}">
                            <x-ui.badge
                                variant="outline"
                                icon="rectangle-stack"
                                :color="$navigationSeason->is($season) ? 'green' : 'neutral'"
                            >
                                Season {{ $navigationSeason->season_number }}
                            </x-ui.badge>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div class="space-y-6">
                <livewire:seasons.watch-progress-panel :series="$series" :season="$season" :key="'season-progress-'.$season->id" />

                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                    <x-ui.icon name="list-bullet" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                    <span>Episode guide</span>
                                </x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Ratings, summaries, release timing, and quick access for every published episode in this season.
                                </x-ui.text>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($episodeCount) }} episodes</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($episodeRows as $episodeMeta)
                                @php
                                    $episodeTitle = $episodeMeta->title;
                                    $watchState = $watchStatesByTitle->get($episodeMeta->title_id);
                                    $watchStateColor = match ($watchState) {
                                        'completed' => 'green',
                                        'watching' => 'slate',
                                        'paused', 'dropped' => 'amber',
                                        default => 'neutral',
                                    };
                                @endphp

                                <x-ui.card class="!max-w-none">
                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="space-y-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">
                                                        S{{ str_pad((string) $episodeMeta->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad((string) $episodeMeta->episode_number, 2, '0', STR_PAD_LEFT) }}
                                                    </x-ui.badge>
                                                    @if ($episodeMeta->aired_at)
                                                        <x-ui.badge variant="outline" color="neutral" icon="calendar-days">
                                                            {{ $episodeMeta->aired_at->format('M j, Y') }}
                                                        </x-ui.badge>
                                                    @endif
                                                    @if ($episodeTitle->runtime_minutes)
                                                        <x-ui.badge variant="outline" color="neutral" icon="clock">{{ $episodeTitle->runtime_minutes }} min</x-ui.badge>
                                                    @endif
                                                    @if ($episodeTitle->statistic?->average_rating)
                                                        <x-ui.badge icon="star" color="amber">
                                                            {{ number_format((float) $episodeTitle->statistic->average_rating, 1) }}/10
                                                        </x-ui.badge>
                                                    @endif
                                                    @auth
                                                        @if ($watchState)
                                                            <x-ui.badge
                                                                variant="outline"
                                                                :color="$watchStateColor"
                                                                :icon="match ($watchState) {
                                                                    'completed' => 'check-circle',
                                                                    'watching' => 'play-circle',
                                                                    'paused' => 'pause-circle',
                                                                    'dropped' => 'x-circle',
                                                                    default => 'bookmark',
                                                                }"
                                                            >
                                                                {{ str($watchState)->headline() }}
                                                            </x-ui.badge>
                                                        @endif
                                                    @endauth
                                                </div>

                                                <div class="space-y-2">
                                                    <x-ui.heading level="h3" size="md">
                                                        <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeTitle]) }}" class="hover:opacity-80">
                                                            {{ $episodeTitle->name }}
                                                        </a>
                                                    </x-ui.heading>
                                                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                        {{ $episodeTitle->plot_outline ?: 'No public synopsis is available for this episode yet.' }}
                                                    </x-ui.text>
                                                </div>

                                                @if ($episodeTitle->credits->isNotEmpty())
                                                    <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                                        <span class="inline-flex items-center gap-1.5 font-medium text-neutral-700 dark:text-neutral-200">
                                                            <x-ui.icon name="users" class="size-4 text-neutral-500 dark:text-neutral-400" />
                                                            <span>Guest cast:</span>
                                                        </span>
                                                        @foreach ($episodeTitle->credits->take(3) as $credit)
                                                            <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                                {{ $credit->person->name }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            <x-ui.link :href="route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeTitle])" variant="ghost" iconAfter="arrow-right">
                                                View episode
                                            </x-ui.link>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-3 text-sm text-neutral-500 dark:text-neutral-400">
                                            <div class="inline-flex items-center gap-1.5">
                                                <x-ui.icon name="star" class="size-4" />
                                                <span>{{ number_format((int) ($episodeTitle->statistic?->rating_count ?? 0)) }} ratings</span>
                                            </div>
                                            <div class="inline-flex items-center gap-1.5">
                                                <x-ui.icon name="chat-bubble-left-right" class="size-4" />
                                                <span>{{ number_format((int) ($episodeTitle->statistic?->review_count ?? 0)) }} reviews</span>
                                            </div>
                                            <div class="inline-flex items-center gap-1.5">
                                                <x-ui.icon name="bookmark" class="size-4" />
                                                <span>{{ number_format((int) ($episodeTitle->statistic?->watchlist_count ?? 0)) }} watchlists</span>
                                            </div>
                                        </div>
                                    </div>
                                </x-ui.card>
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
