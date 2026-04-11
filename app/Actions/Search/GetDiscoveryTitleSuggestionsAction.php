<?php

namespace App\Actions\Search;

use App\Models\MovieRating;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetDiscoveryTitleSuggestionsAction
{
    /**
     * @return Collection<int, Title>
     */
    public function handle(?string $search, int $limit = 6): Collection
    {
        $search = trim((string) $search);

        if (mb_strlen($search) < 2) {
            return new Collection;
        }

        $resolvedLimit = max(1, min($limit, 8));
        $cacheKey = sprintf(
            'search:discovery-title-suggestions:v3:%s:%s',
            Title::usesCatalogOnlySchema() ? 'catalog' : 'local',
            md5(mb_strtolower($search).'|'.$resolvedLimit),
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): Collection => $this->buildQuery($search)
                ->limit($resolvedLimit)
                ->get(),
        );
    }

    private function buildQuery(string $search): Builder
    {
        $query = Title::query()
            ->publishedCatalog()
            ->matchingSearch($search);

        if (Title::usesCatalogOnlySchema()) {
            return $query
                ->select([
                    'movies.id',
                    'movies.tconst',
                    'movies.imdb_id',
                    'movies.primarytitle',
                    'movies.originaltitle',
                    'movies.titletype',
                    'movies.startyear',
                ])
                ->addSelect([
                    'popularity_rank' => MovieRating::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1),
                ])
                ->orderByDesc('popularity_rank')
                ->orderByDesc('movies.startyear')
                ->orderBy('movies.primarytitle');
        }

        return $query
            ->select([
                'titles.id',
                'titles.name',
                'titles.slug',
                'titles.title_type',
                'titles.release_year',
                'titles.popularity_rank',
            ])
            ->orderBy('titles.popularity_rank')
            ->orderByDesc('titles.release_year')
            ->orderBy('titles.sort_title')
            ->orderBy('titles.name');
    }
}
