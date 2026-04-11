<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleMediaArchiveKind;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class LoadTitleMediaArchiveAction
{
    private const IMAGE_PAGE_SIZE = 24;

    private const TRAILER_PAGE_SIZE = 12;

    public function __construct(
        private readonly BuildCatalogMediaLightboxGroupAction $buildCatalogMediaLightboxGroup,
        private readonly LoadTitleMediaGalleryAction $loadTitleMediaGallery,
    ) {}

    /**
     * @return array{
     *     title: Title,
     *     archiveKind: TitleMediaArchiveKind,
     *     mediaCounts: array{posters: int, stills: int, backdrops: int, trailers: int},
     *     archiveAssetCount: int,
     *     archiveAssetsPagination: LengthAwarePaginator<int, CatalogMediaAsset>|null,
     *     trailerAssetsPagination: LengthAwarePaginator<int, CatalogMediaAsset>|null,
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
     *     trailerPreviewAsset: CatalogMediaAsset|null,
     *     overviewHref: string,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title, TitleMediaArchiveKind $archiveKind): array
    {
        $galleryPayload = $this->loadTitleMediaGallery->handle($title);
        $mediaCounts = [
            'posters' => $galleryPayload['posterAssets']->count(),
            'stills' => $galleryPayload['stillAssets']->count(),
            'backdrops' => $galleryPayload['backdropAssets']->count(),
            'trailers' => $galleryPayload['trailerAssets']->count(),
        ];
        $canonical = route('public.titles.media.archive', [
            'title' => $title,
            'archive' => $archiveKind->value,
        ]);
        $openGraphImage = $galleryPayload['backdrop']?->url
            ?? $galleryPayload['poster']?->url;

        if ($archiveKind->isImageArchive()) {
            $archiveAssets = $this->imageArchiveAssets($galleryPayload, $archiveKind);
            $archiveAssetsPagination = $this->paginateAssets(
                $archiveAssets,
                self::IMAGE_PAGE_SIZE,
                'page',
                $archiveKind->archiveSectionId(),
            );
            $lightboxAssets = collect($archiveAssetsPagination->items())
                ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
                ->values();

            return [
                'title' => $title,
                'archiveKind' => $archiveKind,
                'mediaCounts' => $mediaCounts,
                'archiveAssetCount' => $archiveAssets->count(),
                'archiveAssetsPagination' => $archiveAssetsPagination,
                'trailerAssetsPagination' => null,
                'imageLightboxGroups' => [
                    $archiveKind->value => $this->buildCatalogMediaLightboxGroup->handle(
                        $archiveKind->label(),
                        $lightboxAssets,
                    ),
                ],
                'trailerPreviewAsset' => null,
                'overviewHref' => route('public.titles.media', $title),
                'seo' => new PageSeoData(
                    title: $title->name.' '.$archiveKind->label(),
                    description: 'Browse '.$archiveKind->label().' from the media archive for '.$title->name.'.',
                    canonical: $canonical,
                    openGraphType: 'article',
                    openGraphImage: $openGraphImage,
                    openGraphImageAlt: $title->name.' '.$archiveKind->singularLabel(),
                    breadcrumbs: [
                        ['label' => 'Home', 'href' => route('public.home')],
                        ['label' => 'Titles', 'href' => route('public.titles.index')],
                        ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                        ['label' => 'Media Gallery', 'href' => route('public.titles.media', $title)],
                        ['label' => $archiveKind->label()],
                    ],
                    paginationPageName: 'page',
                ),
            ];
        }

        $trailerAssetsPagination = $this->paginateAssets(
            $galleryPayload['trailerAssets'],
            self::TRAILER_PAGE_SIZE,
            'page',
            $archiveKind->archiveSectionId(),
        );

        return [
            'title' => $title,
            'archiveKind' => $archiveKind,
            'mediaCounts' => $mediaCounts,
            'archiveAssetCount' => $galleryPayload['trailerAssets']->count(),
            'archiveAssetsPagination' => null,
            'trailerAssetsPagination' => $trailerAssetsPagination,
            'imageLightboxGroups' => [],
            'trailerPreviewAsset' => $galleryPayload['backdrop']
                ?? $galleryPayload['viewerAsset']
                ?? $galleryPayload['poster'],
            'overviewHref' => route('public.titles.media', $title),
            'seo' => new PageSeoData(
                title: $title->name.' Trailers',
                description: 'Browse trailer, clip, and featurette records from the media archive for '.$title->name.'.',
                canonical: $canonical,
                openGraphType: 'article',
                openGraphImage: $openGraphImage,
                openGraphImageAlt: $title->name.' trailer archive',
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Media Gallery', 'href' => route('public.titles.media', $title)],
                    ['label' => $archiveKind->label()],
                ],
                paginationPageName: 'page',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $galleryPayload
     * @return Collection<int, CatalogMediaAsset>
     */
    private function imageArchiveAssets(array $galleryPayload, TitleMediaArchiveKind $archiveKind): Collection
    {
        return match ($archiveKind) {
            TitleMediaArchiveKind::Posters => $galleryPayload['posterAssets'],
            TitleMediaArchiveKind::Stills => $galleryPayload['stillAssets'],
            TitleMediaArchiveKind::Backdrops => $galleryPayload['backdropAssets'],
            TitleMediaArchiveKind::Trailers => collect(),
        };
    }

    /**
     * @param  Collection<int, CatalogMediaAsset>  $assets
     * @return LengthAwarePaginator<int, CatalogMediaAsset>
     */
    private function paginateAssets(Collection $assets, int $perPage, string $pageName, string $fragment): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage($pageName);

        $paginator = new LengthAwarePaginator(
            $assets->forPage($currentPage, $perPage)->values(),
            $assets->count(),
            $perPage,
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
