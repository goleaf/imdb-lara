<?php

namespace App\Actions\Catalog;

use App\Enums\TitleType;
use App\Models\MovieRating;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicTitleIndexQueryAction
{
    /**
     * @param  array{
     *     search?: string,
     *     searchMode?: string|null,
     *     genre?: string|null,
     *     theme?: string|null,
     *     minimumRating?: int|float|string|null,
     *     ratingMin?: int|float|string|null,
     *     ratingMax?: int|float|string|null,
     *     votesMin?: int|string|null,
     *     type?: string|null,
     *     types?: list<string>,
     *     sort?: string|null,
     *     year?: int|string|null,
     *     yearFrom?: int|string|null,
     *     yearTo?: int|string|null,
     *     language?: string|null,
     *     country?: string|null,
     *     runtime?: string|null,
     *     awards?: string|null,
     *     status?: string|null,
     *     excludeEpisodes?: bool,
     *     includePresentationRelations?: bool,
     *     includePublishedReviewCount?: bool
     * }  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $searchMode = filled($filters['searchMode'] ?? null) ? (string) $filters['searchMode'] : null;
        $genre = filled($filters['genre'] ?? null) ? (string) $filters['genre'] : null;
        $theme = filled($filters['theme'] ?? null) ? (string) $filters['theme'] : null;
        $minimumRating = filled($filters['minimumRating'] ?? $filters['ratingMin'] ?? null)
            ? (float) ($filters['minimumRating'] ?? $filters['ratingMin'])
            : null;
        $maximumRating = filled($filters['ratingMax'] ?? null)
            ? (float) $filters['ratingMax']
            : null;
        $votesMin = filled($filters['votesMin'] ?? null)
            ? (int) $filters['votesMin']
            : null;
        $type = filled($filters['type'] ?? null) ? (string) $filters['type'] : null;
        $types = collect($filters['types'] ?? [])
            ->map(fn (mixed $value): ?TitleType => TitleType::tryFrom((string) $value))
            ->filter()
            ->map(fn (TitleType $titleType): string => $titleType->value)
            ->unique()
            ->values()
            ->all();
        $sort = (string) ($filters['sort'] ?? 'popular');
        $year = filled($filters['year'] ?? null) ? (int) $filters['year'] : null;
        $yearFrom = filled($filters['yearFrom'] ?? null) ? (int) $filters['yearFrom'] : null;
        $yearTo = filled($filters['yearTo'] ?? null) ? (int) $filters['yearTo'] : null;
        $language = filled($filters['language'] ?? null) ? strtoupper((string) $filters['language']) : null;
        $country = filled($filters['country'] ?? null) ? strtoupper((string) $filters['country']) : null;
        $runtime = filled($filters['runtime'] ?? null) ? (string) $filters['runtime'] : null;
        $awards = filled($filters['awards'] ?? null) ? (string) $filters['awards'] : null;
        $status = filled($filters['status'] ?? null) ? (string) $filters['status'] : null;
        $excludeEpisodes = (bool) ($filters['excludeEpisodes'] ?? true);
        $includePresentationRelations = (bool) ($filters['includePresentationRelations'] ?? true);
        $catalogOnly = Title::usesCatalogOnlySchema();

        $query = Title::query()
            ->selectCatalogCardColumns()
            ->addSelect([
                'popularity_rank' => $catalogOnly
                    ? MovieRating::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1)
                    : TitleStatistic::query()
                        ->select('watchlist_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                'rating_count_sort' => $catalogOnly
                    ? MovieRating::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1)
                    : TitleStatistic::query()
                        ->select('rating_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
            ])
            ->published();

        if ($includePresentationRelations) {
            $query->withCatalogListRelations();
        }

        if ($excludeEpisodes) {
            $query->withoutEpisodes();
        }

        if ($searchMode === 'discovery') {
            $query->matchingDiscoverySearch($search);
        } else {
            $query->matchingSearch($search);
        }

        if ($genreId = $this->resolveGenreId($genre)) {
            $query->inGenre($genreId);
        }

        if ($interestCategoryId = $this->resolveInterestCategoryId($theme)) {
            $query->forInterestCategory($interestCategoryId);
        }

        if ($minimumRating !== null || $maximumRating !== null || $votesMin !== null) {
            $query->whereHas('statistic', function (Builder $statisticQuery) use ($maximumRating, $minimumRating, $votesMin): void {
                $averageRatingColumn = Title::usesCatalogOnlySchema() ? 'aggregate_rating' : 'average_rating';
                $ratingCountColumn = Title::usesCatalogOnlySchema() ? 'vote_count' : 'rating_count';

                if ($minimumRating !== null) {
                    $statisticQuery->where($averageRatingColumn, '>=', $minimumRating);
                }

                if ($maximumRating !== null) {
                    $statisticQuery->where($averageRatingColumn, '<=', $maximumRating);
                }

                if ($votesMin !== null) {
                    $statisticQuery->where($ratingCountColumn, '>=', $votesMin);
                }
            });
        }

        if ($typeEnum = TitleType::tryFrom((string) $type)) {
            $query->forType($typeEnum);
        }

        if ($types !== []) {
            if ($catalogOnly) {
                $remoteTypes = collect($types)
                    ->map(fn (string $value): ?TitleType => TitleType::tryFrom($value))
                    ->filter()
                    ->flatMap(fn (TitleType $titleType): array => Title::remoteTypesForCatalogType($titleType))
                    ->unique()
                    ->values()
                    ->all();

                if ($remoteTypes !== []) {
                    $query->whereIn('movies.titletype', $remoteTypes);
                }
            } else {
                $query->whereIn('titles.title_type', $types);
            }
        }

        if ($year !== null) {
            $query->where(Title::catalogColumn('release_year'), $year);
        }

        $query->releasedBetweenYears($yearFrom, $yearTo);

        if ($language !== null) {
            $query->spokenInLanguage($language);
        }

        if ($country !== null) {
            $query->producedInCountry($country);
        }

        if ($runtime !== null) {
            $query->withinRuntimeBucket($runtime);
        }

        if ($awards !== null) {
            $query->whereHas('awardNominations', function (Builder $awardQuery) use ($awards): void {
                if ($awards === 'winners') {
                    $awardQuery->where('is_winner', true);
                }
            });
        }

        if ($status !== null) {
            $this->applyTelevisionStatusFilter($query, $status);
        }

        return match ($sort) {
            'name' => $query->orderBy(Title::catalogColumn('sort_title'))->orderBy(Title::catalogColumn('name')),
            'latest' => $query->orderByDesc(Title::catalogColumn('release_year'))->orderByDesc(Title::catalogColumn('id')),
            'year' => $query->orderByDesc(Title::catalogColumn('release_year'))->orderBy(Title::catalogColumn('sort_title'))->orderBy(Title::catalogColumn('name')),
            'rating' => $query->orderByTopRated(max(1, $votesMin ?? 1)),
            'trending' => $query->orderByTrending(),
            default => $query
                ->orderByDesc('popularity_rank')
                ->orderByDesc('rating_count_sort')
                ->orderByDesc(Title::catalogColumn('release_year'))
                ->orderBy(Title::catalogColumn('sort_title'))
                ->orderBy(Title::catalogColumn('name')),
        };
    }

    private function applyTelevisionStatusFilter(Builder $query, string $status): void
    {
        $currentYear = now()->year;
        $catalogOnly = Title::usesCatalogOnlySchema();
        $titleTypeColumn = $catalogOnly ? 'movies.titletype' : 'titles.title_type';
        $endYearColumn = Title::catalogColumn('end_year');
        $releaseYearColumn = Title::catalogColumn('release_year');

        $seriesTypes = $catalogOnly
            ? array_merge(
                Title::remoteTypesForCatalogType(TitleType::Series),
                Title::remoteTypesForCatalogType(TitleType::MiniSeries),
            )
            : [TitleType::Series->value, TitleType::MiniSeries->value];
        $limitedTypes = $catalogOnly
            ? Title::remoteTypesForCatalogType(TitleType::MiniSeries)
            : [TitleType::MiniSeries->value];

        if ($status === 'limited') {
            $query->whereIn($titleTypeColumn, $limitedTypes);

            return;
        }

        $query->whereIn($titleTypeColumn, $seriesTypes);

        match ($status) {
            'returning' => $query->where(function (Builder $seriesQuery) use ($currentYear): void {
                $seriesQuery
                    ->whereNull(Title::catalogColumn('end_year'))
                    ->orWhere(Title::catalogColumn('end_year'), '>=', $currentYear);
            }),
            'ended' => $query->whereNotNull($endYearColumn)->where($endYearColumn, '<', $currentYear),
            'upcoming' => $query->where($releaseYearColumn, '>', $currentYear),
            default => null,
        };
    }

    private function resolveGenreId(?string $genre): ?int
    {
        if (! filled($genre)) {
            return null;
        }

        if (preg_match('/-g(?P<id>\d+)$/', $genre, $matches) === 1) {
            return (int) $matches['id'];
        }

        return ctype_digit($genre) ? (int) $genre : null;
    }

    private function resolveInterestCategoryId(?string $theme): ?int
    {
        if (! filled($theme)) {
            return null;
        }

        if (preg_match('/-ic(?P<id>\d+)$/', $theme, $matches) === 1) {
            return (int) $matches['id'];
        }

        return ctype_digit($theme) ? (int) $theme : null;
    }
}
