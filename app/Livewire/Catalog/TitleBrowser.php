<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TitleBrowser extends Component
{
    use WithPagination;

    /**
     * @var list<string>
     */
    public array $types = [];

    public ?string $genre = null;

    public ?int $year = null;

    public ?string $country = null;

    public string $sort = 'popular';

    public string $pageName = 'titles';

    public int $perPage = 12;

    public bool $excludeEpisodes = true;

    public bool $showSummary = true;

    public string $displayMode = 'catalog';

    public string $emptyHeading = 'No titles match this collection yet.';

    public string $emptyText = 'Check back soon or explore another part of the catalog.';

    #[Computed]
    public function viewData(): array
    {
        $queryAction = app(BuildPublicTitleIndexQueryAction::class);

        $titles = $queryAction
            ->handle([
                'types' => $this->types,
                'genre' => $this->genre,
                'year' => $this->year,
                'country' => $this->country,
                'sort' => $this->sort,
                'excludeEpisodes' => $this->excludeEpisodes,
            ])
            ->simplePaginate($this->perPage, pageName: $this->pageName)
            ->withQueryString();

        $isChartMode = $this->displayMode === 'chart';
        $pageOffset = $isChartMode ? (($titles->currentPage() - 1) * $this->perPage) : 0;
        $chartRows = [];

        if ($isChartMode) {
            foreach (collect($titles->items())->values() as $index => $title) {
                $chartRows[$title->id] = [
                    'comparisonLabel' => $title->displayRatingCount() > 0
                        ? number_format($title->displayRatingCount()).' votes'
                        : 'Catalog rank',
                    'movementAmount' => 0,
                    'movementDirection' => 'steady',
                    'movementNote' => 'catalog order',
                    'rank' => $pageOffset + $index + 1,
                ];
            }
        }

        return [
            'chartRows' => $chartRows,
            'chartCountryOptions' => [],
            'country' => null,
            'displayMode' => $this->displayMode,
            'emptyHeading' => $this->emptyHeading,
            'emptyText' => $this->emptyText,
            'globalPositions' => [],
            'isChartMode' => $isChartMode,
            'pageOffset' => $pageOffset,
            'perPage' => $this->perPage,
            'selectedCountryCode' => '',
            'selectedCountryLabel' => '',
            'titles' => $titles,
            'showSummary' => $this->showSummary,
        ];
    }

    public function render(): View
    {
        return view('livewire.catalog.title-browser');
    }

    public function paginationSimpleView(): string
    {
        return 'livewire.pagination.island-simple';
    }

    public function paginationIslandName(): string
    {
        return 'title-browser-page';
    }
}
