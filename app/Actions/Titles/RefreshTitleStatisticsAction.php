<?php

namespace App\Actions\Titles;

use App\Models\Title;
use App\Models\TitleStatistic;
use App\ReviewStatus;

class RefreshTitleStatisticsAction
{
    public function handle(Title $title): TitleStatistic
    {
        return TitleStatistic::query()->updateOrCreate(
            [
                'title_id' => $title->id,
            ],
            [
                'rating_count' => $title->ratings()->count(),
                'average_rating' => round((float) ($title->ratings()->avg('score') ?? 0), 2),
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
