<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LoadTitleBoxOfficeAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     summaryCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     rankCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     comparisonCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     marketRows: Collection<int, array{market: string, weeksLabel: string|null, copy: string}>,
     *     reportedFigureCount: int,
     *     reportedMarketCount: int,
     *     spotlightMetric: array{key: string, label: string, value: string, copy: string}|null,
     *     secondaryMetric: array{key: string, label: string, value: string, copy: string}|null,
     *     spotlightRank: array{key: string, label: string, value: string, copy: string}|null,
     *     budgetMultiple: array{key: string, label: string, value: string, copy: string}|null,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'position',
                    'is_primary',
                ])
                ->ordered(),
        ]);

        $poster = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
        $boxOffice = $title->imdbPayloadSection('boxOffice');
        $summaryCards = $this->buildSummaryCards($boxOffice);
        $marketRows = $this->buildMarketRows($boxOffice);
        $comparisonCards = $this->buildComparisonCards($boxOffice, $marketRows->count());
        $rankCards = $this->buildRankCards($title, $boxOffice);
        $spotlightMetric = $summaryCards->firstWhere('key', 'lifetimeGross') ?? $summaryCards->first();
        $secondaryMetric = is_array($spotlightMetric)
            ? $summaryCards->first(fn (array $metric): bool => $metric['key'] !== $spotlightMetric['key'])
            : $summaryCards->first();
        $spotlightRank = is_array($spotlightMetric) ? $rankCards->firstWhere('key', $spotlightMetric['key']) : null;
        $budgetMultiple = $comparisonCards->firstWhere('key', 'budgetMultiple');
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'summaryCards' => $summaryCards,
            'rankCards' => $rankCards,
            'comparisonCards' => $comparisonCards,
            'marketRows' => $marketRows,
            'reportedFigureCount' => $summaryCards->count(),
            'reportedMarketCount' => $marketRows->count(),
            'spotlightMetric' => is_array($spotlightMetric) ? $spotlightMetric : null,
            'secondaryMetric' => is_array($secondaryMetric) ? $secondaryMetric : null,
            'spotlightRank' => is_array($spotlightRank) ? $spotlightRank : null,
            'budgetMultiple' => is_array($budgetMultiple) ? $budgetMultiple : null,
            'seo' => new PageSeoData(
                title: $title->name.' Box Office Report',
                description: 'Review opening weekend, lifetime gross, budget, ranked positions, and market coverage for '.$title->name.'.',
                canonical: route('public.titles.box-office', $title),
                openGraphType: $openGraphType,
                openGraphImage: $backdrop?->url ?? $poster?->url,
                openGraphImageAlt: $backdrop?->alt_text ?: $poster?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Box Office'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $boxOffice
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildSummaryCards(?array $boxOffice): Collection
    {
        if ($boxOffice === null) {
            return collect();
        }

        return collect([
            [
                'key' => 'openingWeekend',
                'label' => 'Opening Weekend',
                'value' => $this->formatMoney(
                    data_get($boxOffice, 'openingWeekendGross.amount'),
                    data_get($boxOffice, 'openingWeekendGross.currency'),
                ),
                'copy' => 'Tracked theatrical debut from the imported gross record.',
            ],
            [
                'key' => 'lifetimeGross',
                'label' => 'Lifetime Gross',
                'value' => $this->formatMoney(
                    data_get($boxOffice, 'worldwideGross.amount'),
                    data_get($boxOffice, 'worldwideGross.currency'),
                ),
                'copy' => 'Worldwide theatrical total carried by the current payload.',
            ],
            [
                'key' => 'domesticGross',
                'label' => 'Domestic Gross',
                'value' => $this->formatMoney(
                    data_get($boxOffice, 'domesticGross.amount'),
                    data_get($boxOffice, 'domesticGross.currency'),
                ),
                'copy' => 'Primary home-market theatrical gross when available.',
            ],
            [
                'key' => 'productionBudget',
                'label' => 'Production Budget',
                'value' => $this->formatMoney(
                    data_get($boxOffice, 'budget.amount'),
                    data_get($boxOffice, 'budget.currency'),
                ),
                'copy' => 'Budget reporting imported alongside the title dossier.',
            ],
        ])
            ->filter(fn (array $card): bool => filled($card['value']))
            ->values();
    }

    /**
     * @param  array<string, mixed>|null  $boxOffice
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildComparisonCards(?array $boxOffice, int $reportedMarketCount): Collection
    {
        if ($boxOffice === null) {
            return collect();
        }

        $budget = $this->resolveMoneyFigure(data_get($boxOffice, 'budget'));
        $openingWeekend = $this->resolveMoneyFigure(data_get($boxOffice, 'openingWeekendGross'));
        $domesticGross = $this->resolveMoneyFigure(data_get($boxOffice, 'domesticGross'));
        $lifetimeGross = $this->resolveMoneyFigure(data_get($boxOffice, 'worldwideGross'));

        return collect([
            $this->buildBudgetMultipleCard($lifetimeGross, $budget),
            $this->buildDomesticShareCard($domesticGross, $lifetimeGross),
            $this->buildInternationalGrossCard($domesticGross, $lifetimeGross),
            $this->buildOpeningShareCard($openingWeekend, $lifetimeGross),
            $reportedMarketCount > 0
                ? [
                    'key' => 'reportedMarkets',
                    'label' => 'Reported Markets',
                    'value' => number_format($reportedMarketCount),
                    'copy' => 'The current payload tracks theatrical runway coverage across these markets.',
                ]
                : null,
        ])
            ->filter(fn (mixed $card): bool => is_array($card))
            ->values();
    }

    /**
     * @param  array<string, mixed>|null  $boxOffice
     * @return Collection<int, array{market: string, weeksLabel: string|null, copy: string}>
     */
    private function buildMarketRows(?array $boxOffice): Collection
    {
        if ($boxOffice === null) {
            return collect();
        }

        return collect(data_get($boxOffice, 'theatricalRuns', []))
            ->map(function (mixed $run): ?array {
                if (! is_array($run)) {
                    return null;
                }

                $market = $this->nullableString(data_get($run, 'market'));
                $weeks = $this->nullableInt(data_get($run, 'weeks'));

                if ($market === null && $weeks === null) {
                    return null;
                }

                $resolvedMarket = $market ?? 'Market pending';
                $weeksLabel = $weeks !== null
                    ? number_format($weeks).' '.str('week')->plural($weeks)
                    : null;

                return [
                    'market' => $resolvedMarket,
                    'weeksLabel' => $weeksLabel,
                    'copy' => $weeksLabel !== null
                        ? $weeksLabel.' in release tracked for this market.'
                        : 'Market presence is recorded without a reported theatrical window.',
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>|null  $boxOffice
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildRankCards(Title $title, ?array $boxOffice): Collection
    {
        if ($boxOffice === null) {
            return collect();
        }

        /** @var Collection<string, array{label: string, path: string}> $metricDefinitions */
        $metricDefinitions = collect([
            'openingWeekend' => ['label' => 'Opening Weekend', 'path' => 'openingWeekendGross'],
            'lifetimeGross' => ['label' => 'Lifetime Gross', 'path' => 'worldwideGross'],
            'domesticGross' => ['label' => 'Domestic Gross', 'path' => 'domesticGross'],
            'productionBudget' => ['label' => 'Production Budget', 'path' => 'budget'],
        ]);

        $trackedTitles = Title::query()
            ->select(['id', 'imdb_payload'])
            ->where('title_type', '!=', TitleType::Episode)
            ->where(function (Builder $query) use ($title): void {
                $query->where('is_published', true)
                    ->orWhere('id', $title->getKey());
            })
            ->whereNotNull('imdb_payload')
            ->get();

        return $metricDefinitions
            ->map(function (array $metricDefinition, string $metricKey) use ($boxOffice, $title, $trackedTitles): ?array {
                $currentFigure = $this->resolveMoneyFigure(data_get($boxOffice, $metricDefinition['path']));

                if ($currentFigure === null) {
                    return null;
                }

                $rankedRows = $trackedTitles
                    ->map(function (Title $trackedTitle) use ($metricDefinition, $currentFigure): ?array {
                        $figure = $this->resolveMoneyFigure(
                            data_get($trackedTitle->imdbPayloadSection('boxOffice'), $metricDefinition['path']),
                        );

                        if ($figure === null || $figure['currency'] !== $currentFigure['currency']) {
                            return null;
                        }

                        return [
                            'title_id' => $trackedTitle->getKey(),
                            'amount' => $figure['amount'],
                        ];
                    })
                    ->filter()
                    ->sortByDesc('amount')
                    ->values();

                if ($rankedRows->isEmpty()) {
                    return null;
                }

                $rank = $this->resolveRankPosition($rankedRows, $title->getKey());

                if ($rank === null) {
                    return null;
                }

                $currencyCode = $currentFigure['currency'];
                $currencyLabel = $currencyCode !== null ? ' '.strtoupper($currencyCode) : '';

                return [
                    'key' => $metricKey,
                    'label' => $metricDefinition['label'],
                    'value' => '#'.$rank,
                    'copy' => 'Out of '.number_format($rankedRows->count()).' tracked'.$currencyLabel.' records for this metric.',
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array{title_id: int, amount: int}>  $rankedRows
     */
    private function resolveRankPosition(Collection $rankedRows, int $titleId): ?int
    {
        $currentRank = 0;
        $previousAmount = null;

        foreach ($rankedRows->values() as $index => $rankedRow) {
            if ($previousAmount === null || $rankedRow['amount'] < $previousAmount) {
                $currentRank = $index + 1;
            }

            if ($rankedRow['title_id'] === $titleId) {
                return $currentRank;
            }

            $previousAmount = $rankedRow['amount'];
        }

        return null;
    }

    /**
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $lifetimeGross
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $budget
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildBudgetMultipleCard(?array $lifetimeGross, ?array $budget): ?array
    {
        if (! $this->canCompareFigures($lifetimeGross, $budget) || $budget['amount'] === 0) {
            return null;
        }

        $multiple = $lifetimeGross['amount'] / $budget['amount'];

        return [
            'key' => 'budgetMultiple',
            'label' => 'Budget Multiple',
            'value' => number_format($multiple, 1).'x',
            'copy' => 'Lifetime gross relative to the reported production budget.',
        ];
    }

    /**
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $domesticGross
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $lifetimeGross
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildDomesticShareCard(?array $domesticGross, ?array $lifetimeGross): ?array
    {
        if (! $this->canCompareFigures($domesticGross, $lifetimeGross) || $lifetimeGross['amount'] === 0) {
            return null;
        }

        $share = ($domesticGross['amount'] / $lifetimeGross['amount']) * 100;

        return [
            'key' => 'domesticShare',
            'label' => 'Domestic Share',
            'value' => number_format($share, 1).'%',
            'copy' => 'Home-market share of the reported lifetime gross.',
        ];
    }

    /**
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $domesticGross
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $lifetimeGross
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildInternationalGrossCard(?array $domesticGross, ?array $lifetimeGross): ?array
    {
        if (! $this->canCompareFigures($domesticGross, $lifetimeGross)) {
            return null;
        }

        $internationalGross = max(0, $lifetimeGross['amount'] - $domesticGross['amount']);

        return [
            'key' => 'internationalGross',
            'label' => 'International Gross',
            'value' => $this->formatMoney($internationalGross, $lifetimeGross['currency']),
            'copy' => 'Estimated by subtracting domestic gross from the reported lifetime total.',
        ];
    }

    /**
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $openingWeekend
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $lifetimeGross
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildOpeningShareCard(?array $openingWeekend, ?array $lifetimeGross): ?array
    {
        if (! $this->canCompareFigures($openingWeekend, $lifetimeGross) || $lifetimeGross['amount'] === 0) {
            return null;
        }

        $share = ($openingWeekend['amount'] / $lifetimeGross['amount']) * 100;

        return [
            'key' => 'openingToLifetime',
            'label' => 'Opening / Lifetime',
            'value' => number_format($share, 1).'%',
            'copy' => 'How much of the lifetime total arrived during opening weekend.',
        ];
    }

    /**
     * @return array{amount: int, currency: string|null, formatted: string}|null
     */
    private function resolveMoneyFigure(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $amount = data_get($value, 'amount');

        if (! is_scalar($amount) || ! is_numeric((string) $amount)) {
            return null;
        }

        $normalizedAmount = max(0, (int) round((float) $amount));
        $currency = $this->nullableString(data_get($value, 'currency'));

        return [
            'amount' => $normalizedAmount,
            'currency' => $currency !== null ? strtoupper($currency) : null,
            'formatted' => $this->formatMoney($normalizedAmount, $currency),
        ];
    }

    /**
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $leftFigure
     * @param  array{amount: int, currency: string|null, formatted: string}|null  $rightFigure
     */
    private function canCompareFigures(?array $leftFigure, ?array $rightFigure): bool
    {
        if ($leftFigure === null || $rightFigure === null) {
            return false;
        }

        return $leftFigure['currency'] === $rightFigure['currency'];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (! is_scalar($value) || ! is_numeric((string) $value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function formatMoney(mixed $amount, mixed $currency): ?string
    {
        if (! is_scalar($amount) || ! is_numeric((string) $amount)) {
            return null;
        }

        $formattedAmount = number_format((float) $amount, 0, '.', ',');
        $currencyCode = $this->nullableString(is_scalar($currency) ? (string) $currency : null);

        if ($currencyCode === null) {
            return $formattedAmount;
        }

        return strtoupper($currencyCode).' '.$formattedAmount;
    }
}
