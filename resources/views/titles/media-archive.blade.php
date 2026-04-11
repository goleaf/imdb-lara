@extends('layouts.public')

@section('title', $title->name.' '.$archiveKind->label())
@section('meta_description', 'Browse '.$archiveKind->label().' from the media archive for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="$overviewHref">Media Gallery</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $archiveKind->label() }}</x-ui.breadcrumbs.item>
@endsection

@php
    $archiveLinks = collect(App\Enums\TitleMediaArchiveKind::cases())
        ->map(function (App\Enums\TitleMediaArchiveKind $candidateKind) use ($title, $mediaCounts, $overviewHref): array {
            $count = $mediaCounts[$candidateKind->value] ?? 0;

            return [
                'kind' => $candidateKind,
                'count' => $count,
                'href' => $count > 0
                    ? route('public.titles.media.archive', ['title' => $title, 'archive' => $candidateKind->value])
                    : $overviewHref.'#'.$candidateKind->sectionId(),
            ];
        })
        ->values();
@endphp

@section('content')
    @if ($archiveKind->isImageArchive())
        <x-catalog.media-lightbox-shell :groups="$imageLightboxGroups" modal-id="title-media-archive-lightbox" class="space-y-6">
            <x-seo.pagination-links :paginator="$archiveAssetsPagination" />

            <x-ui.card data-slot="title-media-archive-hero" class="sb-page-hero !max-w-none p-6 sm:p-7">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_auto] xl:items-start">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="sb-page-kicker">{{ $archiveKind->label() }} archive</div>
                            <x-ui.badge variant="outline" color="neutral" :icon="$archiveKind->badgeIcon()">
                                {{ number_format($archiveAssetCount) }} {{ str($archiveKind->label())->lower() }}
                            </x-ui.badge>
                        </div>

                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="sb-page-title">
                                {{ $title->name }} {{ $archiveKind->label() }}
                            </x-ui.heading>

                            <x-ui.text class="sb-page-copy max-w-4xl text-base">
                                {{ $archiveKind->archiveDescription() }}
                            </x-ui.text>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-catalog.back-link :href="route('public.titles.show', $title)" label="Back to title" />
                            <x-ui.button as="a" :href="$overviewHref" variant="ghost" icon="photo">
                                Back to media gallery
                            </x-ui.button>
                        </div>
                    </div>

                    <div class="rounded-[1.2rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Visible archive</div>
                        <div class="mt-2 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ number_format($archiveAssetsPagination->total()) }} items
                        </div>
                        <div class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                            Open any image to inspect it in the lightbox, then step through the current archive page from the thumb rail.
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <nav class="sb-media-subnav" aria-label="Media archive navigation">
                @foreach ($archiveLinks as $archiveLink)
                    <a
                        href="{{ $archiveLink['href'] }}"
                        class="sb-media-subnav-link {{ $archiveKind === $archiveLink['kind'] ? 'font-semibold text-neutral-950 dark:text-white' : '' }}"
                        @if ($archiveKind === $archiveLink['kind']) aria-current="page" @endif
                    >
                        {{ $archiveLink['kind']->label() }} ({{ number_format($archiveLink['count']) }})
                    </a>
                @endforeach
            </nav>

            <x-ui.card :id="$archiveKind->archiveSectionId()" data-slot="title-media-archive-grid" class="sb-detail-section sb-media-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">{{ $archiveKind->label() }}</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $archiveKind->archiveDescription() }}
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" :icon="$archiveKind->badgeIcon()">
                            {{ number_format($archiveAssetsPagination->total()) }} {{ str($archiveKind->label())->lower() }}
                        </x-ui.badge>
                    </div>

                    @if ($archiveAssetsPagination->total() > 0)
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                            @foreach ($archiveAssetsPagination as $mediaAsset)
                                <button
                                    type="button"
                                    class="sb-media-lightbox-trigger"
                                    x-on:click="openLightbox(@js($archiveKind->value), {{ $loop->index }})"
                                >
                                    <figure class="sb-media-image-card{{ $archiveKind === App\Enums\TitleMediaArchiveKind::Posters ? ' sb-media-image-card--poster' : '' }}{{ $archiveKind === App\Enums\TitleMediaArchiveKind::Backdrops ? ' sb-media-image-card--backdrop' : '' }}">
                                        <img
                                            src="{{ $mediaAsset->url }}"
                                            alt="{{ $mediaAsset->accessibleAltText($title->name) }}"
                                            class="sb-media-card-image{{ $archiveKind === App\Enums\TitleMediaArchiveKind::Posters ? ' sb-media-card-image--poster' : '' }}{{ $archiveKind === App\Enums\TitleMediaArchiveKind::Backdrops ? ' sb-media-card-image--backdrop' : '' }}"
                                            loading="lazy"
                                        >
                                        @if ($mediaAsset->meaningfulCaption() || $mediaAsset->is_primary || $mediaAsset->published_at)
                                            <figcaption class="sb-media-image-overlay">
                                                @if ($mediaAsset->meaningfulCaption())
                                                    <div class="sb-media-image-title">{{ $mediaAsset->meaningfulCaption() }}</div>
                                                @endif

                                                @if ($mediaAsset->is_primary || $mediaAsset->published_at)
                                                    <div class="sb-media-card-meta">
                                                        @if ($mediaAsset->is_primary)
                                                            <span>Primary</span>
                                                        @endif

                                                        @if ($mediaAsset->published_at)
                                                            <span>{{ $mediaAsset->published_at->format('M j, Y') }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </figcaption>
                                        @endif
                                    </figure>
                                </button>
                            @endforeach
                        </div>

                        @if ($archiveAssetsPagination->hasPages())
                            <div class="pt-2">
                                {{ $archiveAssetsPagination->onEachSide(1)->links() }}
                            </div>
                        @endif
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon :name="$archiveKind->badgeIcon()" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">{{ $archiveKind->emptyHeading() }}</x-ui.heading>
                            @if ($archiveKind->emptyCopy())
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    {{ $archiveKind->emptyCopy() }}
                                </x-ui.text>
                            @endif
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        </x-catalog.media-lightbox-shell>
    @else
        <section class="space-y-6">
            <x-seo.pagination-links :paginator="$trailerAssetsPagination" />

            <x-ui.card data-slot="title-media-archive-hero" class="sb-page-hero !max-w-none p-6 sm:p-7">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_auto] xl:items-start">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="sb-page-kicker">Trailer archive</div>
                            <x-ui.badge variant="outline" color="neutral" icon="play">
                                {{ number_format($archiveAssetCount) }} videos
                            </x-ui.badge>
                        </div>

                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="sb-page-title">
                                {{ $title->name }} Trailers
                            </x-ui.heading>

                            <x-ui.text class="sb-page-copy max-w-4xl text-base">
                                {{ $archiveKind->archiveDescription() }}
                            </x-ui.text>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-catalog.back-link :href="route('public.titles.show', $title)" label="Back to title" />
                            <x-ui.button as="a" :href="$overviewHref" variant="ghost" icon="play">
                                Back to media gallery
                            </x-ui.button>
                        </div>
                    </div>

                    <div class="rounded-[1.2rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Video archive</div>
                        <div class="mt-2 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            {{ number_format($trailerAssetsPagination->total()) }} links
                        </div>
                        <div class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                            Every trailer row keeps the external IMDb video link plus the caption, runtime, and publish metadata exposed by the imported feed.
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <nav class="sb-media-subnav" aria-label="Media archive navigation">
                @foreach ($archiveLinks as $archiveLink)
                    <a
                        href="{{ $archiveLink['href'] }}"
                        class="sb-media-subnav-link {{ $archiveKind === $archiveLink['kind'] ? 'font-semibold text-neutral-950 dark:text-white' : '' }}"
                        @if ($archiveKind === $archiveLink['kind']) aria-current="page" @endif
                    >
                        {{ $archiveLink['kind']->label() }} ({{ number_format($archiveLink['count']) }})
                    </a>
                @endforeach
            </nav>

            <x-ui.card :id="$archiveKind->archiveSectionId()" data-slot="title-media-archive-trailers" class="sb-detail-section sb-media-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Trailers</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $archiveKind->archiveDescription() }}
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="play">
                            {{ number_format($trailerAssetsPagination->total()) }} videos
                        </x-ui.badge>
                    </div>

                    @if ($trailerAssetsPagination->total() > 0)
                        <div class="sb-media-trailer-list" data-slot="title-media-trailer-list">
                            @foreach ($trailerAssetsPagination as $video)
                                @php($videoLabel = $video->name ?: $video->meaningfulCaption() ?: str($video->kind->value)->headline())
                                @php($videoCopy = $video->meaningfulCaption() ?: 'IMDb video record linked to this title.')

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
                                            {{ str_pad((string) (($trailerAssetsPagination->currentPage() - 1) * $trailerAssetsPagination->perPage() + $loop->iteration), 2, '0', STR_PAD_LEFT) }}
                                        </div>
                                    </div>

                                    <div class="sb-media-trailer-item-body">
                                        <div class="space-y-2">
                                            <div class="sb-media-trailer-item-copy">{{ $videoCopy }}</div>

                                            <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300">
                                                <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                    {{ $videoLabel }}
                                                </span>
                                                <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                    {{ str($video->kind->value)->headline() }}
                                                </span>
                                                <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                    IMDb
                                                </span>
                                                @if ($video->durationMinutesLabel())
                                                    <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                        {{ $video->durationMinutesLabel() }}
                                                    </span>
                                                @endif
                                                @if ($video->published_at)
                                                    <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                        {{ $video->published_at->format('M j, Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if (filled($video->url))
                                        <div class="sb-media-trailer-item-actions">
                                            <x-ui.button.light-action :href="$video->url" icon="play" open-in-new-tab>
                                                Open video
                                            </x-ui.button.light-action>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        @if ($trailerAssetsPagination->hasPages())
                            <div class="pt-2">
                                {{ $trailerAssetsPagination->onEachSide(1)->links() }}
                            </div>
                        @endif
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="play-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">{{ $archiveKind->emptyHeading() }}</x-ui.heading>
                            @if ($archiveKind->emptyCopy())
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    {{ $archiveKind->emptyCopy() }}
                                </x-ui.text>
                            @endif
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        </section>
    @endif
@endsection
