<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;

class GetPopularPeopleAction
{
    public function __construct(
        private BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery,
    ) {}

    /**
     * @return Collection<int, Person>
     */
    public function handle(int $limit = 6): Collection
    {
        return $this->buildPublicPeopleIndexQuery
            ->handle()
            ->reorder()
            ->orderBy('popularity_rank')
            ->orderByDesc('credits_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
