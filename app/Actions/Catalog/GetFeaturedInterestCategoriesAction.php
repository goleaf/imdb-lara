<?php

namespace App\Actions\Catalog;

use App\Models\InterestCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetFeaturedInterestCategoriesAction
{
    public function __construct(
        private BuildPublicInterestCategoryIndexQueryAction $buildPublicInterestCategoryIndexQuery,
    ) {}

    /**
     * @return Collection<int, InterestCategory>
     */
    public function handle(int $limit = 4, ?int $exceptInterestCategoryId = null): Collection
    {
        $cacheKey = sprintf(
            'catalog:featured-interest-categories:v1:%d:%s',
            $limit,
            $exceptInterestCategoryId ?? 'all',
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($limit, $exceptInterestCategoryId): Collection {
                $query = $this->buildPublicInterestCategoryIndexQuery
                    ->handle(['sort' => 'popular'])
                    ->whereHas('interests', fn (Builder $interestQuery) => $interestQuery->whereHas('movies'));

                if ($exceptInterestCategoryId !== null) {
                    $query->whereKeyNot($exceptInterestCategoryId);
                }

                return $query
                    ->limit($limit)
                    ->get();
            },
        );
    }
}
