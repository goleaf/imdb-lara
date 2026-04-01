<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

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
        return Cache::remember(
            "home:popular-people:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->buildPublicPeopleIndexQuery
                ->handle()
                ->reorder()
                ->orderBy('popularity_rank')
                ->orderByDesc('credits_count')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
        );
    }
}
