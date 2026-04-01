<?php

namespace App\Http\Controllers;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Contracts\View\View;

class LatestReviewController extends Controller
{
    public function __invoke(): View
    {
        $reviews = Review::query()
            ->select(['id', 'user_id', 'title_id', 'headline', 'body', 'contains_spoilers', 'published_at'])
            ->where('status', ReviewStatus::Published)
            ->with([
                'author:id,name,username',
                'title:id,name,slug,title_type,release_year,plot_outline',
                'title.mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Poster)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->latest('published_at')
            ->simplePaginate(12)
            ->withQueryString();

        return view('reviews.index', [
            'reviews' => $reviews,
        ]);
    }
}
