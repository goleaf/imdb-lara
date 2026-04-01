<?php

namespace App\Http\Controllers;

use App\Actions\Home\GetLatestReviewFeedAction;
use Illuminate\Contracts\View\View;

class LatestReviewController extends Controller
{
    public function __invoke(GetLatestReviewFeedAction $getLatestReviewFeed): View
    {
        $reviews = $getLatestReviewFeed
            ->query()
            ->simplePaginate(12)
            ->withQueryString();

        return view('reviews.index', [
            'reviews' => $reviews,
        ]);
    }
}
