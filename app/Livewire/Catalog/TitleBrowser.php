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
                $chartRows[$title->id] = $this->chartRow(
                    $title,
                    $pageOffset + $index + 1,
                );
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

    /**
     * @return array{
     *     comparisonLabel: string,
     *     comparisonToken: string|null,
     *     genres: Collection<int, mixed>,
     *     movementAmount: int,
     *     movementDirection: string,
     *     movementIcon: string,
     *     movementLabel: string,
     *     movementNote: string|null,
     *     originCountryCode: string|null,
     *     originCountryLabel: string|null,
     *     originalTitle: string|null,
     *     poster: mixed,
     *     rank: int,
     *     releaseYear: int|null,
     *     runtimeLabel: string|null,
     *     summaryText: string|null,
     *     titleUrl: string,
     *     voteLabel: string|null
     * }
     */
    private function chartRow(Title $title, int $rank): array
    {
        $comparisonLabel = $title->displayRatingCount() > 0
            ? number_format($title->displayRatingCount()).' votes'
            : 'Catalog rank';
        $movementDirection = 'steady';
        $movementAmount = 0;
        $voteLabel = $title->displayRatingCount() > 0 ? number_format($title->displayRatingCount()).' votes' : null;

        return [
            'comparisonLabel' => $comparisonLabel,
            'comparisonToken' => filled($comparisonLabel) && $comparisonLabel !== $voteLabel
                ? $comparisonLabel
                : null,
            'genres' => $title->resolvedGenres(),
            'movementAmount' => $movementAmount,
            'movementDirection' => $movementDirection,
            'movementIcon' => $this->chartMovementIcon($movementDirection),
            'movementLabel' => $this->chartMovementLabel($movementDirection, $movementAmount),
            'movementNote' => null,
            'originCountryCode' => $title->originCountryCode(),
            'originCountryLabel' => $title->originCountryLabel(),
            'originalTitle' => filled($title->original_name) && $title->original_name !== $title->name
                ? $title->original_name
                : null,
            'poster' => $title->preferredPoster(),
            'rank' => $rank,
            'releaseYear' => $title->release_year,
            'runtimeLabel' => $title->runtimeMinutesLabel(),
            'summaryText' => $title->summaryText(),
            'titleUrl' => route('public.titles.show', $title),
            'voteLabel' => $voteLabel,
        ];
    }

    private function chartMovementIcon(string $movementDirection): string
    {
        return match ($movementDirection) {
            'up' => 'arrow-trending-up',
            'down' => 'arrow-trending-down',
            default => 'minus',
        };
    }

    private function chartMovementLabel(string $movementDirection, int $movementAmount): string
    {
        return match ($movementDirection) {
            'up' => 'Up '.$movementAmount,
            'down' => 'Down '.$movementAmount,
            default => 'Steady',
        };
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
