<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
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

    public function render()
    {
        $queryAction = app(BuildPublicTitleIndexQueryAction::class);

        $baseFilters = [
            'types' => $this->types,
            'genre' => $this->genre,
            'year' => $this->year,
            'sort' => $this->sort,
            'excludeEpisodes' => $this->excludeEpisodes,
        ];

        $titles = $queryAction
            ->handle([
                ...$baseFilters,
                'country' => $this->country,
            ])
            ->simplePaginate($this->perPage, pageName: $this->pageName)
            ->withQueryString();

        $chartCountryOptions = [];
        $globalPositions = [];

        if ($this->displayMode === 'chart') {
            $globalChartTitles = $queryAction
                ->handle([
                    ...$baseFilters,
                    'includePresentationRelations' => false,
                    'includePublishedReviewCount' => false,
                ])
                ->get(['id', 'origin_country']);

            $chartCountryOptions = $globalChartTitles
                ->pluck('origin_country')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(5)
                ->map(
                    fn (int $count, string $country): array => [
                        'code' => $country,
                        'count' => $count,
                        'label' => $this->countryLabel($country),
                    ],
                )
                ->values()
                ->all();

            if (filled($this->country)) {
                $globalPositions = $globalChartTitles
                    ->pluck('id')
                    ->values()
                    ->flip()
                    ->map(fn (int $position): int => $position + 1)
                    ->all();
            }
        }

        $isChartMode = $this->displayMode === 'chart';
        $selectedCountryCode = str((string) $this->country)->trim()->upper()->toString();
        $selectedCountryLabel = collect($chartCountryOptions)->firstWhere('code', $selectedCountryCode)['label'] ?? $selectedCountryCode;
        $pageOffset = $isChartMode ? (($titles->currentPage() - 1) * $this->perPage) : 0;
        $chartRows = [];

        if ($isChartMode) {
            $chartTitles = collect($titles->items());
            $popularityPositions = $chartTitles
                ->sortBy(fn ($title) => $title->popularity_rank ?? PHP_INT_MAX)
                ->values()
                ->pluck('id')
                ->flip();

            foreach ($chartTitles as $index => $title) {
                $chartRank = $pageOffset + $index + 1;
                $popularityPosition = (($popularityPositions[$title->id] ?? $index) + 1);
                $globalRank = $globalPositions[$title->id] ?? null;
                $comparisonRank = $selectedCountryCode !== '' ? $globalRank : $popularityPosition;
                $movementAmount = abs(($comparisonRank ?? $index + 1) - ($index + 1));
                $movementDirection = ($index + 1) < ($comparisonRank ?? $index + 1)
                    ? 'up'
                    : (($index + 1) > ($comparisonRank ?? $index + 1) ? 'down' : 'steady');

                $chartRows[$title->id] = [
                    'comparisonLabel' => $selectedCountryCode !== '' && $globalRank
                        ? 'Global #'.$globalRank
                        : 'Popularity #'.$popularityPosition,
                    'movementAmount' => $movementAmount,
                    'movementDirection' => $movementDirection,
                    'movementNote' => $selectedCountryCode !== '' ? 'vs global' : 'vs popularity',
                    'rank' => $chartRank,
                ];
            }
        }

        return view('livewire.catalog.title-browser', [
            'chartRows' => $chartRows,
            'chartCountryOptions' => $chartCountryOptions,
            'country' => $this->country,
            'displayMode' => $this->displayMode,
            'globalPositions' => $globalPositions,
            'isChartMode' => $isChartMode,
            'pageOffset' => $pageOffset,
            'perPage' => $this->perPage,
            'selectedCountryCode' => $selectedCountryCode,
            'selectedCountryLabel' => $selectedCountryLabel,
            'titles' => $titles,
            'showSummary' => $this->showSummary,
        ]);
    }

    private function countryLabel(string $country): string
    {
        if (class_exists(\Locale::class)) {
            $label = \Locale::getDisplayRegion('-'.$country, app()->getLocale());

            if (filled($label)) {
                return $label;
            }
        }

        return strtoupper($country);
    }
}
