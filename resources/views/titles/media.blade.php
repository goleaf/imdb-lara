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
    <x-catalog.media-lightbox-shell :groups="$imageLightboxGroups" modal-id="title-media-lightbox" class="space-y-6">
        <x-ui.card class="sb-detail-hero sb-media-hero !max-w-none overflow-hidden p-0" data-slot="title-media-hero">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->accessibleAltText($title->name) }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-26"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(112deg,rgba(10,10,9,0.96),rgba(10,10,9,0.86),rgba(10,10,9,0.58))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(12,11,10,0.98),rgba(10,10,9,0.96))]"></div>
                @endif

                <div class="relative p-6 sm:p-7">
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_minmax(19rem,0.92fr)] xl:items-stretch">
                        <div class="space-y-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="sb-page-kicker">Media archive</div>
                                <x-ui.badge variant="outline" color="neutral" icon="film">
                                    {{ str($title->title_type->value)->headline() }}
                                </x-ui.badge>
                                @if ($title->release_year)
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">
                                        {{ $title->release_year }}
                                    </x-ui.badge>
                                @endif
                                @if ($title->runtimeMinutesLabel())
                                    <x-ui.badge variant="outline" color="slate" icon="clock">
                                        {{ $title->runtimeMinutesLabel() }}
                                    </x-ui.badge>
                                @endif
                                @if ($title->statistic?->average_rating)
                                    <x-ui.badge color="amber" icon="star">
                                        {{ number_format((float) $title->statistic->average_rating, 1) }}
                                    </x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-[#d9ccb7]">
                                        {{ $title->name }} media gallery
                                    </div>
                                    <x-ui.heading level="h1" size="xl" class="sb-page-title">
                                        {{ $title->name }}
                                    </x-ui.heading>
                                </div>

                                <x-ui.text class="sb-page-copy max-w-3xl text-base">
                                    {{ $heroCopy }}
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ($heroArchiveCards as $heroArchiveCard)
                                    <a
                                        href="{{ $heroArchiveCard['href'] }}"
                                        class="rounded-[1.2rem] border border-white/10 bg-white/[0.035] p-4 transition hover:border-white/18 hover:bg-white/[0.06]"
                                    >
                                        <div class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-[#cbbb9c]">
                                            {{ $heroArchiveCard['kind']->label() }}
                                        </div>
                                        <div class="mt-2 text-[1.95rem] font-semibold leading-none tracking-[-0.05em] text-[#f7f1e8]">
                                            {{ number_format($heroArchiveCard['count']) }}
                                        </div>
                                        <div class="mt-3 text-sm leading-6 text-[#b6a997]">
                                            {{ $heroArchiveCard['copy'] }}
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <x-catalog.back-link :href="route('public.titles.show', $title)" label="Back to title" />
                                <x-ui.button as="a" href="#title-media-trailers" variant="ghost" icon="play">
                                    Jump to trailers
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="space-y-3" data-slot="title-media-viewer">
                            <div class="overflow-hidden rounded-[1.6rem] border border-white/10 bg-black/25 shadow-[0_34px_90px_rgba(0,0,0,0.32)] backdrop-blur-sm">
                                @if ($viewerAsset)
                                    <button
                                        type="button"
                                        class="block w-full text-left"
                                        x-on:click="openLightboxByUrl(@js($viewerAsset->url))"
                                    >
                                        <img
                                            src="{{ $viewerAsset->url }}"
                                            alt="{{ $viewerAsset->accessibleAltText($title->name) }}"
                                            class="block aspect-[16/10] w-full cursor-zoom-in object-cover"
                                        >
                                    </button>
                                @else
                                    <div class="flex aspect-[16/10] items-center justify-center text-neutral-500">
                                        <x-ui.icon name="photo" class="size-12" />
                                    </div>
                                @endif

                                <div class="space-y-3 border-t border-white/10 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-2">
                                            <div class="sb-media-kicker">Spotlight image</div>
                                            <div class="text-[1.2rem] font-semibold tracking-[-0.03em] text-[#f4eee5]">
                                                {{ $viewerKindLabel }}
                                            </div>
                                            @if ($viewerAsset?->meaningfulCaption())
                                                <div class="text-sm leading-6 text-[#bfb3a3]">
                                                    {{ $viewerAsset->meaningfulCaption() }}
                                                </div>
                                            @endif
                                        </div>

                                        @if ($viewerAsset)
                                            <x-ui.badge variant="outline" color="amber" icon="photo">
                                                Open lightbox
                                            </x-ui.badge>
                                        @endif
                                    </div>

                                    @if ($viewerStripAssets->isNotEmpty())
                                        <div class="flex gap-2 overflow-x-auto pb-1">
                                            @foreach ($viewerStripAssets as $asset)
                                                <button
                                                    type="button"
                                                    class="shrink-0 overflow-hidden rounded-[0.95rem] border {{ $viewerAsset?->id === $asset->id ? 'border-[rgba(214,181,116,0.34)]' : 'border-white/10' }} bg-black/30 transition hover:border-white/20"
                                                    x-on:click="openLightboxByUrl(@js($asset->url))"
                                                >
                                                    <img
                                                        src="{{ $asset->url }}"
                                                        alt="{{ $asset->accessibleAltText($title->name) }}"
                                                        class="h-[4.7rem] w-[3.5rem] object-cover"
                                                    >
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <nav class="sb-media-subnav" aria-label="Media sections">
            @foreach ($mediaSectionLinks as $mediaSectionLink)
                <a href="{{ $mediaSectionLink['href'] }}" class="sb-media-subnav-link">
                    {{ $mediaSectionLink['label'] }} ({{ number_format($mediaSectionLink['count']) }})
                </a>
            @endforeach
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
                    <div class="flex items-center gap-3">
                        <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($posterAssetsPagination->total()) }} posters</x-ui.badge>
                        @if ($posterArchiveHref)
                            <x-ui.link :href="$posterArchiveHref" iconAfter="arrow-right">
                                View all posters
                            </x-ui.link>
                        @endif
                    </div>
                </div>

                @if ($posterAssetsPagination->total() > 0)
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($posterAssetsPagination as $mediaAsset)
                            <button
                                type="button"
                                class="sb-media-lightbox-trigger"
                                x-on:click="openLightbox('posters', {{ $posterLightboxOffset + $loop->index }})"
                            >
                                <figure class="sb-media-image-card sb-media-image-card--poster">
                                    <img
                                        src="{{ $mediaAsset->url }}"
                                        alt="{{ $mediaAsset->accessibleAltText($title->name) }}"
                                        class="sb-media-card-image sb-media-card-image--poster"
                                        loading="lazy"
                                    >
                                    @if ($mediaAsset->meaningfulCaption() || $mediaAsset->is_primary)
                                        <figcaption class="sb-media-image-overlay">
                                            @if ($mediaAsset->meaningfulCaption())
                                                <div class="sb-media-image-title">{{ $mediaAsset->meaningfulCaption() }}</div>
                                            @endif
                                            @if ($mediaAsset->is_primary)
                                                <div class="sb-media-card-meta">
                                                    <span>Primary</span>
                                                </div>
                                            @endif
                                        </figcaption>
                                    @endif
                                </figure>
                            </button>
                        @endforeach
                    </div>

                    @if ($posterAssetsPagination->hasPages())
                        <div class="pt-2">
                            {{ $posterAssetsPagination->onEachSide(1)->links() }}
                        </div>
                    @endif
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
                    <div class="flex items-center gap-3">
                        <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">{{ number_format($stillAssetsPagination->total()) }} stills</x-ui.badge>
                        @if ($stillArchiveHref)
                            <x-ui.link :href="$stillArchiveHref" iconAfter="arrow-right">
                                View all stills
                            </x-ui.link>
                        @endif
                    </div>
                </div>

                @if ($stillAssetsPagination->total() > 0)
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($stillAssetsPagination as $mediaAsset)
                            <button
                                type="button"
                                class="sb-media-lightbox-trigger"
                                x-on:click="openLightbox('stills', {{ $stillLightboxOffset + $loop->index }})"
                            >
                                <figure class="sb-media-image-card">
                                    <img
                                        src="{{ $mediaAsset->url }}"
                                        alt="{{ $mediaAsset->accessibleAltText($title->name) }}"
                                        class="sb-media-card-image"
                                        loading="lazy"
                                    >
                                    @if ($mediaAsset->meaningfulCaption() || $mediaAsset->published_at)
                                        <figcaption class="sb-media-image-overlay">
                                            @if ($mediaAsset->meaningfulCaption())
                                                <div class="sb-media-image-title">{{ $mediaAsset->meaningfulCaption() }}</div>
                                            @endif
                                            @if ($mediaAsset->published_at)
                                                <div class="sb-media-card-meta">
                                                    <span>{{ $mediaAsset->published_at->format('M j, Y') }}</span>
                                                </div>
                                            @endif
                                        </figcaption>
                                    @endif
                                </figure>
                            </button>
                        @endforeach
                    </div>

                    @if ($stillAssetsPagination->hasPages())
                        <div class="pt-2">
                            {{ $stillAssetsPagination->onEachSide(1)->links() }}
                        </div>
                    @endif
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
                    <div class="flex items-center gap-3">
                        <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($backdropAssetsPagination->total()) }} backdrops</x-ui.badge>
                        @if ($backdropArchiveHref)
                            <x-ui.link :href="$backdropArchiveHref" iconAfter="arrow-right">
                                View all backdrops
                            </x-ui.link>
                        @endif
                    </div>
                </div>

                @if ($backdropAssetsPagination->total() > 0)
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($backdropAssetsPagination as $mediaAsset)
                            <button
                                type="button"
                                class="sb-media-lightbox-trigger"
                                x-on:click="openLightbox('backdrops', {{ $backdropLightboxOffset + $loop->index }})"
                            >
                                <figure class="sb-media-image-card sb-media-image-card--backdrop">
                                    <img
                                        src="{{ $mediaAsset->url }}"
                                        alt="{{ $mediaAsset->accessibleAltText($title->name) }}"
                                        class="sb-media-card-image sb-media-card-image--backdrop"
                                        loading="lazy"
                                    >
                                    @if ($mediaAsset->meaningfulCaption() || $mediaAsset->is_primary)
                                        <figcaption class="sb-media-image-overlay">
                                            @if ($mediaAsset->meaningfulCaption())
                                                <div class="sb-media-image-title">{{ $mediaAsset->meaningfulCaption() }}</div>
                                            @endif
                                            @if ($mediaAsset->is_primary)
                                                <div class="sb-media-card-meta">
                                                    <span>Primary</span>
                                                </div>
                                            @endif
                                        </figcaption>
                                    @endif
                                </figure>
                            </button>
                        @endforeach
                    </div>

                    @if ($backdropAssetsPagination->hasPages())
                        <div class="pt-2">
                            {{ $backdropAssetsPagination->onEachSide(1)->links() }}
                        </div>
                    @endif
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
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.badge variant="outline" color="neutral" icon="play">{{ number_format($trailerListItems->count()) }} videos</x-ui.badge>
                        @if ($trailerArchiveHref)
                            <x-ui.link :href="$trailerArchiveHref" iconAfter="arrow-right">
                                View all trailers
                            </x-ui.link>
                        @endif
                        <x-ui.link.light :href="route('public.trailers.latest')" iconAfter="arrow-right">
                            Browse trailers
                        </x-ui.link.light>
                    </div>
                </div>

                @if ($trailerListItems->isNotEmpty())
                    <div class="sb-media-trailer-list" data-slot="title-media-trailer-list">
                        @foreach ($trailerListItems as $trailerListItem)
                            <article class="sb-media-trailer-item" data-slot="title-media-trailer-item">
                                <div class="sb-media-trailer-item-media">
                                    @if ($trailerPreviewAsset)
                                        <img
                                            src="{{ $trailerPreviewAsset->url }}"
                                            alt="{{ $trailerPreviewAsset->accessibleAltText($title->name) }}"
                                            class="sb-media-trailer-item-image"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="sb-media-viewer-empty">
                                            <x-ui.icon name="play-circle" class="size-12" />
                                        </div>
                                    @endif

                                    <div class="sb-media-trailer-item-index">
                                        {{ $trailerListItem['indexLabel'] }}
                                    </div>
                                </div>

                                <div class="sb-media-trailer-item-body">
                                    <div class="sb-media-trailer-item-copy">{{ $trailerListItem['label'] }}</div>
                                    <div class="sb-media-trailer-meta">
                                        <span>{{ $trailerListItem['kindLabel'] }}</span>
                                        @if ($trailerListItem['video']->durationMinutesLabel())
                                            <span>{{ $trailerListItem['video']->durationMinutesLabel() }}</span>
                                        @endif
                                        @if ($trailerListItem['video']->published_at)
                                            <span>{{ $trailerListItem['video']->published_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($trailerListItem['video']->url))
                                    <div class="sb-media-trailer-item-actions">
                                        <x-ui.button.light-action :href="$trailerListItem['video']->url" icon="play" open-in-new-tab>
                                            Open video
                                        </x-ui.button.light-action>
                                    </div>
                                @endif
                            </article>
                        @endforeach
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

    </x-catalog.media-lightbox-shell>
@endsection
