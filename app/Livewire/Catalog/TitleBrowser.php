<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\CatalogBackendUnavailable;
use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use App\Models\Country;
use App\Models\Title;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TitleBrowser extends Component
{
    use WithPagination;

    protected LoadPublicTitleBrowserPageAction $loadPublicTitleBrowserPage;

    /**
     * @var list<string>
     */
    public array $types = [];

    public ?string $genre = null;

    public ?string $theme = null;

    public ?int $year = null;

    public ?string $country = null;

    public string $sort = 'popular';

    public string $pageName = 'titles';

    public int $perPage = 12;

    public bool $showAll = false;

    public bool $excludeEpisodes = true;

    public bool $showSummary = true;

    public string $displayMode = 'catalog';

    public string $emptyHeading = 'No titles match this collection yet.';

    public string $emptyText = 'Check back soon or explore another part of the catalog.';

    public function boot(LoadPublicTitleBrowserPageAction $loadPublicTitleBrowserPage): void
    {
        $this->loadPublicTitleBrowserPage = $loadPublicTitleBrowserPage;
    }

    #[Computed]
    public function viewData(): array
    {
        $filters = [
            'types' => $this->types,
            'genre' => $this->genre,
            'theme' => $this->theme,
            'year' => $this->year,
            'country' => $this->country,
            'sort' => $this->sort,
            'excludeEpisodes' => $this->excludeEpisodes,
        ];

        $pageData = $this->showAll
            ? $this->loadPublicTitleBrowserPage->handleCollectionSafely($filters)
            : $this->loadPublicTitleBrowserPage->handleSafely(
                filters: $filters,
                perPage: $this->perPage,
                pageName: $this->pageName,
            );

        /** @var Paginator|Collection<int, Title> $titles */
        $titles = $pageData['titles'];

        $isChartMode = $this->displayMode === 'chart';
        $pageOffset = $isChartMode && ! $this->showAll ? (($titles->currentPage() - 1) * $this->perPage) : 0;
        $chartRows = [];
        $titleItems = $titles instanceof Collection ? $titles->values() : collect($titles->items())->values();
        $selectedCountryCode = $this->country !== null ? strtoupper($this->country) : '';
        $selectedCountryLabel = $selectedCountryCode !== '' ? (Country::labelForCode($selectedCountryCode) ?? $selectedCountryCode) : '';

        if ($isChartMode) {
            foreach ($titleItems as $index => $title) {
                $chartRows[$title->id] = [
                    'comparisonLabel' => $title->displayRatingCount() > 0
                        ? number_format($title->displayRatingCount()).' votes'
                        : 'Catalog rank',
                    'movementAmount' => 0,
                    'movementDirection' => 'steady',
                    'movementNote' => null,
                    'rank' => $pageOffset + $index + 1,
                ];
            }
        }

        return [
            'chartRows' => $chartRows,
            'chartCountryOptions' => [],
            'country' => $this->country,
            'displayMode' => $this->displayMode,
            'emptyHeading' => $this->emptyHeading,
            'emptyText' => $this->emptyText,
            'globalPositions' => [],
            'hasPagination' => ! $this->showAll,
            'isCatalogUnavailable' => $pageData['isUnavailable'],
            'isChartMode' => $isChartMode,
            'isUsingStaleCache' => $pageData['usingStaleCache'],
            'pageOffset' => $pageOffset,
            'perPage' => $this->perPage,
            'selectedCountryCode' => $selectedCountryCode,
            'selectedCountryLabel' => $selectedCountryLabel,
            'statusHeading' => 'Catalog temporarily unavailable.',
            'statusText' => CatalogBackendUnavailable::userMessage($pageData['usingStaleCache']),
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
