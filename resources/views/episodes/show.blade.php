@extends('layouts.public')

@section('title', $episode->meta_title ?: $episode->name)
@section('meta_description', $episode->meta_description ?: ($episode->plot_outline ?: 'Browse cast, metadata, and gallery items for '.$episode->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.series.index')">TV Shows</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $series)">{{ $series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.seasons.show', ['series' => $series, 'season' => $season])">{{ $season->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $episode->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card data-slot="episode-detail-hero" class="sb-detail-hero !max-w-none overflow-hidden p-0">
            <div class="relative">
                @if ($still)
                    <img
                        src="{{ $still->url }}"
                        alt="{{ $still->alt_text ?: $episode->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-24"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(110deg,rgba(11,10,9,0.92),rgba(11,10,9,0.8),rgba(11,10,9,0.45))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(16,15,13,0.96),rgba(10,10,9,0.98))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
                    <div class="overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                        @if ($still)
                            <img
                                src="{{ $still->url }}"
                                alt="{{ $still->alt_text ?: $episode->name }}"
                                class="aspect-video w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-video items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="tv" class="size-14" />
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="sb-detail-overline">Episode dossier</span>
                                <x-ui.badge variant="outline" color="neutral" icon="tv">{{ $series->name }}</x-ui.badge>
                                @if ($episodeMeta)
                                    <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">
                                        S{{ str_pad((string) $episodeMeta->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad((string) $episodeMeta->episode_number, 2, '0', STR_PAD_LEFT) }}
                                    </x-ui.badge>
                                @endif
                                @if ($episodeMeta?->aired_at)
                                    <x-ui.badge variant="outline" color="neutral" icon="calendar-days">{{ $episodeMeta->aired_at->format('M j, Y') }}</x-ui.badge>
                                @endif
                                @if ($episode->runtimeMinutesLabel())
                                    <x-ui.badge variant="outline" color="neutral" icon="clock">{{ $episode->runtimeMinutesLabel() }}</x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $episode->name }}</x-ui.heading>
                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    {{ $episode->summaryText() ?: 'No public plot outline has been published for this episode yet.' }}
                                </x-ui.text>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sb-cast-summary-card">
                                <div class="sb-cast-summary-label">Audience</div>
                                <div class="sb-cast-summary-value">
                                    {{ $episode->statistic?->average_rating ? number_format((float) $episode->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="sb-cast-summary-copy">{{ number_format($ratingCount) }} votes</div>
                            </div>
                            <div class="sb-cast-summary-card">
                                <div class="sb-cast-summary-label">Series</div>
                                <div class="sb-cast-summary-value">{{ $series->name }}</div>
                                <div class="sb-cast-summary-copy">Season {{ $season->season_number }}</div>
                            </div>
                            <div class="sb-cast-summary-card">
                                <div class="sb-cast-summary-label">Episode</div>
                                <div class="sb-cast-summary-value">{{ $episodeMeta?->episode_number ?? '—' }}</div>
                                <div class="sb-cast-summary-copy">Within the current season</div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-ui.button as="a" :href="route('public.titles.show', $series)" variant="outline" icon="tv">
                                Series page
                            </x-ui.button>
                            <x-ui.button as="a" :href="route('public.seasons.show', ['series' => $series, 'season' => $season])" variant="ghost" icon="rectangle-stack">
                                Season page
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card data-slot="episode-detail-facts" class="sb-detail-section !max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Episode facts</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Metadata from the imported episode record and its parent series context.
                        </x-ui.text>
                    </div>
                    <x-ui.badge variant="outline" color="amber" icon="film">
                        Season {{ $season->season_number }} · Episode {{ $episodeMeta?->episode_number ?? '—' }}
                    </x-ui.badge>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($detailItems as $item)
                        <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                            <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                            <div class="mt-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $item['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <div data-slot="episode-detail-cast" class="space-y-6">
                <x-ui.card class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Guest cast</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Imported cast entries linked to people pages.
                            </x-ui.text>
                        </div>

                        @if ($guestCast->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($guestCast as $credit)
                                    @if ($credit->person)
                                        <a href="{{ route('public.people.show', $credit->person) }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                            <div class="flex items-center gap-3">
                                                <x-ui.avatar
                                                    :src="$credit->person->preferredHeadshot()?->url"
                                                    :alt="$credit->person->preferredHeadshot()?->alt_text ?: $credit->person->name"
                                                    :name="$credit->person->name"
                                                    color="auto"
                                                    class="!h-14 !w-14 shrink-0 border border-black/5 dark:border-white/10"
                                                />
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-neutral-900 dark:text-neutral-100">{{ $credit->person->name }}</div>
                                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $credit->job }}</div>
                                                </div>
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No guest cast rows are linked to this episode yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Key crew</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Grouped crew roles linked to their person profiles.
                            </x-ui.text>
                        </div>

                        @if ($keyCrew->isNotEmpty())
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($keyCrew as $crewGroup)
                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $crewGroup['role'] }}</div>
                                        <div class="mt-3 space-y-2">
                                            @foreach ($crewGroup['credits'] as $credit)
                                                @if ($credit->person)
                                                    <a href="{{ route('public.people.show', $credit->person) }}" class="flex items-center justify-between gap-3 text-sm text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-neutral-100">
                                                        <span>{{ $credit->person->name }}</span>
                                                        <x-ui.icon name="arrow-right" class="size-4" />
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No crew group rows are linked to this episode yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card data-slot="episode-detail-lineup" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Episode navigation</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Season lineup and neighboring episode links without losing season context.
                            </x-ui.text>
                        </div>

                        @if ($previousEpisode && $previousEpisodeTitle)
                            <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $previousEpisodeTitle]) }}" class="flex items-center justify-between gap-3 rounded-[1rem] border border-black/5 bg-white/70 px-4 py-3 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Previous episode</div>
                                    <div class="mt-1 font-medium text-neutral-900 dark:text-neutral-100">{{ $previousEpisodeTitle->name }}</div>
                                </div>
                                <x-ui.icon name="arrow-left" class="size-4" />
                            </a>
                        @endif

                        <div class="rounded-[1rem] border border-black/5 bg-white/70 px-4 py-3 dark:border-white/10 dark:bg-white/[0.02]">
                            <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Season lineup</div>
                            <div class="mt-1 font-medium text-neutral-900 dark:text-neutral-100">{{ $episode->name }}</div>
                            <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Season {{ $season->season_number }} · Episode {{ $episodeMeta?->episode_number ?? '—' }}</div>
                        </div>

                        @if ($nextEpisode && $nextEpisodeTitle)
                            <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $nextEpisodeTitle]) }}" class="flex items-center justify-between gap-3 rounded-[1rem] border border-black/5 bg-white/70 px-4 py-3 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Next episode</div>
                                    <div class="mt-1 font-medium text-neutral-900 dark:text-neutral-100">{{ $nextEpisodeTitle->name }}</div>
                                </div>
                                <x-ui.icon name="arrow-right" class="size-4" />
                            </a>
                        @endif

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
            </div>
        </div>
    </section>
@endsection
