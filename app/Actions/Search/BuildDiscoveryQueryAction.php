<?php

namespace App\Actions\Search;

use App\MediaKind;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;

class BuildDiscoveryQueryAction
{
    /**
     * @param  array{search?: string, genre?: string, minimumRating?: int|float|string|null, type?: string|null, sort?: string|null}  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $genre = $filters['genre'] ?? null;
        $minimumRating = filled($filters['minimumRating'] ?? null)
            ? (float) $filters['minimumRating']
            : null;
        $type = $filters['type'] ?? null;
        $sort = $filters['sort'] ?? 'popular';

        $query = Title::query()
            ->select([
                'id',
                'name',
                'original_name',
                'slug',
                'title_type',
                'release_year',
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

        if ($search !== '') {
            $query->where(function (Builder $titleQuery) use ($search): void {
                $titleQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('plot_outline', 'like', "%{$search}%")
                    ->orWhere('synopsis', 'like', "%{$search}%");
            });
        }

        if (filled($genre)) {
            $query->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->where('slug', $genre));
        }

        if ($minimumRating !== null) {
            $query->whereHas('statistic', fn (Builder $statisticQuery) => $statisticQuery->where('average_rating', '>=', $minimumRating));
        }

        if (filled($type)) {
            $query->where('title_type', $type);
        }

        return match ($sort) {
            'name' => $query->orderBy('name'),
            'year' => $query->orderByDesc('release_year')->orderBy('name'),
            'rating' => $query->orderByDesc(
                TitleStatistic::query()
                    ->select('average_rating')
                    ->whereColumn('title_statistics.title_id', 'titles.id')
                    ->limit(1)
            )->orderBy('name'),
            default => $query->orderBy('popularity_rank')->orderBy('name'),
        };
    }
}
