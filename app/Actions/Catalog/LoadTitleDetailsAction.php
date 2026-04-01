<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Models\Title;

class LoadTitleDetailsAction
{
    public function handle(Title $title): Title
    {
        $title->load([
            'genres:id,name,slug',
            'companies:id,name,slug,kind,country_code',
            'credits.person:id,name,slug',
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets',
            'titleVideos' => fn ($query) => $query
                ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'caption', 'published_at'])
                ->whereIn('kind', [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette])
                ->orderByDesc('published_at')
                ->limit(3),
            'seasons' => fn ($query) => $query
                ->select(['id', 'series_id', 'name', 'slug', 'season_number', 'summary', 'release_year'])
                ->withCount('episodes')
                ->orderBy('season_number'),
            'reviews' => fn ($query) => $query
                ->where('status', ReviewStatus::Published)
                ->with('author:id,name,username')
                ->latest('published_at'),
        ]);

        return $title;
    }
}
