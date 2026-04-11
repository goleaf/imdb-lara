<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use Illuminate\Database\Eloquent\Builder;

class BuildSearchTitleResultsQueryAction
{
    public function __construct(
        protected BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     genre?: string|null,
     *     theme?: string|null,
     *     ratingMin?: int|float|string|null,
     *     ratingMax?: int|float|string|null,
     *     votesMin?: int|string|null,
     *     type?: string|null,
     *     sort?: string|null,
     *     yearFrom?: int|string|null,
     *     yearTo?: int|string|null,
     *     language?: string|null,
     *     country?: string|null,
     *     runtime?: string|null,
     *     status?: string|null
     * }  $filters
     */
    public function handle(array $filters = []): Builder
    {
        return $this->buildPublicTitleIndexQuery->handle([
            ...$filters,
            'excludeEpisodes' => false,
        ]);
    }
}
