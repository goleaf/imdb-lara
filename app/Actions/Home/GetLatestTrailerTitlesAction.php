<?php

namespace App\Actions\Home;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetLatestTrailerTitlesAction
{
    public function query(): Builder
    {
        $trailerKinds = [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette];

        return Title::query()
            ->select([
                'id',
                'name',
                'slug',
                'title_type',
                'release_year',
                'plot_outline',
                'popularity_rank',
                'is_published',
            ])
            ->publishedCatalog()
            ->whereHas('titleVideos', fn (Builder $videoQuery) => $videoQuery->whereIn('kind', $trailerKinds))
            ->with([
                'genres:id,name,slug',
                'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                'mediaAssets' => fn ($mediaQuery) => $mediaQuery
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
                'titleVideos' => fn ($videoQuery) => $videoQuery
                    ->select([
                        'id',
                        'mediable_type',
                        'mediable_id',
                        'kind',
                        'url',
                        'caption',
                        'provider',
                        'published_at',
                    ])
                    ->whereIn('kind', $trailerKinds)
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderByDesc(
                MediaAsset::query()
                    ->select('published_at')
                    ->whereColumn('media_assets.mediable_id', 'titles.id')
                    ->where('media_assets.mediable_type', Title::class)
                    ->whereIn('kind', $trailerKinds)
                    ->orderByDesc('published_at')
                    ->limit(1),
            );
    }

    /**
     * @return Collection<int, Title>
     */
    public function handle(int $limit = 4): Collection
    {
        return Cache::remember(
            "home:latest-trailers:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->query()
                ->limit($limit)
                ->get(),
        );
    }
}
