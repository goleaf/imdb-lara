@extends('layouts.public')

@section('title', $title->meta_title ?: $title->name)
@section('meta_description', $title->meta_description ?: ($title->plot_outline ?: 'Read credits, ratings, and reviews for '.$title->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    @php
        $shareModalId = 'share-title-'.$title->id;
        $shareUrl = route('public.titles.show', $title);
        $isSeriesLike = in_array($title->title_type, [\App\Enums\TitleType::Series, \App\Enums\TitleType::MiniSeries], true);
        $ratingCount = (int) ($title->statistic?->rating_count ?? 0);
        $maxBreakdownCount = max(1, (int) $ratingsBreakdown->max('count'));
        $titleTypeIcon = match ($title->title_type) {
            \App\Enums\TitleType::Series, \App\Enums\TitleType::MiniSeries => 'tv',
            \App\Enums\TitleType::Documentary => 'camera',
            \App\Enums\TitleType::Special => 'sparkles',
            \App\Enums\TitleType::Episode => 'rectangle-stack',
            default => 'film',
        };
    @endphp

    <section class="space-y-6">
        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-20"
                    >
                    <div class="absolute inset-0 bg-linear-to-r from-white via-white/95 to-white/85 dark:from-neutral-950 dark:via-neutral-950/95 dark:to-neutral-950/85"></div>
                @else
                    <div class="absolute inset-0 bg-linear-to-br from-neutral-100 via-white to-neutral-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[15rem_minmax(0,1fr)]">
                    <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
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

                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="outline" :icon="$titleTypeIcon">{{ str($title->title_type->value)->headline() }}</x-ui.badge>

                                @if ($title->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $title->release_year }}</x-ui.badge>
                                    </a>
                                @endif

                                @if ($title->runtime_minutes)
                                    <x-ui.badge variant="outline" color="neutral" icon="clock">{{ $title->runtime_minutes }} min</x-ui.badge>
                                @endif

                                @if ($title->age_rating)
                                    <x-ui.badge variant="outline" color="neutral" icon="shield-check">{{ $title->age_rating }}</x-ui.badge>
                                @endif

                                @if ($title->statistic?->average_rating)
                                    <x-ui.badge color="amber" icon="star">
                                        {{ number_format((float) $title->statistic->average_rating, 1) }}/10
                                    </x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <x-ui.heading level="h1" size="xl">{{ $title->name }}</x-ui.heading>

                                @if (filled($title->original_name) && $title->original_name !== $title->name)
                                    <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                        Original title: {{ $title->original_name }}
                                    </x-ui.text>
                                @endif

                                @if (filled($title->tagline))
                                    <x-ui.text class="text-base italic text-neutral-500 dark:text-neutral-400">
                                        {{ $title->tagline }}
                                    </x-ui.text>
                                @endif

                                <x-ui.text class="max-w-4xl text-base text-neutral-700 dark:text-neutral-200">
                                    {{ $title->plot_outline ?: 'A full plot outline has not been published yet.' }}
                                </x-ui.text>
                            </div>

                            @if ($title->genres->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($title->genres as $genre)
                                        <a href="{{ route('public.genres.show', $genre) }}">
                                            <x-ui.badge variant="outline" color="neutral" icon="tag">{{ $genre->name }}</x-ui.badge>
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if ($countries->isNotEmpty() || $languages->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($countries as $country)
                                        <x-ui.badge variant="outline" color="slate">
                                            <x-ui.flag type="country" :code="$country" class="size-4" />
                                            {{ $country }}
                                        </x-ui.badge>
                                    @endforeach

                                    @foreach ($languages as $language)
                                        <x-ui.badge variant="outline" color="neutral">
                                            <x-ui.flag type="language" :code="$language" class="size-4" />
                                            {{ $language }}
                                        </x-ui.badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Audience rating</div>
                                <div class="mt-2 text-2xl font-semibold">
                                    {{ $title->statistic?->average_rating ? number_format((float) $title->statistic->average_rating, 1) : 'N/A' }}
                                </div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ number_format($ratingCount) }} ratings
                                </div>
                            </div>

                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Reviews</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($title->statistic?->review_count ?? 0)) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Published audience responses</div>
                            </div>

                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Watchlists</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($title->statistic?->watchlist_count ?? 0)) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Members tracking this title</div>
                            </div>

                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Awards</div>
                                <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($title->statistic?->awards_won_count ?? 0)) }}</div>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ number_format((int) ($title->statistic?->awards_nominated_count ?? 0)) }} nominations
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-ui.button as="a" href="#title-rating" variant="outline" icon="star">
                                Rate
                            </x-ui.button>
                            <x-ui.button as="a" href="#title-review" variant="outline" icon="chat-bubble-left-right">
                                Write review
                            </x-ui.button>
                            <x-ui.button as="a" href="#title-lists" variant="outline" icon="queue-list">
                                Add to custom list
                            </x-ui.button>
                            <x-ui.button as="a" :href="route('public.titles.cast', $title)" variant="ghost" icon="users">
                                Full cast
                            </x-ui.button>
                            <x-ui.modal.trigger :id="$shareModalId">
                                <x-ui.button variant="ghost" icon="share">
                                    Share
                                </x-ui.button>
                            </x-ui.modal.trigger>
                            @can('update', $title)
                                <x-ui.button as="a" :href="route('admin.titles.edit', $title)" variant="ghost" icon="pencil-square">
                                    Edit title
                                </x-ui.button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            <livewire:titles.watchlist-toggle :title="$title" :key="'watchlist-'.$title->id" />
            <livewire:titles.watch-state-panel :title="$title" :key="'watch-state-'.$title->id" />
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.75fr)]">
        <div class="space-y-6">
            <x-ui.card class="!max-w-none">
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

            <x-ui.card class="!max-w-none">
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
                <x-ui.card class="!max-w-none">
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
                            <div class="flex flex-wrap gap-2">
                                @foreach ($title->seasons as $season)
                                    <a href="{{ route('public.seasons.show', ['series' => $title, 'season' => $season]) }}">
                                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                                            Season {{ $season->season_number }}
                                        </x-ui.badge>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($latestSeason)
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Latest season overview</div>
                                        <div class="mt-1 text-lg font-semibold">
                                            <a href="{{ route('public.seasons.show', ['series' => $title, 'season' => $latestSeason]) }}" class="hover:opacity-80">
                                                {{ $latestSeason->name }}
                                            </a>
                                        </div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ number_format($latestSeason->episodes_count) }} episodes
                                            @if ($latestSeason->release_year)
                                                · {{ $latestSeason->release_year }}
                                            @endif
                                        </div>
                                        @if (filled($latestSeason->summary))
                                            <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                                {{ $latestSeason->summary }}
                                            </x-ui.text>
                                        @endif
                                    </div>

                                    <x-ui.link :href="route('public.seasons.show', ['series' => $title, 'season' => $latestSeason])" variant="ghost" iconAfter="arrow-right">
                                        View season
                                    </x-ui.link>
                                </div>

                                @if ($latestSeasonEpisodes->isNotEmpty())
                                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                                        @foreach ($latestSeasonEpisodes as $episodeMeta)
                                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div class="font-medium">
                                                            <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => $latestSeason, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
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

                                                    @if ($episodeMeta->title->statistic?->average_rating)
                                                        <x-ui.badge icon="star" color="amber">
                                                            {{ number_format((float) $episodeMeta->title->statistic->average_rating, 1) }}
                                                        </x-ui.badge>
                                                    @endif
                                                </div>

                                                @if (filled($episodeMeta->title->plot_outline))
                                                    <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                                        {{ str($episodeMeta->title->plot_outline)->limit(120) }}
                                                    </x-ui.text>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="grid gap-3">
                            @forelse ($title->seasons as $season)
                                <div class="flex flex-wrap items-start justify-between gap-3 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div>
                                        <div class="font-medium">
                                            <a href="{{ route('public.seasons.show', ['series' => $title, 'season' => $season]) }}" class="hover:opacity-80">
                                                {{ $season->name }}
                                            </a>
                                        </div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ number_format($season->episodes_count) }} episodes
                                            @if ($season->release_year)
                                                · {{ $season->release_year }}
                                            @endif
                                        </div>
                                    </div>

                                    <x-ui.link :href="route('public.seasons.show', ['series' => $title, 'season' => $season])" variant="ghost" iconAfter="arrow-right">
                                        View season
                                    </x-ui.link>
                                </div>
                            @empty
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="tv" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No seasons are available yet.</x-ui.heading>
                                </x-ui.empty>
                            @endforelse
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg">Top-rated episodes</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral" icon="star">
                                {{ number_format($topRatedEpisodes->count()) }} ranked
                            </x-ui.badge>
                        </div>

                        @if ($topRatedEpisodes->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($topRatedEpisodes as $episodeMeta)
                                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium">
                                                    <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => $episodeMeta->season, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                        {{ $episodeMeta->title->name }}
                                                    </a>
                                                </div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    Season {{ $episodeMeta->season_number }}, episode {{ $episodeMeta->episode_number }}
                                                    @if ($episodeMeta->season)
                                                        · {{ $episodeMeta->season->name }}
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
                                <x-ui.heading level="h3">Episode rankings are still coming together.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Scores will appear here once members start rating individual episodes.
                                </x-ui.text>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Media gallery</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($galleryAssets->count()) }} assets</x-ui.badge>
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

            <x-ui.card class="!max-w-none">
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
            <livewire:titles.rating-panel :title="$title" :key="'rating-'.$title->id" />

            <livewire:titles.custom-list-picker :title="$title" :key="'custom-lists-'.$title->id" />

            <livewire:contributions.suggestion-form
                contributableType="title"
                :contributableId="$title->id"
                :contributableLabel="$title->name"
                :key="'title-contribution-'.$title->id"
            />

            <x-ui.card class="!max-w-none">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Details & context</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Secondary metadata, score distribution, related catalog links, and future extension points.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="squares-2x2">7 panels</x-ui.badge>
                    </div>

                    <x-ui.accordion class="rounded-box border border-black/5 dark:border-white/10">
                        <x-ui.accordion.item expanded>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="information-circle" class="size-4" />
                                    Details
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                <div class="grid gap-3">
                                    @forelse ($detailItems as $item)
                                        <div class="flex items-start justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                                            <div class="text-right text-sm text-neutral-800 dark:text-neutral-100">{{ $item['value'] }}</div>
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
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="cog-6-tooth" class="size-4" />
                                    Technical specs
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                <div class="grid gap-3">
                                    @forelse ($technicalSpecItems as $item)
                                        <div class="flex items-start justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                            <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                                            <div class="text-right text-sm text-neutral-800 dark:text-neutral-100">{{ $item['value'] }}</div>
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
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="chart-bar" class="size-4" />
                                    Ratings breakdown
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
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
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="film" class="size-4" />
                                    Related titles
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
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
                                    </x-ui.empty>
                                @endif
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="trophy" class="size-4" />
                                    Awards
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
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
                                        </x-ui.empty>
                                    @endforelse
                                </div>
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="play" class="size-4" />
                                    Where to watch
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    Streaming and purchase availability can slot into this block once provider feeds are connected to the catalog.
                                </x-ui.text>
                                <x-ui.empty class="mt-3 rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="play" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">Availability is not published yet.</x-ui.heading>
                                </x-ui.empty>
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="sparkles" class="size-4" />
                                    Editorial extensions
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    Trivia, goofs, quotes, and soundtrack modules are not in the current domain model yet. This page keeps a reserved extension point so those datasets can slot in without changing the surrounding layout.
                                </x-ui.text>
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>
                    </x-ui.accordion>
                </div>
            </x-ui.card>
        </div>
    </section>

    <section class="space-y-4">
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

        <livewire:titles.review-composer :title="$title" :key="'review-'.$title->id" />

        <div class="grid gap-4">
            @forelse ($reviews as $review)
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <x-ui.heading level="h3" size="md">
                                    {{ $review->headline ?: 'Member review' }}
                                </x-ui.heading>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $review->author->name }}
                                    @if ($review->published_at)
                                        · {{ $review->published_at->format('M j, Y') }}
                                    @endif
                                    @if ($review->helpful_votes_count)
                                        · {{ number_format((int) $review->helpful_votes_count) }} found this helpful
                                    @endif
                                </div>
                            </div>

                            @if ($review->contains_spoilers)
                                <x-ui.badge color="red" variant="outline" icon="exclamation-triangle">Spoilers</x-ui.badge>
                            @endif
                        </div>

                        <x-ui.text class="text-neutral-700 dark:text-neutral-200">
                            {{ $review->body }}
                        </x-ui.text>

                        <div class="pt-1">
                            <livewire:reviews.report-review-form :review="$review" :key="'report-'.$review->id" />
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="chat-bubble-left-right" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No published reviews yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Be the first member to publish a review for this title.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>
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
