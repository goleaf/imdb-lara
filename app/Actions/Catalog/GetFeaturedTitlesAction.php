<?php

namespace App\Actions\Catalog;

use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

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
        return $this->buildPublicTitleIndexQuery
            ->handle([
                'sort' => 'popular',
                'excludeEpisodes' => true,
            ])
            ->limit($limit)
            ->get();
    }
}
