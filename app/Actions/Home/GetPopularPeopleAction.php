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
        if (Person::usesCatalogOnlySchema() && ! Person::catalogPeopleAvailable()) {
            return new Collection;
        }

        return Cache::remember(
            "home:popular-people:v2:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->buildPublicPeopleIndexQuery
                ->handle(['sort' => 'popular'])
                ->limit($limit)
                ->get(),
        );
    }
}
