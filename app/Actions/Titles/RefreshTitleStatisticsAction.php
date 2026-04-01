<?php

namespace App\Actions\Titles;

use App\Enums\ReviewStatus;
use App\Models\Title;
use App\Models\TitleStatistic;

class RefreshTitleStatisticsAction
{
    public function handle(Title $title): TitleStatistic
    {
        $ratingsByScore = $title->ratings()
            ->select(['score'])
            ->get()
            ->countBy(fn ($rating): string => (string) $rating->score);

        $ratingDistribution = TitleStatistic::normalizeRatingDistribution(
            collect(range(10, 1))
                ->mapWithKeys(fn (int $score): array => [
                    (string) $score => (int) ($ratingsByScore->get((string) $score) ?? $ratingsByScore->get($score) ?? 0),
                ])
                ->all(),
        );

        $ratingCount = array_sum($ratingDistribution);
        $weightedScoreTotal = collect($ratingDistribution)->reduce(
            fn (int $carry, int $count, string $score): int => $carry + ((int) $score * $count),
            0,
        );

        return TitleStatistic::query()->updateOrCreate(
            [
                'title_id' => $title->id,
            ],
            [
                'rating_count' => $ratingCount,
                'average_rating' => $ratingCount > 0
                    ? round($weightedScoreTotal / $ratingCount, 2)
                    : 0,
                'rating_distribution' => $ratingDistribution,
                'review_count' => $title->reviews()->where('status', ReviewStatus::Published)->count(),
                'watchlist_count' => $title->listItems()
                    ->whereHas('userList', fn ($query) => $query->where('is_watchlist', true))
                    ->count(),
                'episodes_count' => $title->seriesEpisodes()->count(),
                'awards_nominated_count' => $title->awardNominations()->count(),
                'awards_won_count' => $title->awardNominations()->where('is_winner', true)->count(),
            ],
        );
    }
}
