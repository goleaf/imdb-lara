<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetLatestTrailerTitlesAction
{
    public function __construct(
        private BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    public function query(): Builder
    {
        return $this->buildPublicTitleIndexQuery
            ->handle([
                'includePresentationRelations' => false,
                'sort' => 'trending',
            ])
            ->whereHas('titleVideos')
            ->with([
                'statistic:movie_id,aggregate_rating,vote_count',
                'titleImages' => fn (Builder $imageQuery) => $imageQuery
                    ->select(['id', 'movie_id', 'position', 'url', 'width', 'height', 'type'])
                    ->whereIn('type', ['poster', 'backdrop', 'still_frame', 'gallery'])
                    ->limit(6),
                'primaryImageRecord:movie_id,url,width,height,type',
                'plotRecord:movie_id,plot',
                'titleVideos' => fn (Builder $videoQuery) => $videoQuery
                    ->select([
                        'imdb_id',
                        'movie_id',
                        'video_type_id',
                        'name',
                        'description',
                        'width',
                        'height',
                        'runtime_seconds',
                        'position',
                    ])
                    ->orderBy('position')
                    ->orderBy('imdb_id')
                    ->limit(3),
            ]);
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
