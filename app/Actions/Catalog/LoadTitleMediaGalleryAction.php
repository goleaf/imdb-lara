<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadTitleMediaGalleryAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     viewerAsset: MediaAsset|null,
     *     featuredTrailer: MediaAsset|null,
     *     posterAssets: Collection<int, MediaAsset>,
     *     stillAssets: Collection<int, MediaAsset>,
     *     backdropAssets: Collection<int, MediaAsset>,
     *     trailerAssets: Collection<int, MediaAsset>,
     *     viewerStripAssets: Collection<int, MediaAsset>,
     *     leadTrailer: MediaAsset|null,
     *     trailerArchive: Collection<int, MediaAsset>,
     *     totalImageAssets: int,
     *     heroCopy: string,
     *     viewerKindLabel: string,
     *     featuredTrailerLabel: string|null,
     *     featuredTrailerDuration: string|null,
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'genres:id,name,slug',
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'width',
                    'height',
                    'provider',
                    'provider_key',
                    'language',
                    'duration_seconds',
                    'is_primary',
                    'position',
                    'published_at',
                ])
                ->ordered(),
        ]);

        /** @var Collection<int, MediaAsset> $allMediaAssets */
        $allMediaAssets = $title->mediaAssets->values();

        /** @var Collection<int, MediaAsset> $imageAssets */
        $imageAssets = $allMediaAssets
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array($mediaAsset->kind, [
                MediaKind::Poster,
                MediaKind::Backdrop,
                MediaKind::Gallery,
                MediaKind::Still,
            ], true))
            ->values();

        /** @var Collection<int, MediaAsset> $videoAssets */
        $videoAssets = $allMediaAssets
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array($mediaAsset->kind, [
                MediaKind::Trailer,
                MediaKind::Clip,
                MediaKind::Featurette,
            ], true))
            ->values();

        $posterAssets = $imageAssets
            ->where('kind', MediaKind::Poster)
            ->values();
        $stillAssets = $imageAssets
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array($mediaAsset->kind, [MediaKind::Still, MediaKind::Gallery], true))
            ->values();
        $backdropAssets = $imageAssets
            ->where('kind', MediaKind::Backdrop)
            ->values();

        $poster = MediaAsset::preferredFrom($posterAssets, MediaKind::Poster)
            ?? MediaAsset::preferredFrom($imageAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($backdropAssets, MediaKind::Backdrop)
            ?? MediaAsset::preferredFrom($imageAssets, MediaKind::Backdrop, MediaKind::Poster);
        $viewerAsset = MediaAsset::preferredFrom($imageAssets, MediaKind::Backdrop, MediaKind::Still, MediaKind::Gallery, MediaKind::Poster);
        $featuredTrailer = MediaAsset::preferredFrom($videoAssets, MediaKind::Trailer, MediaKind::Featurette, MediaKind::Clip);
        $leadTrailer = $featuredTrailer ?: $videoAssets->first();
        $viewerStripAssets = collect([$viewerAsset])
            ->merge($posterAssets->take(1))
            ->merge($stillAssets->take(2))
            ->merge($backdropAssets->take(1))
            ->filter()
            ->unique('id')
            ->take(4)
            ->values();

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'viewerAsset' => $viewerAsset,
            'featuredTrailer' => $featuredTrailer,
            'posterAssets' => $posterAssets,
            'stillAssets' => $stillAssets,
            'backdropAssets' => $backdropAssets,
            'trailerAssets' => $videoAssets,
            'viewerStripAssets' => $viewerStripAssets,
            'leadTrailer' => $leadTrailer,
            'trailerArchive' => $videoAssets
                ->reject(fn (MediaAsset $video): bool => $leadTrailer && $video->id === $leadTrailer->id)
                ->values(),
            'totalImageAssets' => $imageAssets->count(),
            'heroCopy' => $title->tagline ?: $title->plot_outline ?: 'A premium catalog view of posters, stills, backdrops, and trailers attached to this title.',
            'viewerKindLabel' => $viewerAsset?->kind?->label() ?? 'Gallery viewer',
            'featuredTrailerLabel' => $featuredTrailer?->caption ?: $featuredTrailer?->kind?->label(),
            'featuredTrailerDuration' => $featuredTrailer?->durationMinutesLabel(),
        ];
    }
}
