<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadTitleMediaGalleryAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: CatalogMediaAsset|null,
     *     backdrop: CatalogMediaAsset|null,
     *     viewerAsset: CatalogMediaAsset|null,
     *     featuredTrailer: CatalogMediaAsset|null,
     *     posterAssets: Collection<int, CatalogMediaAsset>,
     *     stillAssets: Collection<int, CatalogMediaAsset>,
     *     backdropAssets: Collection<int, CatalogMediaAsset>,
     *     trailerAssets: Collection<int, CatalogMediaAsset>,
     *     viewerStripAssets: Collection<int, CatalogMediaAsset>,
     *     leadTrailer: CatalogMediaAsset|null,
     *     trailerArchive: Collection<int, CatalogMediaAsset>,
     *     totalImageAssets: int,
     *     heroCopy: string,
     *     viewerKindLabel: string,
     *     featuredTrailerLabel: string|null,
     *     featuredTrailerDuration: string|null,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'statistic:movie_id,aggregate_rating,vote_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'titleVideos:imdb_id,movie_id,video_type_id,name,description,width,height,runtime_seconds,position',
            'titleVideos.videoType:id,name',
            'primaryImageRecord:movie_id,url,width,height,type',
            'plotRecord:movie_id,plot',
        ]);

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $groupedAssets = $title->groupedMediaAssetsByKind();
        $posterAssets = $this->resolveGroupedAssets($groupedAssets, MediaKind::Poster);
        $stillAssets = $this->resolveGroupedAssets($groupedAssets, MediaKind::Still, MediaKind::Gallery);
        $backdropAssets = $this->resolveGroupedAssets($groupedAssets, MediaKind::Backdrop);
        $trailerAssets = $this->resolveGroupedAssets(
            $groupedAssets,
            MediaKind::Trailer,
            MediaKind::Featurette,
            MediaKind::Clip,
        );
        $viewerAsset = CatalogMediaAsset::preferredFrom(
            $stillAssets->concat($backdropAssets)->concat($posterAssets),
            MediaKind::Backdrop,
            MediaKind::Still,
            MediaKind::Gallery,
            MediaKind::Poster,
        );
        $featuredTrailer = CatalogMediaAsset::preferredFrom(
            $trailerAssets,
            MediaKind::Trailer,
            MediaKind::Featurette,
            MediaKind::Clip,
        );
        $leadTrailer = $featuredTrailer ?? $trailerAssets->first();
        $viewerStripAssets = collect([$viewerAsset])
            ->merge($posterAssets->take(1))
            ->merge($stillAssets->take(2))
            ->merge($backdropAssets->take(1))
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->unique('url')
            ->take(4)
            ->values();
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'viewerAsset' => $viewerAsset,
            'featuredTrailer' => $featuredTrailer,
            'posterAssets' => $posterAssets,
            'stillAssets' => $stillAssets,
            'backdropAssets' => $backdropAssets,
            'trailerAssets' => $trailerAssets,
            'viewerStripAssets' => $viewerStripAssets,
            'leadTrailer' => $leadTrailer,
            'trailerArchive' => $trailerAssets
                ->reject(fn (CatalogMediaAsset $video): bool => $leadTrailer !== null && $video->url === $leadTrailer->url)
                ->values(),
            'totalImageAssets' => $posterAssets->count() + $stillAssets->count() + $backdropAssets->count(),
            'heroCopy' => $title->summaryText() ?: 'A premium catalog view of posters, stills, backdrops, and trailers attached to this title.',
            'viewerKindLabel' => $viewerAsset?->kindLabel() ?? 'Gallery viewer',
            'featuredTrailerLabel' => $featuredTrailer?->caption ?: $featuredTrailer?->alt_text ?: $featuredTrailer?->kindLabel(),
            'featuredTrailerDuration' => $featuredTrailer?->durationMinutesLabel(),
            'seo' => new PageSeoData(
                title: $title->name.' Media Gallery',
                description: 'Browse posters, stills, backdrops, and trailers for '.$title->name.'.',
                canonical: route('public.titles.media', $title),
                openGraphType: $openGraphType,
                openGraphImage: ($backdrop ?? $poster)?->url,
                openGraphImageAlt: ($backdrop ?? $poster)?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Media Gallery'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    /**
     * @param  Collection<string, Collection<int, CatalogMediaAsset>>  $groupedAssets
     * @return Collection<int, CatalogMediaAsset>
     */
    private function resolveGroupedAssets(Collection $groupedAssets, MediaKind ...$kinds): Collection
    {
        return collect($kinds)
            ->flatMap(
                fn (MediaKind $kind): Collection => $groupedAssets->get($kind->value, collect()),
            )
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->unique('url')
            ->values();
    }
}
