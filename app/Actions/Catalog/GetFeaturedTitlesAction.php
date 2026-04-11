<?php

namespace App\Actions\Catalog;

use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetFeaturedTitlesAction
{
    public function __construct(
        public BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    /**
     * @return Collection<int, Title>
     */
    public function handle(int $limit = 6): Collection
    {
        return Cache::remember(
            "catalog:featured-titles:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->buildPublicTitleIndexQuery
                ->handle([
                    'sort' => 'popular',
                    'excludeEpisodes' => true,
                ])
                ->limit($limit)
                ->get(),
        );
    }
}
