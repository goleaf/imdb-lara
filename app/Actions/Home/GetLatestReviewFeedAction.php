<?php

namespace App\Actions\Home;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetLatestReviewFeedAction
{
    public function query(): Builder
    {
        return Review::query()
            ->select([
                'id',
                'user_id',
                'title_id',
                'headline',
                'body',
                'contains_spoilers',
                'published_at',
            ])
            ->where('status', ReviewStatus::Published)
            ->with([
                'author:id,name,username',
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->latest('published_at');
    }

    /**
     * @return Collection<int, Review>
     */
    public function handle(int $limit = 4): Collection
    {
        return Cache::remember(
            "home:latest-reviews:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->query()
                ->limit($limit)
                ->get(),
        );
    }
}
