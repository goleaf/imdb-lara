<?php

namespace App\Actions\Search;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use Illuminate\Database\Eloquent\Builder;

class BuildDiscoveryQueryAction
{
    /**
     * @param  array{search?: string, genre?: string, minimumRating?: int|float|string|null, type?: string|null, sort?: string|null}  $filters
     */
    public function handle(array $filters = []): Builder
    {
        return app(BuildPublicTitleIndexQueryAction::class)->handle($filters);
    }
}
