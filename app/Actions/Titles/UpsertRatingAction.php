<?php

namespace App\Actions\Titles;

use App\Models\Rating;
use App\Models\Title;
use App\Models\User;

class UpsertRatingAction
{
    public function __construct(
        public RefreshTitleStatisticsAction $refreshTitleStatistics,
    ) {}

    public function handle(User $user, Title $title, int $score): Rating
    {
        $rating = Rating::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'title_id' => $title->id,
            ],
            [
                'score' => $score,
            ],
        );

        $this->refreshTitleStatistics->handle($title);

        return $rating;
    }
}
