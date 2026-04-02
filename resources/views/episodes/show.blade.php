@extends('layouts.public')

@section('title', $episode->meta_title ?: $episode->name)
@section('meta_description', $episode->meta_description ?: ($episode->plot_outline ?: 'Browse credits, reviews, and metadata for '.$episode->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.series.index')">TV Shows</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $series)">{{ $series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.seasons.show', ['series' => $series, 'season' => $season])">{{ $season->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $episode->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-detail-hero sb-episode-detail-hero !max-w-none overflow-hidden p-0" data-slot="episode-detail-hero">
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
                    <div class="sb-episode-hero-media overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
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

                    <div class="sb-detail-panel sb-episode-hero-panel space-y-6 p-5 sm:p-6">
                        <div class="space-y-5">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 space-y-3">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span class="sb-detail-overline">Episode dossier</span>
                                        <a href="{{ route('public.titles.show', $series) }}" class="sb-detail-meta-link">{{ $series->name }}</a>
                                        @if ($episodeMeta)
                                            <span class="sb-detail-meta-chip">
                                                S{{ str_pad((string) $episodeMeta->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad((string) $episodeMeta->episode_number, 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                        @endif
                                        @if ($episodeMeta?->aired_at)
                                            <span class="sb-detail-meta-chip">{{ $episodeMeta->aired_at->format('M j, Y') }}</span>
                                        @endif
                                        @if ($episode->runtime_minutes)
                                            <span class="sb-detail-meta-chip">{{ $episode->runtime_minutes }} min</span>
                                        @endif
                                    </div>

                                    <div class="space-y-2">
                                        <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $episode->name }}</x-ui.heading>
                                        <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                            {{ $episode->plot_outline ?: 'No public plot outline has been published for this episode yet.' }}
                                        </x-ui.text>
                                        @if (filled($episode->synopsis))
                                            <x-ui.text class="text-sm leading-7 text-[#b8ad9d] dark:text-[#b8ad9d]">
                                                {{ $episode->synopsis }}
                                            </x-ui.text>
                                        @endif
                                    </div>
                                </div>

                                <div class="sb-detail-rating-shell">
                                    <div class="sb-detail-rating-label">Episode rating</div>
                                    <div class="sb-detail-rating-value">
                                        {{ $episode->statistic?->average_rating ? number_format((float) $episode->statistic->average_rating, 1) : 'N/A' }}
                                    </div>
                                    <div class="sb-detail-rating-copy">
                                        {{ number_format($ratingCount) }} member ratings
                                    </div>
                                </div>
                            </div>

                            <div class="sb-episode-context-facts">
                                <div class="sb-series-hero-fact">
                                    <span class="sb-series-hero-fact-label">Series context</span>
                                    <span class="sb-series-hero-fact-value">{{ $series->name }}</span>
                                </div>
                                <div class="sb-series-hero-fact">
                                    <span class="sb-series-hero-fact-label">Season</span>
                                    <span class="sb-series-hero-fact-value">{{ $season->name }}</span>
                                </div>
                                @if ($episodeMeta)
                                    <div class="sb-series-hero-fact">
                                        <span class="sb-series-hero-fact-label">Episode number</span>
                                        <span class="sb-series-hero-fact-value">Episode {{ $episodeMeta->episode_number }}</span>
                                    </div>
                                @endif
                            </div>

                            @if ($episode->genres->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($episode->genres as $genre)
                                        <a href="{{ route('public.genres.show', $genre) }}" class="sb-detail-chip">
                                            {{ $genre->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sb-cast-summary-card">
                                <div class="sb-cast-summary-label">Audience</div>
                                <div class="sb-cast-summary-value">
                                    {{ $episode->statistic?->average_rating ? number_format((float) $episode->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="sb-cast-summary-copy">{{ number_format($ratingCount) }} ratings</div>
                            </div>
                            <div class="sb-cast-summary-card">
                                <div class="sb-cast-summary-label">Reviews</div>
                                <div class="sb-cast-summary-value">{{ number_format((int) ($episode->statistic?->review_count ?? 0)) }}</div>
                                <div class="sb-cast-summary-copy">Published responses</div>
                            </div>
                            <div class="sb-cast-summary-card">
                                <div class="sb-cast-summary-label">Watchlists</div>
                                <div class="sb-cast-summary-value">{{ number_format((int) ($episode->statistic?->watchlist_count ?? 0)) }}</div>
                                <div class="sb-cast-summary-copy">Members tracking</div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <div class="flex flex-wrap gap-3">
                                <x-ui.button as="a" href="#episode-rating" icon="star" color="amber" class="sb-detail-primary-action">
                                    Rate episode
                                </x-ui.button>
                                <x-ui.button as="a" href="#episode-review" variant="outline" color="amber" icon="chat-bubble-left-right" class="sb-detail-secondary-action">
                                    Write review
                                </x-ui.button>
                            </div>

                            <div class="flex flex-wrap gap-x-4 gap-y-2">
                                <a href="{{ route('public.titles.show', $series) }}" class="sb-detail-utility-link">Series page</a>
                                <a href="{{ route('public.seasons.show', ['series' => $series, 'season' => $season]) }}" class="sb-detail-utility-link">Season page</a>
                                <a href="#episode-season-lineup" class="sb-detail-utility-link">Season lineup</a>
                                <a href="#episode-parents-guide" class="sb-detail-utility-link">Parents guide</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-detail-section sb-episode-nav-shell !max-w-none" data-slot="episode-navigation-shell">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="sb-cast-section-label">Episode navigation</div>
                        <x-ui.heading level="h2" size="lg" class="mt-2">Move through the season</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Jump between seasons, then move one episode at a time without losing the series context.
                        </x-ui.text>
                    </div>

                    <x-ui.badge variant="outline" color="amber" icon="film">
                        Season {{ $season->season_number }} · Episode {{ $episodeMeta?->episode_number ?? '—' }}
                    </x-ui.badge>
                </div>

                <div class="sb-episode-nav-grid">
                    @if ($previousEpisode && $previousEpisodeTitle)
                        <a
                            href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $previousEpisodeTitle]) }}"
                            class="sb-episode-nav-card"
                        >
                            <div class="sb-episode-nav-card-label">Previous episode</div>
                            <div class="sb-episode-nav-card-title">{{ $previousEpisodeTitle->name }}</div>
                            <div class="sb-episode-nav-card-meta">
                                Episode {{ $previousEpisode->episode_number ?? '—' }}
                                @if ($previousEpisode->aired_at)
                                    · {{ $previousEpisode->aired_at->format('M j, Y') }}
                                @endif
                            </div>
                        </a>
                    @else
                        <div class="sb-episode-nav-card sb-episode-nav-card--inactive">
                            <div class="sb-episode-nav-card-label">Previous episode</div>
                            <div class="sb-episode-nav-card-title">Season opener</div>
                            <div class="sb-episode-nav-card-meta">There is no earlier episode in this season.</div>
                        </div>
                    @endif

                    <div class="sb-episode-nav-current">
                        <div class="sb-cast-section-label">Now viewing</div>
                        <div class="sb-episode-nav-current-code">S{{ $season->season_number }} · E{{ $episodeMeta?->episode_number ?? '—' }}</div>
                        <div class="sb-episode-nav-current-title">{{ $episode->name }}</div>
                    </div>

                    @if ($nextEpisode && $nextEpisodeTitle)
                        <a
                            href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $nextEpisodeTitle]) }}"
                            class="sb-episode-nav-card sb-episode-nav-card--next"
                        >
                            <div class="sb-episode-nav-card-label">Next episode</div>
                            <div class="sb-episode-nav-card-title">{{ $nextEpisodeTitle->name }}</div>
                            <div class="sb-episode-nav-card-meta">
                                Episode {{ $nextEpisode->episode_number ?? '—' }}
                                @if ($nextEpisode->aired_at)
                                    · {{ $nextEpisode->aired_at->format('M j, Y') }}
                                @endif
                            </div>
                        </a>
                    @else
                        <div class="sb-episode-nav-card sb-episode-nav-card--inactive">
                            <div class="sb-episode-nav-card-label">Next episode</div>
                            <div class="sb-episode-nav-card-title">Season finale</div>
                            <div class="sb-episode-nav-card-meta">There is no later episode in this season.</div>
                        </div>
                    @endif
                </div>

                <div class="sb-season-nav-row">
                    @foreach ($seasonNavigation as $navigationSeason)
                        <a
                            href="{{ route('public.seasons.show', ['series' => $series, 'season' => $navigationSeason]) }}"
                            class="sb-season-nav-pill{{ $navigationSeason->is($season) ? ' sb-season-nav-pill--active' : '' }}"
                        >
                            <span class="sb-season-nav-pill-title">Season {{ $navigationSeason->season_number }}</span>
                            <span class="sb-season-nav-pill-meta">{{ number_format((int) $navigationSeason->episodes_count) }} episodes</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            <livewire:titles.watchlist-toggle :title="$episode" :key="'watchlist-'.$episode->id" />
            <livewire:titles.watch-state-panel :title="$episode" :key="'watch-state-'.$episode->id" />
        </div>

        <x-ui.card class="sb-detail-section sb-title-directory-shell !max-w-none">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="space-y-2">
                    <div class="sb-title-directory-kicker">Episode dossier</div>
                    <x-ui.heading level="h2" size="lg" class="sb-title-directory-title">Navigation map</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-[#b8ad9d] dark:text-[#b8ad9d]">
                        Plot, guest cast, crew, parents guide preview, trivia, goofs, reviews, and the full season lineup arranged in one vertical scan.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($episodeDirectory as $directoryItem)
                        <a href="{{ $directoryItem['href'] }}" class="sb-title-directory-link">
                            {{ $directoryItem['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)]">
            <div class="space-y-6">
                <x-ui.card id="episode-plot" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Plot</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Editorial episode summary, audience-facing synopsis, and release context in one dossier block.
                                </x-ui.text>
                            </div>
                            @if ($episodeMeta?->aired_at)
                                <x-ui.badge variant="outline" color="neutral" icon="calendar-days">{{ $episodeMeta->aired_at->format('M j, Y') }}</x-ui.badge>
                            @endif
                        </div>

                        <x-ui.text class="text-neutral-700 dark:text-neutral-200">
                            {{ $episode->plot_outline ?: 'A public plot outline has not been published for this episode yet.' }}
                        </x-ui.text>

                        @if (filled($episode->synopsis))
                            <x-ui.text class="text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                                {{ $episode->synopsis }}
                            </x-ui.text>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card id="episode-guest-cast" class="sb-detail-section sb-episode-guest-shell !max-w-none" data-slot="episode-guest-cast-shell">
                    <div class="space-y-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="sb-cast-section-label">Featured guests</div>
                                <x-ui.heading level="h2" size="lg">Guest cast</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Featured performers, character names, and credited-as details for this episode record.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="amber" icon="users">{{ number_format($guestCast->count()) }} credits</x-ui.badge>
                        </div>

                        @if ($guestCast->isNotEmpty())
                            <div class="sb-episode-guest-highlights">
                                @foreach ($guestCast->take(3) as $credit)
                                    <a href="{{ route('public.people.show', $credit->person) }}" class="sb-episode-guest-pill">
                                        <span class="sb-episode-guest-pill-name">{{ $credit->person->name }}</span>
                                        <span class="sb-episode-guest-pill-role">
                                            {{ $credit->character_name ?: ($credit->credited_as ?: 'Guest cast') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>

                            <div class="grid gap-2.5">
                                @foreach ($guestCast as $credit)
                                    <div class="sb-cast-credit-row">
                                        <div class="sb-cast-credit-rank">{{ $credit->billing_order ? '#'.$credit->billing_order : 'Guest' }}</div>
                                        <div>
                                            <div class="sb-cast-credit-name">
                                                <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                    {{ $credit->person->name }}
                                                </a>
                                            </div>
                                            <div class="mt-1 sb-cast-credit-role">
                                                {{ $credit->character_name ?: ($credit->credited_as ?: 'Cast credit') }}
                                            </div>
                                        </div>
                                        <div class="sb-cast-credit-note text-right">
                                            @if ($credit->credited_as)
                                                Credited as {{ $credit->credited_as }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No guest cast has been published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card id="episode-crew" class="sb-detail-section !max-w-none">
                    <div class="space-y-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Key crew</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Core creative and technical contributors grouped the way a professional episode archive should read.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-group">{{ number_format($keyCrew->count()) }} role groups</x-ui.badge>
                        </div>

                        @if ($keyCrew->isNotEmpty())
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($keyCrew as $group)
                                    <div class="sb-crew-group sb-crew-group--lead">
                                        <div class="sb-crew-group-title">{{ $group['role'] }}</div>
                                        <div class="mt-3 space-y-2">
                                            @foreach ($group['credits'] as $credit)
                                                <div>
                                                    <a href="{{ route('public.people.show', $credit->person) }}" class="sb-crew-credit-name hover:opacity-80">
                                                        {{ $credit->person->name }}
                                                    </a>
                                                    @if ($credit->credited_as)
                                                        <div class="mt-1 sb-crew-credit-note">Credited as {{ $credit->credited_as }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="briefcase" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No crew credits are available yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card id="episode-trivia" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Trivia</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Production anecdotes, behind-the-scenes notes, and viewer-facing factoids tied directly to this episode.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">
                                {{ $triviaItems->isNotEmpty() ? number_format($triviaItems->count()).' notes' : 'Reserved' }}
                            </x-ui.badge>
                        </div>

                        @if ($triviaItems->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($triviaItems as $triviaItem)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $triviaItem }}
                                        </x-ui.text>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Trivia has not been published for this episode yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    The dossier keeps a dedicated trivia section so episode-level notes can appear without changing the page structure.
                                </x-ui.text>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card id="episode-goofs" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Goofs</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Continuity slips, production mistakes, and archive-ready error notes when that episode feed exists.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="exclamation-circle">
                                {{ $goofItems->isNotEmpty() ? number_format($goofItems->count()).' notes' : 'Reserved' }}
                            </x-ui.badge>
                        </div>

                        @if ($goofItems->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($goofItems as $goofItem)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $goofItem }}
                                        </x-ui.text>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="exclamation-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Goofs are not available for this episode yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    The layout reserves this section so continuity issues and production errors can appear as soon as the data exists.
                                </x-ui.text>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card id="episode-reviews" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Latest reviews</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Published audience reactions with spoiler flags and helpful-vote signal.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="check-circle">Published only</x-ui.badge>
                        </div>

                        <livewire:reviews.title-review-list :title="$episode" :key="'episode-reviews-'.$episode->id" />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <livewire:titles.rating-panel :title="$episode" anchorId="episode-rating" :key="'rating-'.$episode->id" />

                <div id="title-lists">
                    <livewire:titles.custom-list-picker :title="$episode" :key="'custom-lists-'.$episode->id" />
                </div>

                <livewire:titles.review-composer :title="$episode" anchorId="episode-review" :key="'review-'.$episode->id" />

                <x-ui.card id="episode-parents-guide" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Parents guide preview</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Certification, advisory severity, and spoiler-aware preview guidance for this episode.
                                </x-ui.text>
                            </div>
                            @if ($episode->age_rating)
                                <x-ui.badge color="amber" icon="shield-check">{{ $episode->age_rating }}</x-ui.badge>
                            @endif
                        </div>

                        @if ($certificateItems->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($certificateItems as $certificateItem)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $certificateItem['rating'] }}</div>
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
                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $parentGuideItem['category'] }}</div>
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
                                <x-ui.heading level="h3">Parents guide preview is still pending.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    The page is ready for structured advisory data, but this episode does not yet carry parents guide detail beyond its certification.
                                </x-ui.text>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                            <x-ui.icon name="information-circle" class="size-5 text-neutral-500 dark:text-neutral-400" />
                            <span>Episode details</span>
                        </x-ui.heading>

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

                <x-ui.card id="episode-season-lineup" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Season lineup</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    The full running order for this season, with the current episode pinned in context.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($seasonEpisodes->count()) }} episodes</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($seasonEpisodes as $seasonEpisode)
                                @if ($seasonEpisode->title)
                                    <div class="sb-episode-lineup-row{{ $seasonEpisode->title->is($episode) ? ' sb-episode-lineup-row--active' : '' }}">
                                        <div class="sb-episode-lineup-rank">{{ str_pad((string) $seasonEpisode->episode_number, 2, '0', STR_PAD_LEFT) }}</div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                                <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $seasonEpisode->title]) }}" class="hover:opacity-80">
                                                    {{ $seasonEpisode->title->name }}
                                                </a>
                                            </div>
                                            <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                Episode {{ $seasonEpisode->episode_number }}
                                                @if ($seasonEpisode->aired_at)
                                                    · {{ $seasonEpisode->aired_at->format('M j, Y') }}
                                                @endif
                                            </div>
                                        </div>

                                        @if ($seasonEpisode->title->statistic?->average_rating)
                                            <x-ui.badge icon="star" color="amber">
                                                {{ number_format((float) $seasonEpisode->title->statistic->average_rating, 1) }}
                                            </x-ui.badge>
                                        @endif
                                    </div>
                                @endif
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="list-bullet" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No season lineup is available yet.</x-ui.heading>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </section>
    </section>
@endsection
