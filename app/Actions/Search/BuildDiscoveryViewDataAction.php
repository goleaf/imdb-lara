<?php

namespace App\Actions\Search;

use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\InterestCategory;
use Illuminate\Database\Eloquent\Collection;

class BuildDiscoveryViewDataAction
{
    public function __construct(
        private BuildDiscoveryQueryAction $buildDiscoveryQuery,
        private GetDiscoveryFilterOptionsAction $getDiscoveryFilterOptions,
        private GetDiscoveryTitleSuggestionsAction $getDiscoveryTitleSuggestions,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     genre?: ?string,
     *     theme?: ?string,
     *     type?: ?string,
     *     sort?: string,
     *     minimumRating?: ?string,
     *     yearFrom?: ?string,
     *     yearTo?: ?string,
     *     votesMin?: ?string,
     *     language?: ?string,
     *     country?: ?string,
     *     runtime?: ?string,
     *     awards?: ?string
     * }  $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters, int $perPage = 12, string $pageName = 'discover'): array
    {
        $filterOptions = $this->getDiscoveryFilterOptions->handle();
        $normalizedFilters = $this->normalizeFilters($filters, $filterOptions);
        $titles = $this->buildDiscoveryQuery
            ->handle([
                ...$normalizedFilters,
                'includePresentationRelations' => false,
            ])
            ->with([
                'statistic:movie_id,aggregate_rating,vote_count',
                'titleImages:id,movie_id,position,url,width,height,type',
                'primaryImageRecord:movie_id,url,width,height,type',
                'genres:id,name',
            ])
            ->simplePaginate($perPage, pageName: $pageName)
            ->withQueryString();

        $searchSuggestions = $this->getDiscoveryTitleSuggestions->handle($normalizedFilters['search']);
        $awardLabels = collect($filterOptions['awardOptions'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $countryLabels = collect($filterOptions['countries'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $languageLabels = collect($filterOptions['languages'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $runtimeLabels = collect($filterOptions['runtimeOptions'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $voteThresholdLabels = collect($filterOptions['voteThresholdOptions'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $selectedTitleType = TitleType::tryFrom((string) ($normalizedFilters['type'] ?? ''));
        $activeFilters = collect([
            ['icon' => 'magnifying-glass', 'label' => filled($normalizedFilters['search']) ? 'Keyword: '.$normalizedFilters['search'] : null],
            ['icon' => $selectedTitleType?->icon() ?? 'film', 'label' => $selectedTitleType?->label()],
            ['icon' => 'tag', 'label' => $this->matchingGenreLabel($filterOptions['genres'], $normalizedFilters['genre'])],
            ['icon' => 'squares-2x2', 'label' => $this->matchingInterestCategoryLabel($filterOptions['interestCategories'], $normalizedFilters['theme'])],
            ['icon' => 'trophy', 'label' => $normalizedFilters['awards'] ? $awardLabels->get($normalizedFilters['awards']) : null],
            ['icon' => 'calendar-days', 'label' => $normalizedFilters['yearFrom'] ? 'From '.$normalizedFilters['yearFrom'] : null],
            ['icon' => 'calendar-days', 'label' => $normalizedFilters['yearTo'] ? 'To '.$normalizedFilters['yearTo'] : null],
            ['icon' => 'star', 'label' => $normalizedFilters['minimumRating'] ? $normalizedFilters['minimumRating'].'+ rating' : null],
            ['icon' => 'users', 'label' => $normalizedFilters['votesMin'] ? $voteThresholdLabels->get($normalizedFilters['votesMin']) : null],
            ['icon' => 'clock', 'label' => $normalizedFilters['runtime'] ? $runtimeLabels->get($normalizedFilters['runtime']) : null],
            ['icon' => 'language', 'label' => $normalizedFilters['language'] ? $languageLabels->get($normalizedFilters['language']) : null],
            ['icon' => 'globe-alt', 'label' => $normalizedFilters['country'] ? $countryLabels->get($normalizedFilters['country']) : null],
        ])->filter(fn (array $filter): bool => filled($filter['label']))->values();
        $sortOptions = collect($filterOptions['sortOptions'])
            ->map(fn (array $option): array => [
                ...$option,
                'icon' => match ($option['value']) {
                    'popular' => 'fire',
                    'trending' => 'bolt',
                    'rating' => 'star',
                    'latest' => 'clock',
                    'year' => 'calendar-days',
                    default => 'bars-arrow-down',
                },
            ])
            ->all();

        return [
            'activeFilterCount' => collect([
                $normalizedFilters['search'],
                $normalizedFilters['genre'],
                $normalizedFilters['theme'],
                $normalizedFilters['type'],
                $normalizedFilters['minimumRating'],
                $normalizedFilters['yearFrom'],
                $normalizedFilters['yearTo'],
                $normalizedFilters['votesMin'],
                $normalizedFilters['language'],
                $normalizedFilters['country'],
                $normalizedFilters['runtime'],
                $normalizedFilters['awards'],
            ])->filter(fn (mixed $value): bool => filled($value))->count(),
            'activeFilters' => $activeFilters,
            'awardOptions' => $filterOptions['awardOptions'],
            'countries' => $filterOptions['countries'],
            'titles' => $titles,
            'genres' => $filterOptions['genres'],
            'interestCategories' => $filterOptions['interestCategories'],
            'keywordActive' => filled($normalizedFilters['search']),
            'languages' => $filterOptions['languages'],
            'coreFiltersActive' => filled($normalizedFilters['type']) || filled($normalizedFilters['genre']) || filled($normalizedFilters['theme']) || filled($normalizedFilters['awards']),
            'loadingTargets' => 'search,genre,theme,type,sort,minimumRating,yearFrom,yearTo,votesMin,language,country,runtime,awards',
            'titleTypes' => $filterOptions['titleTypes'],
            'minimumRatings' => $filterOptions['minimumRatings'],
            'orderingActive' => $normalizedFilters['sort'] !== 'popular',
            'originFiltersActive' => filled($normalizedFilters['language']) || filled($normalizedFilters['country']),
            'releaseFiltersActive' => filled($normalizedFilters['yearFrom']) || filled($normalizedFilters['yearTo']),
            'runtimeOptions' => $filterOptions['runtimeOptions'],
            'showSummary' => false,
            'signalFiltersActive' => filled($normalizedFilters['minimumRating']) || filled($normalizedFilters['votesMin']) || filled($normalizedFilters['runtime']),
            'sortLabel' => collect($filterOptions['sortOptions'])->firstWhere('value', $normalizedFilters['sort'])['label'] ?? 'Popularity',
            'sortOptions' => $sortOptions,
            'searchSuggestions' => $searchSuggestions,
            'titleResultsCount' => count($titles->items()),
            'voteThresholdOptions' => $filterOptions['voteThresholdOptions'],
            'years' => $filterOptions['years'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $filterOptions
     * @return array<string, string|null>
     */
    private function normalizeFilters(array $filters, array $filterOptions): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'genre' => $this->matchingGenreSlug($filterOptions['genres'], $filters['genre'] ?? null),
            'theme' => $this->matchingInterestCategorySlug($filterOptions['interestCategories'], $filters['theme'] ?? null),
            'type' => TitleType::tryFrom((string) ($filters['type'] ?? ''))?->value,
            'sort' => $this->normalizeAllowedValue($filters['sort'] ?? 'popular', $filterOptions['sortOptions'], 'popular'),
            'minimumRating' => $this->normalizeRating($filters['minimumRating'] ?? null, $filterOptions['minimumRatings']),
            'yearFrom' => $this->normalizeYear($filters['yearFrom'] ?? null, $filterOptions['years']),
            'yearTo' => $this->normalizeYear($filters['yearTo'] ?? null, $filterOptions['years']),
            'votesMin' => $this->normalizeAllowedValue($filters['votesMin'] ?? null, $filterOptions['voteThresholdOptions']),
            'language' => $this->normalizeAllowedValue($filters['language'] ?? null, $filterOptions['languages']),
            'country' => $this->normalizeAllowedValue($filters['country'] ?? null, $filterOptions['countries']),
            'runtime' => $this->normalizeAllowedValue($filters['runtime'] ?? null, $filterOptions['runtimeOptions']),
            'awards' => $this->normalizeAllowedValue($filters['awards'] ?? null, $filterOptions['awardOptions']),
        ];
    }

    /**
     * @param  array<int, array{value: string, label: string}>  $options
     */
    private function normalizeAllowedValue(mixed $value, array $options, ?string $fallback = null): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return $fallback;
        }

        $allowedValues = collect($options)->pluck('value');

        return $allowedValues->contains($candidate) ? $candidate : $fallback;
    }

    /**
     * @param  list<int>  $ratings
     */
    private function normalizeRating(mixed $value, array $ratings): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '' || ! ctype_digit($candidate)) {
            return null;
        }

        return collect($ratings)
            ->map(fn (int $rating): string => (string) $rating)
            ->contains($candidate)
                ? $candidate
                : null;
    }

    /**
     * @param  list<int>  $years
     */
    private function normalizeYear(mixed $value, array $years): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '' || ! ctype_digit($candidate)) {
            return null;
        }

        return collect($years)
            ->map(fn (int $year): string => (string) $year)
            ->contains($candidate)
                ? $candidate
                : null;
    }

    /**
     * @param  Collection<int, Genre>  $genres
     */
    private function matchingGenreSlug($genres, mixed $value): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return null;
        }

        return $genres->firstWhere('slug', $candidate)?->slug;
    }

    /**
     * @param  Collection<int, InterestCategory>  $interestCategories
     */
    private function matchingInterestCategorySlug($interestCategories, mixed $value): ?string
    {
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return null;
        }

        return $interestCategories->firstWhere('slug', $candidate)?->slug;
    }

    /**
     * @param  Collection<int, Genre>  $genres
     */
    private function matchingGenreLabel($genres, ?string $genre): ?string
    {
        return filled($genre) ? $genres->firstWhere('slug', $genre)?->name : null;
    }

    /**
     * @param  Collection<int, InterestCategory>  $interestCategories
     */
    private function matchingInterestCategoryLabel($interestCategories, ?string $theme): ?string
    {
        return filled($theme) ? $interestCategories->firstWhere('slug', $theme)?->name : null;
    }
}
