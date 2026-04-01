<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicTitleIndexQueryAction
{
    /**
     * @param  array{
     *     search?: string,
     *     genre?: string|null,
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
     *     status?: string|null,
     *     excludeEpisodes?: bool
     * }  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $genre = filled($filters['genre'] ?? null) ? (string) ($filters['genre'] ?? null) : null;
        $minimumRating = filled($filters['minimumRating'] ?? $filters['ratingMin'] ?? null)
            ? (float) ($filters['minimumRating'] ?? $filters['ratingMin'] ?? null)
            : null;
        $maximumRating = filled($filters['ratingMax'] ?? null)
            ? (float) ($filters['ratingMax'] ?? null)
            : null;
        $votesMin = filled($filters['votesMin'] ?? null)
            ? (int) ($filters['votesMin'] ?? null)
            : null;
        $type = filled($filters['type'] ?? null) ? (string) ($filters['type'] ?? null) : null;
        $types = array_values(array_filter(
            array_map(
                static fn (mixed $titleType): string => (string) $titleType,
                $filters['types'] ?? [],
            ),
            static fn (string $titleType): bool => $titleType !== '',
        ));
        $sort = $filters['sort'] ?? 'popular';
        $year = filled($filters['year'] ?? null) ? (int) ($filters['year'] ?? null) : null;
        $yearFrom = filled($filters['yearFrom'] ?? null) ? (int) ($filters['yearFrom'] ?? null) : null;
        $yearTo = filled($filters['yearTo'] ?? null) ? (int) ($filters['yearTo'] ?? null) : null;
        $language = filled($filters['language'] ?? null) ? (string) ($filters['language'] ?? null) : null;
        $country = filled($filters['country'] ?? null) ? (string) ($filters['country'] ?? null) : null;
        $runtime = filled($filters['runtime'] ?? null) ? (string) ($filters['runtime'] ?? null) : null;
        $status = filled($filters['status'] ?? null) ? (string) ($filters['status'] ?? null) : null;
        $excludeEpisodes = (bool) ($filters['excludeEpisodes'] ?? true);

        $query = Title::query()
            ->select([
                'id',
                'name',
                'original_name',
                'slug',
                'title_type',
                'release_year',
                'release_date',
                'runtime_minutes',
                'plot_outline',
                'origin_country',
                'original_language',
                'popularity_rank',
                'is_published',
            ])
            ->published()
            ->with([
                'genres:id,name,slug',
                'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                'mediaAssets' => fn ($mediaQuery) => $mediaQuery
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
                    ->where('kind', MediaKind::Poster)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->withCount([
                'reviews as published_reviews_count' => fn (Builder $reviewQuery) => $reviewQuery
                    ->where('status', ReviewStatus::Published),
            ]);

        if ($excludeEpisodes) {
            $query->withoutEpisodes();
        }

        $query->matchingSearch($search);

        if ($genre !== null) {
            $query->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->where('slug', $genre));
        }

        if ($minimumRating !== null || $maximumRating !== null || $votesMin !== null) {
            $query->whereHas('statistic', function (Builder $statisticQuery) use ($maximumRating, $minimumRating, $votesMin): void {
                if ($minimumRating !== null) {
                    $statisticQuery->where('average_rating', '>=', $minimumRating);
                }

                if ($maximumRating !== null) {
                    $statisticQuery->where('average_rating', '<=', $maximumRating);
                }

                if ($votesMin !== null) {
                    $statisticQuery->where('rating_count', '>=', $votesMin);
                }
            });
        }

        if ($type !== null) {
            $query->where('title_type', $type);
        }

        if ($types !== []) {
            $query->whereIn('title_type', $types);
        }

        if ($year !== null) {
            $query->where('release_year', $year);
        }

        if ($yearFrom !== null) {
            $query->where('release_year', '>=', $yearFrom);
        }

        if ($yearTo !== null) {
            $query->where('release_year', '<=', $yearTo);
        }

        if ($language !== null) {
            $query->where('original_language', $language);
        }

        if ($country !== null) {
            $query->where('origin_country', $country);
        }

        if ($runtime !== null) {
            match ($runtime) {
                'under-30' => $query->whereNotNull('runtime_minutes')->where('runtime_minutes', '<', 30),
                '30-60' => $query->whereBetween('runtime_minutes', [30, 60]),
                '60-90' => $query->whereBetween('runtime_minutes', [60, 90]),
                '90-120' => $query->whereBetween('runtime_minutes', [90, 120]),
                '120-plus' => $query->where('runtime_minutes', '>=', 120),
                default => $query,
            };
        }

        if ($status !== null) {
            $this->applyTelevisionStatusFilter($query, $status);
        }

        return match ($sort) {
            'name' => $query->orderBy('name'),
            'latest' => $query->orderByDesc('release_date')->orderByDesc('release_year')->orderBy('name'),
            'year' => $query->orderByDesc('release_year')->orderBy('name'),
            'rating' => $query->orderByTopRated(max(1, $votesMin ?? 1)),
            'trending' => $query->orderByTrending(),
            default => $query->orderBy('popularity_rank')->orderBy('name'),
        };
    }

    private function applyTelevisionStatusFilter(Builder $query, string $status): void
    {
        $today = now()->startOfDay();
        $currentYear = (int) $today->format('Y');

        if ($status === 'limited') {
            $query->where('title_type', TitleType::MiniSeries);

            return;
        }

        $query->whereIn('title_type', [
            TitleType::Series,
            TitleType::MiniSeries,
        ]);

        match ($status) {
            'upcoming' => $query->where(function (Builder $statusQuery) use ($currentYear, $today): void {
                $statusQuery
                    ->where('release_year', '>', $currentYear)
                    ->orWhereDate('release_date', '>', $today);
            }),
            'returning' => $query
                ->where('title_type', TitleType::Series)
                ->where(function (Builder $statusQuery) use ($currentYear, $today): void {
                    $statusQuery
                        ->whereNull('release_date')
                        ->orWhereDate('release_date', '<=', $today)
                        ->orWhere('release_year', '<=', $currentYear);
                })
                ->whereNull('end_year'),
            'ended' => $query->whereNotNull('end_year')->where('end_year', '<=', $currentYear),
            default => $query,
        };
    }
}
