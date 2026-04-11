<?php

namespace App\Actions\Titles;

use App\Models\Title;
use App\Models\TitleStatistic;

class RefreshTitleStatisticsAction
{
    public function handle(Title $title): TitleStatistic
    {
        return $title->relationLoaded('statistic') && $title->statistic instanceof TitleStatistic
            ? $title->statistic
            : $title->statistic()->firstOrNew(
                [
                    'movie_id' => $title->getKey(),
                ],
                [
                    'rating_distribution' => TitleStatistic::normalizeRatingDistribution(),
                ],
            );
    }
}
