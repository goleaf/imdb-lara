<?php

namespace App\Actions\Titles;

use App\Models\ListItem;
use App\Models\Rating;
use App\Models\Review;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;

class RefreshTitleStatisticsAction
{
    public function handle(Title $title): TitleStatistic
    {
        $existingStatistic = $title->relationLoaded('statistic') && $title->statistic instanceof TitleStatistic
            ? $title->statistic
            : $title->statistic()->first();

        $ratings = Rating::query()
            ->select(['score'])
            ->whereBelongsTo($title)
            ->get();

        $ratingCount = $ratings->count();
        $averageRating = $ratingCount > 0
            ? round((float) $ratings->avg('score'), 2)
            : 0;

        $ratingDistribution = TitleStatistic::normalizeRatingDistribution(
            $ratings
                ->countBy(fn (Rating $rating): string => (string) $rating->score)
                ->all(),
        );

        $reviewCount = Review::query()
            ->select(['id'])
            ->whereBelongsTo($title)
            ->published()
            ->count();

        $watchlistCount = ListItem::query()
            ->select(['list_items.id'])
            ->whereBelongsTo($title)
            ->whereHas('userList', fn (Builder $listQuery): Builder => $listQuery->where('is_watchlist', true))
            ->count();

        $episodesCount = $title->seriesEpisodes()->count();

        $statistic = $title->statistic()->updateOrCreate(
            [
                'title_id' => $title->getKey(),
            ],
            [
                'rating_count' => $ratingCount,
                'average_rating' => $averageRating,
                'rating_distribution' => $ratingDistribution,
                'review_count' => $reviewCount,
                'watchlist_count' => $watchlistCount,
                'episodes_count' => $episodesCount,
                'awards_nominated_count' => $existingStatistic?->awards_nominated_count ?? 0,
                'awards_won_count' => $existingStatistic?->awards_won_count ?? 0,
                'metacritic_score' => $existingStatistic?->metacritic_score,
                'metacritic_review_count' => $existingStatistic?->metacritic_review_count,
            ],
        );

        $title->setRelation('statistic', $statistic);

        return $statistic;
    }
}
