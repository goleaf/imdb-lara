<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Enums\TitleType;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicTitleIndexQueryAction
{
    /**
     * @param  array{
     *     search?: string,
     *     genre?: string|null,
     *     minimumRating?: int|float|string|null,
     *     type?: string|null,
     *     types?: list<string>,
     *     sort?: string|null,
     *     year?: int|string|null,
     *     excludeEpisodes?: bool
     * }  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $genre = filled($filters['genre'] ?? null) ? (string) ($filters['genre'] ?? null) : null;
        $minimumRating = filled($filters['minimumRating'] ?? null)
            ? (float) ($filters['minimumRating'] ?? null)
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
            $query->where('title_type', '!=', TitleType::Episode);
        }

        if ($search !== '') {
            $query->where(function (Builder $titleQuery) use ($search): void {
                $titleQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('plot_outline', 'like', "%{$search}%")
                    ->orWhere('synopsis', 'like', "%{$search}%")
                    ->orWhere('search_keywords', 'like', "%{$search}%");
            });
        }

        if ($genre !== null) {
            $query->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->where('slug', $genre));
        }

        if ($minimumRating !== null) {
            $query->whereHas('statistic', fn (Builder $statisticQuery) => $statisticQuery->where('average_rating', '>=', $minimumRating));
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

        return match ($sort) {
            'name' => $query->orderBy('name'),
            'latest' => $query->orderByDesc('release_date')->orderByDesc('release_year')->orderBy('name'),
            'year' => $query->orderByDesc('release_year')->orderBy('name'),
            'rating' => $query
                ->whereHas('statistic')
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('average_rating')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('rating_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderBy('name'),
            'trending' => $query
                ->whereHas('statistic')
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('watchlist_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('review_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderBy('popularity_rank')
                ->orderBy('name'),
            default => $query->orderBy('popularity_rank')->orderBy('name'),
        };
    }
}
