<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\MovieBoxOffice;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadTitleBoxOfficeAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: CatalogMediaAsset|null,
     *     backdrop: CatalogMediaAsset|null,
     *     summaryCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     rankCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     comparisonCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     heroContextCards: Collection<int, array{key: string, label: string, value: string, copy: string}>,
     *     reportingRows: Collection<int, array{key: string, label: string, badge: string|null, copy: string}>,
     *     reportedFigureCount: int,
     *     reportedCoverageCount: int,
     *     spotlightMetric: array{key: string, label: string, value: string, copy: string}|null,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->loadMissing(Title::catalogBoxOfficeRelations());

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $summaryCards = $this->buildSummaryCards($title->boxOfficeRecord);
        $reportingRows = $this->buildReportingRows($title->boxOfficeRecord, $summaryCards);
        $comparisonCards = $this->buildComparisonCards($title->boxOfficeRecord, $reportingRows->count());
        $rankCards = $this->buildRankCards($title->boxOfficeRecord);
        $spotlightMetric = $summaryCards->firstWhere('key', 'lifetimeGross') ?? $summaryCards->first();
        $secondaryMetric = is_array($spotlightMetric)
            ? $summaryCards->first(fn (array $metric): bool => $metric['key'] !== $spotlightMetric['key'])
            : $summaryCards->first();
        $spotlightRank = is_array($spotlightMetric) ? $rankCards->firstWhere('key', $spotlightMetric['key']) : null;
        $budgetMultiple = $comparisonCards->firstWhere('key', 'budgetMultiple');
        $heroContextCards = $this->buildHeroContextCards(
            $title->boxOfficeRecord,
            $summaryCards,
            is_array($secondaryMetric) ? $secondaryMetric : null,
            is_array($spotlightRank) ? $spotlightRank : null,
            is_array($budgetMultiple) ? $budgetMultiple : null,
        );
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
            'heroContextCards' => $heroContextCards,
            'reportingRows' => $reportingRows,
            'reportedFigureCount' => $summaryCards->count(),
            'reportedCoverageCount' => $reportingRows->count(),
            'spotlightMetric' => is_array($spotlightMetric) ? $spotlightMetric : null,
            'seo' => new PageSeoData(
                title: $title->name.' Box Office Report',
                description: 'Review opening weekend, lifetime gross, budget, ranked positions, and reporting footprint for '.$title->name.'.',
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
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildSummaryCards(?MovieBoxOffice $boxOffice): Collection
    {
        if (! $boxOffice instanceof MovieBoxOffice) {
            return collect();
        }

        return collect([
            [
                'key' => 'openingWeekend',
                'label' => 'Opening Weekend',
                'value' => $this->formatMoney(
                    $boxOffice->opening_weekend_gross_amount,
                    $boxOffice->opening_weekend_gross_currency_code,
                ),
                'copy' => 'Tracked theatrical debut from the imported gross record.',
            ],
            [
                'key' => 'lifetimeGross',
                'label' => 'Lifetime Gross',
                'value' => $this->formatMoney(
                    $boxOffice->worldwide_gross_amount,
                    $boxOffice->worldwide_gross_currency_code,
                ),
                'copy' => 'Worldwide theatrical total carried by the current import.',
            ],
            [
                'key' => 'domesticGross',
                'label' => 'Domestic Gross',
                'value' => $this->formatMoney(
                    $boxOffice->domestic_gross_amount,
                    $boxOffice->domestic_gross_currency_code,
                ),
                'copy' => 'Primary home-market theatrical gross when available.',
            ],
            [
                'key' => 'productionBudget',
                'label' => 'Production Budget',
                'value' => $this->formatMoney(
                    $boxOffice->production_budget_amount,
                    $boxOffice->production_budget_currency_code,
                ),
                'copy' => 'Budget reporting imported alongside the title dossier.',
            ],
        ])
            ->filter(fn (array $card): bool => filled($card['value']))
            ->values();
    }

    /**
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildComparisonCards(?MovieBoxOffice $boxOffice, int $reportedCoverageCount): Collection
    {
        if (! $boxOffice instanceof MovieBoxOffice) {
            return collect();
        }

        $budget = $this->resolveStoredMoneyFigure(
            $boxOffice->production_budget_amount,
            $boxOffice->production_budget_currency_code,
        );
        $openingWeekend = $this->resolveStoredMoneyFigure(
            $boxOffice->opening_weekend_gross_amount,
            $boxOffice->opening_weekend_gross_currency_code,
        );
        $domesticGross = $this->resolveStoredMoneyFigure(
            $boxOffice->domestic_gross_amount,
            $boxOffice->domestic_gross_currency_code,
        );
        $lifetimeGross = $this->resolveStoredMoneyFigure(
            $boxOffice->worldwide_gross_amount,
            $boxOffice->worldwide_gross_currency_code,
        );

        return collect([
            $this->buildBudgetMultipleCard($lifetimeGross, $budget),
            $this->buildDomesticShareCard($domesticGross, $lifetimeGross),
            $this->buildInternationalGrossCard($domesticGross, $lifetimeGross),
            $this->buildOpeningShareCard($openingWeekend, $lifetimeGross),
            $reportedCoverageCount > 0
                ? [
                    'key' => 'reportedFields',
                    'label' => 'Imported Fields',
                    'value' => number_format($reportedCoverageCount),
                    'copy' => 'Commercial details currently attached to the imported box office record.',
                ]
                : null,
        ])
            ->filter(fn (mixed $card): bool => is_array($card))
            ->values();
    }

    /**
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildRankCards(?MovieBoxOffice $boxOffice): Collection
    {
        if (! $boxOffice instanceof MovieBoxOffice) {
            return collect();
        }

        /** @var Collection<string, array{label: string, amountColumn: string, currencyColumn: string}> $metricDefinitions */
        $metricDefinitions = collect([
            'openingWeekend' => [
                'label' => 'Opening Weekend',
                'amountColumn' => 'opening_weekend_gross_amount',
                'currencyColumn' => 'opening_weekend_gross_currency_code',
            ],
            'lifetimeGross' => [
                'label' => 'Lifetime Gross',
                'amountColumn' => 'worldwide_gross_amount',
                'currencyColumn' => 'worldwide_gross_currency_code',
            ],
            'domesticGross' => [
                'label' => 'Domestic Gross',
                'amountColumn' => 'domestic_gross_amount',
                'currencyColumn' => 'domestic_gross_currency_code',
            ],
            'productionBudget' => [
                'label' => 'Production Budget',
                'amountColumn' => 'production_budget_amount',
                'currencyColumn' => 'production_budget_currency_code',
            ],
        ]);

        return $metricDefinitions
            ->map(function (array $metricDefinition, string $metricKey) use ($boxOffice): ?array {
                $currentFigure = $this->resolveStoredMoneyFigure(
                    $boxOffice->getAttribute($metricDefinition['amountColumn']),
                    $boxOffice->getAttribute($metricDefinition['currencyColumn']),
                );

                if ($currentFigure === null) {
                    return null;
                }

                $trackedQuery = MovieBoxOffice::query()
                    ->whereNotNull($metricDefinition['amountColumn'])
                    ->where($metricDefinition['currencyColumn'], $currentFigure['currency']);
                $trackedCount = (clone $trackedQuery)->count();

                if ($trackedCount === 0) {
                    return null;
                }

                $higherCount = (clone $trackedQuery)
                    ->where($metricDefinition['amountColumn'], '>', $currentFigure['amount'])
                    ->count();
                $currencyLabel = $currentFigure['currency'] !== null ? ' '.$currentFigure['currency'] : '';

                return [
                    'key' => $metricKey,
                    'label' => $metricDefinition['label'],
                    'value' => '#'.number_format($higherCount + 1),
                    'copy' => 'Out of '.number_format($trackedCount).' tracked'.$currencyLabel.' records for this metric.',
                ];
            })
            ->filter()
            ->values();
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
     * @param  Collection<int, array{key: string, label: string, value: string, copy: string}>  $summaryCards
     * @return Collection<int, array{key: string, label: string, badge: string|null, copy: string}>
     */
    private function buildReportingRows(?MovieBoxOffice $boxOffice, Collection $summaryCards): Collection
    {
        if (! $boxOffice instanceof MovieBoxOffice) {
            return collect();
        }

        $reportedCurrenciesLabel = $this->formatReportedCurrenciesLabel($boxOffice);
        $openingWeekendCloseDate = $this->formatOpeningWeekendCloseDate($boxOffice);

        return collect([
            filled($boxOffice->worldwide_gross_amount)
                ? [
                    'key' => 'lifetimeGross',
                    'label' => 'Lifetime gross reporting',
                    'badge' => $this->formatCurrencyBadge($boxOffice->worldwide_gross_currency_code),
                    'copy' => 'A worldwide theatrical gross is currently attached to the imported box office record.',
                ]
                : null,
            filled($boxOffice->domestic_gross_amount)
                ? [
                    'key' => 'domesticGross',
                    'label' => 'Domestic gross reporting',
                    'badge' => $this->formatCurrencyBadge($boxOffice->domestic_gross_currency_code),
                    'copy' => 'A domestic theatrical gross is currently attached to this title.',
                ]
                : null,
            filled($boxOffice->opening_weekend_gross_amount)
                ? [
                    'key' => 'openingWeekend',
                    'label' => 'Opening weekend reporting',
                    'badge' => $openingWeekendCloseDate ?? $this->formatCurrencyBadge($boxOffice->opening_weekend_gross_currency_code),
                    'copy' => $openingWeekendCloseDate !== null
                        ? 'An opening weekend gross is present, along with the recorded weekend close date.'
                        : 'An opening weekend gross is currently attached to the imported box office record.',
                ]
                : null,
            filled($boxOffice->production_budget_amount)
                ? [
                    'key' => 'productionBudget',
                    'label' => 'Production budget reporting',
                    'badge' => $this->formatCurrencyBadge($boxOffice->production_budget_currency_code),
                    'copy' => 'A production budget is available for revenue comparisons and multiples.',
                ]
                : null,
            $reportedCurrenciesLabel !== null
                ? [
                    'key' => 'reportedCurrencies',
                    'label' => 'Reported currencies',
                    'badge' => $reportedCurrenciesLabel,
                    'copy' => 'These are the currency codes currently attached to the imported commercial figures.',
                ]
                : null,
            $summaryCards->isNotEmpty()
                ? [
                    'key' => 'figureCoverage',
                    'label' => 'Figure coverage',
                    'badge' => $summaryCards->count().' / 4',
                    'copy' => 'This shows how many headline commercial figures are already filled on the imported record.',
                ]
                : null,
        ])
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values();
    }

    /**
     * @param  Collection<int, array{key: string, label: string, value: string, copy: string}>  $summaryCards
     * @param  array{key: string, label: string, value: string, copy: string}|null  $secondaryMetric
     * @param  array{key: string, label: string, value: string, copy: string}|null  $spotlightRank
     * @param  array{key: string, label: string, value: string, copy: string}|null  $budgetMultiple
     * @return Collection<int, array{key: string, label: string, value: string, copy: string}>
     */
    private function buildHeroContextCards(
        ?MovieBoxOffice $boxOffice,
        Collection $summaryCards,
        ?array $secondaryMetric,
        ?array $spotlightRank,
        ?array $budgetMultiple,
    ): Collection {
        return collect([
            $this->formatRankCardForHero($spotlightRank),
            $budgetMultiple,
            $this->buildOpeningWeekendCloseDateCard($boxOffice),
            $this->buildFigureCoverageCard($summaryCards),
            $this->buildReportedCurrenciesCard($boxOffice),
            $secondaryMetric,
        ])
            ->filter(fn (mixed $card): bool => is_array($card))
            ->unique('key')
            ->take(2)
            ->values();
    }

    /**
     * @param  array{key: string, label: string, value: string, copy: string}|null  $spotlightRank
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function formatRankCardForHero(?array $spotlightRank): ?array
    {
        if ($spotlightRank === null) {
            return null;
        }

        return [
            'key' => $spotlightRank['key'].'Rank',
            'label' => $spotlightRank['label'].' rank',
            'value' => $spotlightRank['value'],
            'copy' => $spotlightRank['copy'],
        ];
    }

    /**
     * @param  Collection<int, array{key: string, label: string, value: string, copy: string}>  $summaryCards
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildFigureCoverageCard(Collection $summaryCards): ?array
    {
        if ($summaryCards->isEmpty()) {
            return null;
        }

        return [
            'key' => 'figureCoverage',
            'label' => 'Figure coverage',
            'value' => $summaryCards->count().' / 4',
            'copy' => 'Headline commercial figures currently filled on the imported box office record.',
        ];
    }

    /**
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildOpeningWeekendCloseDateCard(?MovieBoxOffice $boxOffice): ?array
    {
        $openingWeekendCloseDate = $this->formatOpeningWeekendCloseDate($boxOffice);

        if ($openingWeekendCloseDate === null) {
            return null;
        }

        return [
            'key' => 'openingWeekendCloseDate',
            'label' => 'Weekend closed',
            'value' => $openingWeekendCloseDate,
            'copy' => 'Calendar close date stored alongside the opening-weekend gross figure.',
        ];
    }

    /**
     * @return array{key: string, label: string, value: string, copy: string}|null
     */
    private function buildReportedCurrenciesCard(?MovieBoxOffice $boxOffice): ?array
    {
        $reportedCurrenciesLabel = $this->formatReportedCurrenciesLabel($boxOffice);

        if ($reportedCurrenciesLabel === null) {
            return null;
        }

        return [
            'key' => 'reportedCurrencies',
            'label' => 'Reported currencies',
            'value' => $reportedCurrenciesLabel,
            'copy' => 'Currency codes currently attached to the imported commercial figures.',
        ];
    }

    private function formatOpeningWeekendCloseDate(?MovieBoxOffice $boxOffice): ?string
    {
        if (! $boxOffice instanceof MovieBoxOffice) {
            return null;
        }

        if (
            ! is_int($boxOffice->opening_weekend_end_year)
            || ! is_int($boxOffice->opening_weekend_end_month)
            || ! is_int($boxOffice->opening_weekend_end_day)
        ) {
            return null;
        }

        if (! checkdate(
            $boxOffice->opening_weekend_end_month,
            $boxOffice->opening_weekend_end_day,
            $boxOffice->opening_weekend_end_year,
        )) {
            return null;
        }

        return sprintf(
            '%s %d, %d',
            now()->setDate(
                $boxOffice->opening_weekend_end_year,
                $boxOffice->opening_weekend_end_month,
                $boxOffice->opening_weekend_end_day,
            )->format('M'),
            $boxOffice->opening_weekend_end_day,
            $boxOffice->opening_weekend_end_year,
        );
    }

    private function formatReportedCurrenciesLabel(?MovieBoxOffice $boxOffice): ?string
    {
        if (! $boxOffice instanceof MovieBoxOffice) {
            return null;
        }

        $currencyCodes = array_values(array_unique(array_filter([
            $this->formatCurrencyBadge($boxOffice->domestic_gross_currency_code),
            $this->formatCurrencyBadge($boxOffice->worldwide_gross_currency_code),
            $this->formatCurrencyBadge($boxOffice->opening_weekend_gross_currency_code),
            $this->formatCurrencyBadge($boxOffice->production_budget_currency_code),
        ])));

        if ($currencyCodes === []) {
            return null;
        }

        return implode(' / ', $currencyCodes);
    }

    private function formatCurrencyBadge(mixed $currency): ?string
    {
        $currencyCode = $this->nullableString(is_scalar($currency) ? (string) $currency : null);

        return $currencyCode !== null ? strtoupper($currencyCode) : null;
    }

    /**
     * @return array{amount: int, currency: string|null, formatted: string}|null
     */
    private function resolveStoredMoneyFigure(mixed $amount, mixed $currency): ?array
    {
        if (! is_scalar($amount) || ! is_numeric((string) $amount)) {
            return null;
        }

        $normalizedAmount = max(0, (int) round((float) $amount));
        $currencyCode = $this->nullableString(is_scalar($currency) ? (string) $currency : null);

        return [
            'amount' => $normalizedAmount,
            'currency' => $currencyCode !== null ? strtoupper($currencyCode) : null,
            'formatted' => $this->formatMoney($normalizedAmount, $currencyCode),
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
