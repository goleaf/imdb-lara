<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

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

        $resolvedLimit = max(1, min($limit, 8));

        return Cache::remember(
            'search:discovery-title-suggestions:'.md5(mb_strtolower($search).'|'.$resolvedLimit),
            now()->addMinutes(5),
            fn (): Collection => $this->buildPublicTitleIndexQuery
                ->handle([
                    'search' => $search,
                    'searchMode' => 'discovery',
                    'sort' => 'popular',
                    'excludeEpisodes' => false,
                    'includePresentationRelations' => false,
                ])
                ->limit($resolvedLimit)
                ->get(),
        );
    }
}
