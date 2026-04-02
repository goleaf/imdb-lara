<?php

namespace App\Actions\Home;

use App\Actions\Lists\BuildPublicListsIndexQueryAction;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetFeaturedPublicListsAction
{
    public function __construct(
        protected BuildPublicListsIndexQueryAction $buildPublicListsIndexQuery,
    ) {}

    public function query(): Builder
    {
        return $this->buildPublicListsIndexQuery->handle(sort: 'most_titles');
    }

    /**
     * @return Collection<int, UserList>
     */
    public function handle(int $limit = 6): Collection
    {
        return Cache::remember(
            "home:featured-public-lists:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->query()
                ->limit($limit)
                ->get(),
        );
    }
}
