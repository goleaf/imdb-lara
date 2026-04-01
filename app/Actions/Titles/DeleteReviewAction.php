<?php

namespace App\Actions\Titles;

use App\Models\Review;

class DeleteReviewAction
{
    public function handle(Review $review): bool
    {
        return (bool) $review->delete();
    }
}
