<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class LoadTitleMediaGalleryAction
{
    private const IMAGES_PER_PAGE = 8;

    public function __construct(
        private readonly BuildCatalogMediaLightboxGroupAction $buildCatalogMediaLightboxGroup,
    ) {}

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
     *     posterAssetsPagination: LengthAwarePaginator<int, CatalogMediaAsset>,
     *     stillAssetsPagination: LengthAwarePaginator<int, CatalogMediaAsset>,
     *     backdropAssetsPagination: LengthAwarePaginator<int, CatalogMediaAsset>,
     *     imageLightboxGroups: array<string, array{
     *         label: string,
     *         items: list<array{
     *             id: string,
     *             url: string,
     *             altText: string,
     *             caption: string,
     *             meta: list<string>
     *         }>
     *     }>,
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
        $title->loadMissing(Title::catalogMediaRelations());

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $groupedAssets = $title->groupedMediaAssetsByKind();
        $posterAssets = $this->resolveGroupedAssets($groupedAssets, MediaKind::Poster);
        $stillAssets = $this->resolveGroupedAssets($groupedAssets, MediaKind::Still, MediaKind::Gallery);
        $backdropAssets = $this->resolveGroupedAssets($groupedAssets, MediaKind::Backdrop);
        $posterAssetsPagination = $this->paginateAssets($posterAssets, 'posters_page', 'title-media-posters');
        $stillAssetsPagination = $this->paginateAssets($stillAssets, 'stills_page', 'title-media-stills');
        $backdropAssetsPagination = $this->paginateAssets($backdropAssets, 'backdrops_page', 'title-media-backdrops');
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
            'posterAssetsPagination' => $posterAssetsPagination,
            'stillAssetsPagination' => $stillAssetsPagination,
            'backdropAssetsPagination' => $backdropAssetsPagination,
            'imageLightboxGroups' => [
                'posters' => $this->buildCatalogMediaLightboxGroup->handle('Posters', $posterAssets),
                'stills' => $this->buildCatalogMediaLightboxGroup->handle('Stills', $stillAssets),
                'backdrops' => $this->buildCatalogMediaLightboxGroup->handle('Backdrops', $backdropAssets),
            ],
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
                openGraphImageAlt: ($backdrop ?? $poster)?->accessibleAltText($title->name) ?? $title->name,
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

    /**
     * @param  Collection<int, CatalogMediaAsset>  $assets
     * @return LengthAwarePaginator<int, CatalogMediaAsset>
     */
    private function paginateAssets(Collection $assets, string $pageName, string $fragment): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage($pageName);

        $paginator = new LengthAwarePaginator(
            $assets->forPage($currentPage, self::IMAGES_PER_PAGE)->values(),
            $assets->count(),
            self::IMAGES_PER_PAGE,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );

        return $paginator
            ->withQueryString()
            ->fragment($fragment);
    }
}
