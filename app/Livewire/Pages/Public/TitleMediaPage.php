<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadTitleMediaGalleryAction;
use App\Enums\TitleMediaArchiveKind;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;

class TitleMediaPage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?Title $title = null;

    public function mount(Title $title): void
    {
        abort_unless($title->is_published, 404);

        $this->title = $title;
    }

    public function render(LoadTitleMediaGalleryAction $loadTitleMediaGallery): View
    {
        abort_unless($this->title instanceof Title, 404);

        $data = $this->normalizeMediaViewData($loadTitleMediaGallery->handle($this->title));

        return $this->renderPageView('titles.media', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeMediaViewData(array $data): array
    {
        $data = $this->withCollectionDefaults($data, [
            'posterAssets',
            'stillAssets',
            'backdropAssets',
            'trailerAssets',
            'viewerStripAssets',
            'trailerArchive',
        ]);
        $data = $this->withDefaultValues($data, [
            'poster' => null,
            'backdrop' => null,
            'viewerAsset' => null,
        ]);

        $title = $this->title;
        $posterAssetsPagination = $data['posterAssetsPagination'];
        $stillAssetsPagination = $data['stillAssetsPagination'];
        $backdropAssetsPagination = $data['backdropAssetsPagination'];

        if (! $posterAssetsPagination instanceof LengthAwarePaginator
            || ! $stillAssetsPagination instanceof LengthAwarePaginator
            || ! $backdropAssetsPagination instanceof LengthAwarePaginator) {
            return $data;
        }

        $mediaSections = collect([
            [
                'kind' => TitleMediaArchiveKind::Posters,
                'count' => $posterAssetsPagination->total(),
                'copy' => 'Campaign art and primary sheets',
                'href' => '#title-media-posters',
                'archiveHref' => $posterAssetsPagination->total() > 1
                    ? route('public.titles.media.archive', ['title' => $title, 'archive' => TitleMediaArchiveKind::Posters->value])
                    : null,
            ],
            [
                'kind' => TitleMediaArchiveKind::Stills,
                'count' => $stillAssetsPagination->total(),
                'copy' => 'Scene captures and editorial frames',
                'href' => '#title-media-stills',
                'archiveHref' => $stillAssetsPagination->total() > 1
                    ? route('public.titles.media.archive', ['title' => $title, 'archive' => TitleMediaArchiveKind::Stills->value])
                    : null,
            ],
            [
                'kind' => TitleMediaArchiveKind::Backdrops,
                'count' => $backdropAssetsPagination->total(),
                'copy' => 'Wide artwork for hero surfaces',
                'href' => '#title-media-backdrops',
                'archiveHref' => $backdropAssetsPagination->total() > 1
                    ? route('public.titles.media.archive', ['title' => $title, 'archive' => TitleMediaArchiveKind::Backdrops->value])
                    : null,
            ],
            [
                'kind' => TitleMediaArchiveKind::Trailers,
                'count' => $data['trailerAssets']->count(),
                'copy' => 'IMDb video records and links',
                'href' => '#title-media-trailers',
                'archiveHref' => $data['trailerAssets']->count() > 1
                    ? route('public.titles.media.archive', ['title' => $title, 'archive' => TitleMediaArchiveKind::Trailers->value])
                    : null,
            ],
        ])->values();

        $trailerPreviewAsset = $data['backdrop'] ?? $data['viewerAsset'] ?? $data['poster'];
        $trailerListAssets = $data['trailerArchive']->isNotEmpty()
            ? $data['trailerArchive']
            : $data['trailerAssets'];
        $trailerListIndexOffset = $data['trailerAssets']->count() === $trailerListAssets->count() ? 0 : 1;

        return [
            ...$data,
            'heroArchiveCards' => $mediaSections,
            'mediaSectionLinks' => $mediaSections->map(fn (array $section): array => [
                'href' => $section['href'],
                'label' => $section['kind']->label(),
                'count' => $section['count'],
            ])->values(),
            'posterArchiveHref' => $mediaSections->firstWhere('kind', TitleMediaArchiveKind::Posters)['archiveHref'] ?? null,
            'stillArchiveHref' => $mediaSections->firstWhere('kind', TitleMediaArchiveKind::Stills)['archiveHref'] ?? null,
            'backdropArchiveHref' => $mediaSections->firstWhere('kind', TitleMediaArchiveKind::Backdrops)['archiveHref'] ?? null,
            'trailerArchiveHref' => $mediaSections->firstWhere('kind', TitleMediaArchiveKind::Trailers)['archiveHref'] ?? null,
            'posterLightboxOffset' => ($posterAssetsPagination->currentPage() - 1) * $posterAssetsPagination->perPage(),
            'stillLightboxOffset' => ($stillAssetsPagination->currentPage() - 1) * $stillAssetsPagination->perPage(),
            'backdropLightboxOffset' => ($backdropAssetsPagination->currentPage() - 1) * $backdropAssetsPagination->perPage(),
            'trailerPreviewAsset' => $trailerPreviewAsset,
            'trailerListAssets' => $trailerListAssets,
            'trailerListItems' => $this->mapTrailerListItems($trailerListAssets, $trailerListIndexOffset),
        ];
    }

    /**
     * @param  Collection<int, CatalogMediaAsset>  $videos
     * @return Collection<int, array{
     *     video: CatalogMediaAsset,
     *     indexLabel: string,
     *     label: string,
     *     kindLabel: string
     * }>
     */
    private function mapTrailerListItems(Collection $videos, int $indexOffset): Collection
    {
        return $videos
            ->values()
            ->map(fn (CatalogMediaAsset $video, int $index): array => [
                'video' => $video,
                'indexLabel' => str_pad((string) ($index + $indexOffset + 1), 2, '0', STR_PAD_LEFT),
                'label' => $video->meaningfulCaption() ?? str($video->kind->value)->headline()->toString(),
                'kindLabel' => str($video->kind->value)->headline()->toString(),
            ]);
    }
}
