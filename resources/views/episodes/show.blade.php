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
    @php
        $episodeMeta = $episode->episodeMeta;
        $previousEpisodeTitle = $previousEpisode?->title;
        $nextEpisodeTitle = $nextEpisode?->title;
    @endphp

    <section class="space-y-6">
        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="relative">
                @if ($still)
                    <img
                        src="{{ $still->url }}"
                        alt="{{ $still->alt_text ?: $episode->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-20"
                    >
                    <div class="absolute inset-0 bg-linear-to-r from-white via-white/95 to-white/85 dark:from-neutral-950 dark:via-neutral-950/95 dark:to-neutral-950/85"></div>
                @else
                    <div class="absolute inset-0 bg-linear-to-br from-neutral-100 via-white to-neutral-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)]">
                    <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
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

                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="outline">{{ $series->name }}</x-ui.badge>
                                @if ($episodeMeta)
                                    <x-ui.badge variant="outline" color="slate">
                                        S{{ str_pad((string) $episodeMeta->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad((string) $episodeMeta->episode_number, 2, '0', STR_PAD_LEFT) }}
                                    </x-ui.badge>
                                @endif
                                @if ($episodeMeta?->aired_at)
                                    <x-ui.badge variant="outline" color="neutral">
                                        {{ $episodeMeta->aired_at->format('M j, Y') }}
                                    </x-ui.badge>
                                @endif
                                @if ($episode->runtime_minutes)
                                    <x-ui.badge variant="outline" color="neutral">{{ $episode->runtime_minutes }} min</x-ui.badge>
                                @endif
                                @if ($episode->statistic?->average_rating)
                                    <x-ui.badge icon="star" color="amber">
                                        {{ number_format((float) $episode->statistic->average_rating, 1) }}/10
                                    </x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <x-ui.heading level="h1" size="xl">{{ $episode->name }}</x-ui.heading>
                                <x-ui.text class="max-w-4xl text-base text-neutral-700 dark:text-neutral-200">
                                    {{ $episode->plot_outline ?: 'No public plot outline has been published for this episode yet.' }}
                                </x-ui.text>
                                @if (filled($episode->synopsis))
                                    <x-ui.text class="text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                                        {{ $episode->synopsis }}
                                    </x-ui.text>
                                @endif
                            </div>

                            @if ($episode->genres->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($episode->genres as $genre)
                                        <a href="{{ route('public.genres.show', $genre) }}">
                                            <x-ui.badge variant="outline" color="neutral">{{ $genre->name }}</x-ui.badge>
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-3">
                                <x-ui.button as="a" href="#title-rating" variant="outline" icon="star">
                                    Rate episode
                                </x-ui.button>
                                <x-ui.button as="a" href="#title-review" variant="outline" icon="chat-bubble-left-right">
                                    Write review
                                </x-ui.button>
                                <x-ui.button as="a" href="#title-lists" variant="outline" icon="queue-list">
                                    Add to custom list
                                </x-ui.button>
                                <x-ui.button as="a" :href="route('public.seasons.show', ['series' => $series, 'season' => $season])" variant="ghost" icon="list-bullet">
                                    Season page
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Audience rating</div>
                                <div class="mt-2 text-2xl font-semibold">
                                    {{ $episode->statistic?->average_rating ? number_format((float) $episode->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ number_format((int) ($episode->statistic?->rating_count ?? 0)) }} ratings
                                </div>
                            </div>
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Reviews</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($episode->statistic?->review_count ?? 0)) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Published audience responses</div>
                            </div>
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Watchlists</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($episode->statistic?->watchlist_count ?? 0)) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Members tracking this episode</div>
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
                        <x-ui.heading level="h2" size="lg">Episode navigation</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Move through the season without losing your place in the series hierarchy.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if ($previousEpisode && $previousEpisodeTitle)
                            <x-ui.button as="a" :href="route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $previousEpisodeTitle])" variant="ghost" icon="arrow-left">
                                Previous episode
                            </x-ui.button>
                        @endif
                        @if ($nextEpisode && $nextEpisodeTitle)
                            <x-ui.button as="a" :href="route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $nextEpisodeTitle])" variant="ghost" icon="arrow-right">
                                Next episode
                            </x-ui.button>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($seasonNavigation as $navigationSeason)
                        <a href="{{ route('public.seasons.show', ['series' => $series, 'season' => $navigationSeason]) }}">
                            <x-ui.badge
                                variant="outline"
                                :color="$navigationSeason->is($season) ? 'green' : 'neutral'"
                            >
                                Season {{ $navigationSeason->season_number }}
                            </x-ui.badge>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            <livewire:titles.watchlist-toggle :title="$episode" :key="'watchlist-'.$episode->id" />
            <livewire:titles.watch-state-panel :title="$episode" :key="'watch-state-'.$episode->id" />
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="space-y-6">
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg">Guest cast</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral">{{ number_format($guestCast->count()) }} credits</x-ui.badge>
                        </div>

                        @if ($guestCast->isNotEmpty())
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($guestCast as $credit)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                        <div class="font-medium">
                                            <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                {{ $credit->person->name }}
                                            </a>
                                        </div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $credit->character_name ?: ($credit->credited_as ?: 'Cast credit') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.heading level="h3">No guest cast has been published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg">Key crew</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral">{{ number_format($keyCrew->count()) }} role groups</x-ui.badge>
                        </div>

                        @if ($keyCrew->isNotEmpty())
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($keyCrew as $group)
                                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                        <div class="font-medium">{{ $group['role'] }}</div>
                                        <div class="mt-3 space-y-2 text-sm text-neutral-600 dark:text-neutral-300">
                                            @foreach ($group['credits'] as $credit)
                                                <div>
                                                    <a href="{{ route('public.people.show', $credit->person) }}" class="font-medium text-neutral-800 hover:opacity-80 dark:text-neutral-100">
                                                        {{ $credit->person->name }}
                                                    </a>
                                                    @if ($credit->credited_as)
                                                        <span class="text-neutral-500 dark:text-neutral-400">· {{ $credit->credited_as }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.heading level="h3">No crew credits are available yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <livewire:titles.rating-panel :title="$episode" :key="'rating-'.$episode->id" />

                <livewire:titles.custom-list-picker :title="$episode" :key="'custom-lists-'.$episode->id" />

                <livewire:titles.review-composer :title="$episode" :key="'review-'.$episode->id" />

                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg">Latest reviews</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral">{{ number_format($reviews->count()) }} published</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($reviews as $review)
                                <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="font-medium">{{ $review->headline ?: 'Member review' }}</div>
                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $review->author->name }}
                                        </div>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                        @if ($review->contains_spoilers)
                                            <span>Spoilers</span>
                                        @endif
                                        @if ($review->published_at)
                                            <span>{{ $review->published_at->format('M j, Y') }}</span>
                                        @endif
                                        <span>{{ number_format((int) $review->helpful_votes_count) }} helpful</span>
                                    </div>
                                    <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ str($review->body)->limit(260) }}
                                    </x-ui.text>
                                </div>
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.heading level="h3">No published reviews yet.</x-ui.heading>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <x-ui.heading level="h2" size="lg">Details</x-ui.heading>

                        <div class="grid gap-3">
                            @forelse ($detailItems as $item)
                                <div class="flex items-start justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                                    <div class="text-right text-sm text-neutral-800 dark:text-neutral-100">{{ $item['value'] }}</div>
                                </div>
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.heading level="h3">Detailed metadata is still being curated.</x-ui.heading>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg">Season lineup</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral">{{ number_format($seasonEpisodes->count()) }} episodes</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @forelse ($seasonEpisodes as $seasonEpisode)
                                @php
                                    $seasonEpisodeTitle = $seasonEpisode->title;
                                @endphp

                                @if ($seasonEpisodeTitle)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10 {{ $seasonEpisodeTitle->is($episode) ? 'ring-1 ring-emerald-500/40' : '' }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium">
                                                    <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $seasonEpisodeTitle]) }}" class="hover:opacity-80">
                                                        {{ $seasonEpisodeTitle->name }}
                                                    </a>
                                                </div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    Episode {{ $seasonEpisode->episode_number }}
                                                    @if ($seasonEpisode->aired_at)
                                                        · {{ $seasonEpisode->aired_at->format('M j, Y') }}
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($seasonEpisodeTitle->statistic?->average_rating)
                                                <x-ui.badge icon="star" color="amber">
                                                    {{ number_format((float) $seasonEpisodeTitle->statistic->average_rating, 1) }}
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
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
