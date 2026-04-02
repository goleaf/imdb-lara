@extends('layouts.public')

@section('title', $title->name.' Keywords & Connections')
@section('meta_description', 'Explore keywords and title connections for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Keywords &amp; Connections</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero sb-metadata-hero !max-w-none overflow-hidden p-0" data-slot="title-metadata-hero">
            <div class="sb-metadata-hero-backdrop">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="sb-metadata-hero-backdrop-image"
                    >
                @endif
            </div>

            <div class="relative grid gap-6 p-6 sm:p-7 xl:grid-cols-[minmax(0,11rem)_minmax(0,1fr)_minmax(17rem,0.78fr)] xl:items-end">
                <div class="sb-metadata-poster-shell">
                    @if ($poster)
                        <img
                            src="{{ $poster->url }}"
                            alt="{{ $poster->alt_text ?: $title->name }}"
                            class="aspect-[2/3] w-full object-cover"
                            loading="lazy"
                        >
                    @else
                        <div class="sb-metadata-poster-empty">
                            <x-ui.icon name="film" class="size-9" />
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="sb-page-kicker">Metadata Exploration</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Keywords &amp; Connections</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl text-base">
                            A focused metadata map for {{ $title->name }}, combining search cues, thematic keywords, and connected-title relationships in one exploration surface.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="amber" icon="tag">{{ number_format($keywordCount) }} keyword cues</x-ui.badge>
                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">{{ number_format($connectionCount) }} connections</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="sparkles">Deep discovery</x-ui.badge>
                    </div>
                </div>

                <div class="sb-metadata-hero-panel">
                    <div class="space-y-3">
                        <div>
                            <div class="sb-metadata-panel-kicker">Metadata Lens</div>
                            <div class="sb-metadata-panel-copy">
                                Use this page to jump from story cues into related titles without losing the premium archive feel of the title page.
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="sb-metadata-stat-card">
                                <div class="sb-metadata-stat-label">Keyword density</div>
                                <div class="sb-metadata-stat-value">{{ number_format($keywordCount) }}</div>
                                <div class="sb-metadata-stat-copy">Grouped into signal bands for faster scan paths.</div>
                            </div>
                            <div class="sb-metadata-stat-card">
                                <div class="sb-metadata-stat-label">Connection map</div>
                                <div class="sb-metadata-stat-value">{{ number_format($connectionCount) }}</div>
                                <div class="sb-metadata-stat-copy">Structured by relationship family and title card language.</div>
                            </div>
                        </div>

                        <x-ui.link :href="route('public.titles.show', $title)" variant="ghost" iconAfter="arrow-right">
                            Back to title page
                        </x-ui.link>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.88fr)_minmax(0,1.12fr)]">
            <x-ui.card class="sb-detail-section sb-keyword-map-shell !max-w-none p-5 sm:p-6" data-slot="title-keyword-map">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Keyword Map</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Grouped discovery language with quiet relevance indicators for each cue.
                            </x-ui.text>
                        </div>

                        <x-ui.badge variant="outline" color="neutral" icon="magnifying-glass">
                            {{ number_format($keywordCount) }} indexed cues
                        </x-ui.badge>
                    </div>

                    @if ($keywordGroups->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($keywordGroups as $group)
                                <section class="sb-keyword-group-card">
                                    <div class="space-y-1">
                                        <div class="sb-keyword-group-title">{{ $group['label'] }}</div>
                                        <div class="sb-keyword-group-copy">{{ $group['copy'] }}</div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2.5">
                                        @foreach ($group['keywords'] as $keyword)
                                            <a href="{{ $keyword['href'] }}" class="sb-keyword-chip" title="{{ $keyword['relevanceLabel'] }}">
                                                <span class="sb-keyword-chip-main">
                                                    <span class="sb-keyword-chip-text">{{ $keyword['name'] }}</span>
                                                    <span class="sb-keyword-signal" aria-hidden="true">
                                                        @for ($signalIndex = 1; $signalIndex <= 3; $signalIndex++)
                                                            <span class="{{ $signalIndex <= $keyword['relevance'] ? 'sb-keyword-signal-dot--active' : 'sb-keyword-signal-dot' }}"></span>
                                                        @endfor
                                                    </span>
                                                </span>
                                                <span class="sb-keyword-chip-action">Explore</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="tag" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Keyword discovery is still thin.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Search keywords will surface here as the editorial and import layers deepen.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="sb-detail-section sb-connection-map-shell !max-w-none p-5 sm:p-6" data-slot="title-connection-map">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Title Connections</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Related-title cards grouped by how this title connects to the wider catalog.
                            </x-ui.text>
                        </div>

                        <x-ui.badge variant="outline" color="neutral" icon="film">
                            {{ number_format($connectionCount) }} linked titles
                        </x-ui.badge>
                    </div>

                    @if ($connectionGroups->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($connectionGroups as $group)
                                <section class="sb-connection-group-card">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="sb-connection-group-title">{{ $group['label'] }}</div>
                                            <div class="sb-connection-group-copy">{{ $group['copy'] }}</div>
                                        </div>

                                        <x-ui.badge variant="outline" color="slate" icon="queue-list">
                                            {{ number_format($group['count']) }} {{ str('title')->plural($group['count']) }}
                                        </x-ui.badge>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        @foreach ($group['items'] as $item)
                                            <article class="sb-connection-card" data-slot="connection-card">
                                                <div class="sb-connection-card-topline">
                                                    <span class="sb-connection-card-collection">Connection Card</span>
                                                    @if ($item['weight'])
                                                        <span class="sb-connection-weight-badge">Signal {{ $item['weight'] }}/10</span>
                                                    @endif
                                                </div>

                                                <div class="sb-connection-card-body">
                                                    <a href="{{ route('public.titles.show', $item['title']) }}" class="sb-connection-card-media">
                                                        @if ($item['title']->preferredPoster())
                                                            <img
                                                                src="{{ $item['title']->preferredPoster()->url }}"
                                                                alt="{{ $item['title']->preferredPoster()->alt_text ?: $item['title']->name }}"
                                                                class="sb-connection-card-image"
                                                                loading="lazy"
                                                            >
                                                        @else
                                                            <div class="sb-connection-card-empty">
                                                                <x-ui.icon name="film" class="size-7" />
                                                            </div>
                                                        @endif
                                                    </a>

                                                    <div class="space-y-3 p-4">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="sb-connection-type-badge">{{ $item['badgeLabel'] }}</span>
                                                            <span class="sb-connection-card-family">{{ $group['label'] }}</span>
                                                        </div>

                                                        <div class="space-y-2">
                                                            <x-ui.heading level="h3" size="md" class="sb-connection-card-title">
                                                                <a href="{{ route('public.titles.show', $item['title']) }}" class="hover:opacity-80">
                                                                    {{ $item['title']->name }}
                                                                </a>
                                                            </x-ui.heading>

                                                            <div class="sb-connection-card-meta">
                                                                <span>{{ $item['typeLabel'] }}</span>
                                                                @if ($item['yearLabel'])
                                                                    <span>{{ $item['yearLabel'] }}</span>
                                                                @endif
                                                                @if ($item['ratingLabel'])
                                                                    <span>{{ $item['ratingLabel'] }} rating</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @if ($item['note'])
                                                            <div class="sb-connection-card-copy">{{ $item['note'] }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No title connections are linked yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Connection cards will appear here as the title graph becomes richer.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </section>
@endsection
