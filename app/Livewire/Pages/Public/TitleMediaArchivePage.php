<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadTitleMediaArchiveAction;
use App\Enums\TitleMediaArchiveKind;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;

class TitleMediaArchivePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?Title $title = null;

    public ?TitleMediaArchiveKind $archiveKind = null;

    public function mount(Title $title, string $archive): void
    {
        abort_unless($title->is_published, 404);

        $this->title = $title;
        $this->archiveKind = TitleMediaArchiveKind::tryFrom($archive);

        abort_unless($this->archiveKind instanceof TitleMediaArchiveKind, 404);
    }

    public function render(LoadTitleMediaArchiveAction $loadTitleMediaArchive): View
    {
        abort_unless($this->title instanceof Title, 404);
        abort_unless($this->archiveKind instanceof TitleMediaArchiveKind, 404);

        $data = $this->normalizeArchiveViewData(
            $loadTitleMediaArchive->handle($this->title, $this->archiveKind),
        );

        return $this->renderPageView(
            'titles.media-archive',
            $data,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeArchiveViewData(array $data): array
    {
        $data = $this->withCollectionDefaults($data, [
            'archiveLinks',
            'trailerArchiveItems',
        ]);
        $data = $this->withDefaultValues($data, [
            'trailerPreviewAsset' => null,
            'archiveAssetsPagination' => null,
            'trailerAssetsPagination' => null,
        ]);

        $title = $this->title;
        $overviewHref = (string) ($data['overviewHref'] ?? route('public.titles.media', $title));
        $mediaCounts = is_array($data['mediaCounts'] ?? null) ? $data['mediaCounts'] : [];

        $archiveLinks = collect(TitleMediaArchiveKind::cases())
            ->map(function (TitleMediaArchiveKind $candidateKind) use ($mediaCounts, $overviewHref, $title): array {
                $count = (int) ($mediaCounts[$candidateKind->value] ?? 0);

                return [
                    'kind' => $candidateKind,
                    'count' => $count,
                    'href' => $count > 0
                        ? route('public.titles.media.archive', ['title' => $title, 'archive' => $candidateKind->value])
                        : $overviewHref.'#'.$candidateKind->sectionId(),
                ];
            })
            ->values();

        if (! $this->archiveKind instanceof TitleMediaArchiveKind || $this->archiveKind->isImageArchive()) {
            return [
                ...$data,
                'archiveLinks' => $archiveLinks,
            ];
        }

        $trailerAssetsPagination = $data['trailerAssetsPagination'];

        if (! $trailerAssetsPagination instanceof LengthAwarePaginator) {
            return [
                ...$data,
                'archiveLinks' => $archiveLinks,
            ];
        }

        return [
            ...$data,
            'archiveLinks' => $archiveLinks,
            'trailerArchiveItems' => $this->mapTrailerArchiveItems($trailerAssetsPagination),
        ];
    }

    /**
     * @return Collection<int, array{
     *     video: CatalogMediaAsset,
     *     indexLabel: string,
     *     label: string,
     *     copy: string,
     *     kindLabel: string
     * }>
     */
    private function mapTrailerArchiveItems(LengthAwarePaginator $videos): Collection
    {
        return collect($videos->items())
            ->values()
            ->map(function (mixed $video, int $index) use ($videos): ?array {
                if (! $video instanceof CatalogMediaAsset) {
                    return null;
                }

                return [
                    'video' => $video,
                    'indexLabel' => str_pad(
                        (string) ((($videos->currentPage() - 1) * $videos->perPage()) + $index + 1),
                        2,
                        '0',
                        STR_PAD_LEFT,
                    ),
                    'label' => $video->name ?: $video->meaningfulCaption() ?: str($video->kind->value)->headline()->toString(),
                    'copy' => $video->meaningfulCaption() ?: 'IMDb video record linked to this title.',
                    'kindLabel' => str($video->kind->value)->headline()->toString(),
                ];
            })
            ->filter()
            ->values();
    }
}
