<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

class GetDiscoveryTitleSuggestionsAction
{
    public function __construct(
        private BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    /**
     * @return Collection<int, Title>
     */
    public function handle(?string $search, int $limit = 6): Collection
    {
        $search = trim((string) $search);

        if (mb_strlen($search) < 2) {
            return new Collection;
        }

        return $this->buildPublicTitleIndexQuery
            ->handle([
                'search' => $search,
                'searchMode' => 'discovery',
                'sort' => 'popular',
                'excludeEpisodes' => false,
            ])
            ->limit(max(1, min($limit, 8)))
            ->get();
    }
}
