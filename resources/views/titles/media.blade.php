@extends('layouts.public')

@section('title', $title->name.' Media Gallery')
@section('meta_description', 'Browse posters, stills, backdrops, and trailers for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Media Gallery</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-detail-hero sb-media-hero !max-w-none overflow-hidden p-0" data-slot="title-media-hero">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-26"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(112deg,rgba(10,10,9,0.96),rgba(10,10,9,0.86),rgba(10,10,9,0.58))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(12,11,10,0.98),rgba(10,10,9,0.96))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[minmax(0,1.2fr)_22rem]">
                    <div class="sb-media-viewer-shell" data-slot="title-media-viewer">
                        <div class="sb-media-viewer-frame">
                            @if ($viewerAsset)
                                <img
                                    src="{{ $viewerAsset->url }}"
                                    alt="{{ $viewerAsset->alt_text ?: $title->name }}"
                                    class="sb-media-viewer-image"
                                >
                            @else
                                <div class="sb-media-viewer-empty">
                                    <x-ui.icon name="photo" class="size-12" />
                                </div>
                            @endif
                        </div>

                        <div class="sb-media-viewer-overlay">
                            <div class="space-y-2">
                                <div class="sb-media-kicker">Main viewer</div>
                                <div class="sb-media-viewer-title">{{ $viewerKindLabel }}</div>
                                <div class="sb-media-viewer-copy">
                                    {{ $viewerAsset?->caption ?: $viewerAsset?->alt_text ?: 'Primary gallery imagery selected from the title archive.' }}
                                </div>
                            </div>

                            @if ($viewerAsset)
                                <x-ui.badge variant="outline" color="amber" icon="photo">
                                    {{ $viewerKindLabel }}
                                </x-ui.badge>
                            @endif
                        </div>

                        @if ($viewerStripAssets->isNotEmpty())
                            <div class="sb-media-viewer-strip" aria-hidden="true">
                                @foreach ($viewerStripAssets as $asset)
                                    <div class="sb-media-viewer-thumb{{ $viewerAsset?->id === $asset->id ? ' sb-media-viewer-thumb--active' : '' }}">
                                        <img
                                            src="{{ $asset->url }}"
                                            alt="{{ $asset->alt_text ?: $title->name }}"
                                            class="sb-media-viewer-thumb-image"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="sb-media-hero-panel space-y-5">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="sb-media-kicker">Media gallery</span>
                                <span class="sb-cast-meta-item">{{ str($title->title_type->value)->headline() }}</span>
                                @if ($title->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}" class="sb-cast-meta-item">
                                        {{ $title->release_year }}
                                    </a>
                                @endif
                                @if ($title->runtime_minutes)
                                    <span class="sb-cast-meta-item">{{ $title->runtime_minutes }} min</span>
                                @endif
                                @if ($title->statistic?->average_rating)
                                    <span class="sb-cast-meta-item sb-cast-meta-item--rating">
                                        <x-ui.icon name="star" class="size-4" />
                                        {{ number_format((float) $title->statistic->average_rating, 1) }}
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $title->name }} Media Gallery</x-ui.heading>
                                <x-ui.text class="sb-detail-copy text-base">
                                    {{ $heroCopy }}
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="sb-media-stat">
                                    <div class="sb-cast-summary-label">Posters</div>
                                    <div class="sb-cast-summary-value">{{ number_format($posterAssets->count()) }}</div>
                                </div>
                                <div class="sb-media-stat">
                                    <div class="sb-cast-summary-label">Stills</div>
                                    <div class="sb-cast-summary-value">{{ number_format($stillAssets->count()) }}</div>
                                </div>
                                <div class="sb-media-stat">
                                    <div class="sb-cast-summary-label">Backdrops</div>
                                    <div class="sb-cast-summary-value">{{ number_format($backdropAssets->count()) }}</div>
                                </div>
                                <div class="sb-media-stat">
                                    <div class="sb-cast-summary-label">Trailers</div>
                                    <div class="sb-cast-summary-value">{{ number_format($trailerAssets->count()) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="sb-media-trailer-spotlight">
                            <div class="flex items-center justify-between gap-3">
                                <div class="sb-cast-summary-label">Trailer spotlight</div>
                                @if ($featuredTrailer)
                                    <x-ui.badge variant="outline" color="amber" icon="play">
                                        {{ str($featuredTrailer->kind->value)->headline() }}
                                    </x-ui.badge>
                                @endif
                            </div>

                            @if ($featuredTrailer)
                                <div class="mt-3 space-y-2">
                                    <div class="sb-media-trailer-title">{{ $featuredTrailerLabel }}</div>
                                    <div class="sb-media-trailer-meta">
                                        @if ($featuredTrailer->provider)
                                            <span>{{ str($featuredTrailer->provider)->headline() }}</span>
                                        @endif
                                        @if ($featuredTrailerDuration)
                                            <span>{{ $featuredTrailerDuration }}</span>
                                        @endif
                                        @if ($featuredTrailer->published_at)
                                            <span>{{ $featuredTrailer->published_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($featuredTrailer->url))
                                    <div class="mt-4">
                                        <x-ui.button as="a" :href="$featuredTrailer->url" variant="outline" color="amber" icon="play" target="_blank" rel="noreferrer">
                                            Watch featured trailer
                                        </x-ui.button>
                                    </div>
                                @endif
                            @else
                                <x-ui.text class="mt-3 text-sm text-neutral-500 dark:text-neutral-400">
                                    No published trailers are attached to this title yet.
                                </x-ui.text>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" color="amber" icon="arrow-left">
                                Back to title
                            </x-ui.button>
                            <x-ui.button as="a" href="#title-media-trailers" variant="ghost" icon="play">
                                Jump to trailers
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <nav class="sb-media-subnav" aria-label="Media sections">
            <a href="#title-media-posters" class="sb-media-subnav-link">Posters</a>
            <a href="#title-media-stills" class="sb-media-subnav-link">Stills</a>
            <a href="#title-media-backdrops" class="sb-media-subnav-link">Backdrops</a>
            <a href="#title-media-trailers" class="sb-media-subnav-link">Trailers</a>
        </nav>

        <x-ui.card id="title-media-posters" class="sb-detail-section sb-media-section !max-w-none" data-slot="title-media-posters">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Posters</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Primary key art, campaign sheets, and vertical one-sheets attached directly to this title.
                        </x-ui.text>
                    </div>
                    <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($posterAssets->count()) }} posters</x-ui.badge>
                </div>

                @if ($posterAssets->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($posterAssets as $mediaAsset)
                            <figure class="sb-media-image-card sb-media-image-card--poster">
                                <img
                                    src="{{ $mediaAsset->url }}"
                                    alt="{{ $mediaAsset->alt_text ?: $title->name }}"
                                    class="sb-media-card-image sb-media-card-image--poster"
                                    loading="lazy"
                                >
                                <figcaption class="sb-media-image-overlay">
                                    <div class="sb-media-image-title">{{ $mediaAsset->caption ?: $mediaAsset->alt_text ?: $title->name.' poster' }}</div>
                                    <div class="sb-media-card-meta">
                                        @if ($mediaAsset->is_primary)
                                            <span>Primary</span>
                                        @endif
                                        <span>{{ str($mediaAsset->kind->value)->headline() }}</span>
                                    </div>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No posters are published yet.</x-ui.heading>
                    </x-ui.empty>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card id="title-media-stills" class="sb-detail-section sb-media-section !max-w-none" data-slot="title-media-stills">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Stills</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Production stills, scene captures, and gallery imagery selected for a cleaner editorial gallery surface.
                        </x-ui.text>
                    </div>
                    <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($stillAssets->count()) }} stills</x-ui.badge>
                </div>

                @if ($stillAssets->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($stillAssets as $mediaAsset)
                            <figure class="sb-media-image-card">
                                <img
                                    src="{{ $mediaAsset->url }}"
                                    alt="{{ $mediaAsset->alt_text ?: $title->name }}"
                                    class="sb-media-card-image"
                                    loading="lazy"
                                >
                                <figcaption class="sb-media-image-overlay">
                                    <div class="sb-media-image-title">{{ $mediaAsset->caption ?: $mediaAsset->alt_text ?: $title->name.' still' }}</div>
                                    <div class="sb-media-card-meta">
                                        <span>{{ str($mediaAsset->kind->value)->headline() }}</span>
                                        @if ($mediaAsset->published_at)
                                            <span>{{ $mediaAsset->published_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="rectangle-stack" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No stills are published yet.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            The gallery is ready for production stills and editorial image drops as the media archive expands.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card id="title-media-backdrops" class="sb-detail-section sb-media-section !max-w-none" data-slot="title-media-backdrops">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Backdrops</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Wide-format hero artwork for atmospheric page headers, viewer surfaces, and cinematic browsing.
                        </x-ui.text>
                    </div>
                    <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($backdropAssets->count()) }} backdrops</x-ui.badge>
                </div>

                @if ($backdropAssets->isNotEmpty())
                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach ($backdropAssets as $mediaAsset)
                            <figure class="sb-media-image-card sb-media-image-card--backdrop">
                                <img
                                    src="{{ $mediaAsset->url }}"
                                    alt="{{ $mediaAsset->alt_text ?: $title->name }}"
                                    class="sb-media-card-image sb-media-card-image--backdrop"
                                    loading="lazy"
                                >
                                <figcaption class="sb-media-image-overlay">
                                    <div class="sb-media-image-title">{{ $mediaAsset->caption ?: $mediaAsset->alt_text ?: $title->name.' backdrop' }}</div>
                                    <div class="sb-media-card-meta">
                                        @if ($mediaAsset->is_primary)
                                            <span>Primary</span>
                                        @endif
                                        <span>{{ str($mediaAsset->kind->value)->headline() }}</span>
                                    </div>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No backdrops are published yet.</x-ui.heading>
                    </x-ui.empty>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card id="title-media-trailers" class="sb-detail-section sb-media-section !max-w-none" data-slot="title-media-trailers">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Trailers</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Trailer, clip, and featurette records arranged in a cleaner dark gallery instead of a noisy public feed.
                        </x-ui.text>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge variant="outline" color="neutral" icon="play">{{ number_format($trailerAssets->count()) }} videos</x-ui.badge>
                        <x-ui.link :href="route('public.trailers.latest')" variant="ghost" iconAfter="arrow-right">
                            Browse trailers
                        </x-ui.link>
                    </div>
                </div>

                @if ($trailerAssets->isNotEmpty())
                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(19rem,0.8fr)]">
                        <div class="sb-media-trailer-lead">
                            <div class="sb-media-trailer-poster sb-media-trailer-poster--lead">
                                @if ($backdrop ?? $poster)
                                    <img
                                        src="{{ ($backdrop ?? $poster)?->url }}"
                                        alt="{{ ($backdrop ?? $poster)?->alt_text ?: $title->name }}"
                                        class="sb-media-card-image sb-media-card-image--backdrop"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="sb-media-viewer-empty">
                                        <x-ui.icon name="play-circle" class="size-12" />
                                    </div>
                                @endif

                                @if ($leadTrailer)
                                    <div class="sb-media-trailer-badge">
                                        <x-ui.icon name="play" class="size-4" />
                                        {{ str($leadTrailer->kind->value)->headline() }}
                                    </div>
                                @endif
                            </div>

                            @if ($leadTrailer)
                                <div class="sb-media-trailer-lead-body">
                                    <div class="sb-media-kicker">Lead trailer</div>
                                    <div class="sb-media-trailer-lead-title">{{ $leadTrailer->caption ?: str($leadTrailer->kind->value)->headline() }}</div>
                                    <div class="sb-media-trailer-lead-copy">
                                        Editorial lead for this title's video archive, surfaced ahead of clips and supporting featurettes.
                                    </div>
                                    <div class="sb-media-trailer-meta">
                                        @if ($leadTrailer->provider)
                                            <span>{{ str($leadTrailer->provider)->headline() }}</span>
                                        @endif
                                        @if ($leadTrailer->duration_seconds)
                                            <span>{{ max(1, (int) ceil($leadTrailer->duration_seconds / 60)) }} min</span>
                                        @endif
                                        @if ($leadTrailer->published_at)
                                            <span>{{ $leadTrailer->published_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>

                                    @if (filled($leadTrailer->url))
                                        <div class="mt-5 flex flex-wrap gap-3">
                                            <x-ui.button as="a" :href="$leadTrailer->url" variant="outline" color="amber" icon="play" target="_blank" rel="noreferrer">
                                                Open video
                                            </x-ui.button>
                                            <x-ui.button as="a" :href="$leadTrailer->url" variant="ghost" icon="play" target="_blank" rel="noreferrer">
                                                Watch featured trailer
                                            </x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="sb-media-trailer-archive">
                            <div class="space-y-2">
                                <div class="sb-media-kicker">Trailer archive</div>
                                <div class="sb-media-trailer-archive-title">
                                    {{ $trailerArchive->isNotEmpty() ? 'Clips and supporting video records' : 'Single lead record published' }}
                                </div>
                            </div>

                            @if ($trailerArchive->isNotEmpty())
                                <div class="mt-4 grid gap-3">
                                    @foreach ($trailerArchive as $video)
                                        <div class="sb-media-trailer-row">
                                            <div class="sb-media-trailer-row-index">
                                                {{ str_pad((string) ($loop->iteration + 1), 2, '0', STR_PAD_LEFT) }}
                                            </div>

                                            <div class="min-w-0 space-y-1">
                                                <div class="sb-media-trailer-row-title">
                                                    {{ $video->caption ?: str($video->kind->value)->headline() }}
                                                </div>
                                                <div class="sb-media-trailer-meta">
                                                    <span>{{ str($video->kind->value)->headline() }}</span>
                                                    @if ($video->provider)
                                                        <span>{{ str($video->provider)->headline() }}</span>
                                                    @endif
                                                    @if ($video->duration_seconds)
                                                        <span>{{ max(1, (int) ceil($video->duration_seconds / 60)) }} min</span>
                                                    @endif
                                                    @if ($video->published_at)
                                                        <span>{{ $video->published_at->format('M j, Y') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            @if (filled($video->url))
                                                <x-ui.button as="a" :href="$video->url" variant="ghost" icon="play" target="_blank" rel="noreferrer">
                                                    Open video
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-ui.text class="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
                                    This title currently has one published lead trailer and no supporting clips or featurettes.
                                </x-ui.text>
                            @endif
                        </div>
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="play-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No trailers are published yet.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Trailer links will appear here as soon as the public media feed is attached to this title.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </div>
        </x-ui.card>
    </section>
@endsection
