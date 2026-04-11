<?php

namespace App\Actions\Search;

use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PruneTopCatalogMatchAction
{
    /**
     * @template TModel of Model
     *
     * @param  Collection<int, TModel>  $suggestions
     * @return Collection<int, TModel>
     */
    public function handle(
        Collection $suggestions,
        Person|Title|null $topMatchRecord,
        int $limit,
    ): Collection {
        $normalizedLimit = max(1, min($limit, 24));

        if (! $topMatchRecord instanceof Model) {
            return $suggestions->take($normalizedLimit)->values();
        }

        return $suggestions
            ->reject(fn (Model $suggestion): bool => $suggestion->is($topMatchRecord))
            ->take($normalizedLimit)
            ->values();
    }
}
