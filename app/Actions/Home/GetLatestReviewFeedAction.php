<?php

namespace App\Actions\Home;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
                'title:id,name,slug,title_type,release_year,plot_outline',
                'title.mediaAssets' => fn ($mediaQuery) => $mediaQuery
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
            ->latest('published_at');
    }

    /**
     * @return Collection<int, Review>
     */
    public function handle(int $limit = 4): Collection
    {
        return $this->query()
            ->limit($limit)
            ->get();
    }
}
