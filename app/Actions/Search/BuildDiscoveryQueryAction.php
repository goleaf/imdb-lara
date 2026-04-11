<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use Illuminate\Database\Eloquent\Builder;

class BuildDiscoveryQueryAction
{
    public function __construct(
        public BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     genre?: string|null,
     *     theme?: string|null,
     *     minimumRating?: int|float|string|null,
     *     type?: string|null,
     *     sort?: string|null,
     *     yearFrom?: int|string|null,
     *     yearTo?: int|string|null,
     *     votesMin?: int|string|null,
     *     language?: string|null,
     *     country?: string|null,
     *     runtime?: string|null,
     *     awards?: string|null,
     *     includePresentationRelations?: bool
     * }  $filters
     */
    public function handle(array $filters = []): Builder
    {
        return $this->buildPublicTitleIndexQuery->handle([
            ...$filters,
            'searchMode' => 'discovery',
        ]);
    }
}
