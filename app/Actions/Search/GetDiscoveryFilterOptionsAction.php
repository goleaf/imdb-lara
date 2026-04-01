<?php

namespace App\Actions\Search;

use App\Enums\TitleType;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetDiscoveryFilterOptionsAction
{
    /**
     * @return array{
     *     genres: Collection<int, Genre>,
     *     titleTypes: list<TitleType>,
     *     minimumRatings: list<int>,
     *     sortOptions: list<array{value: string, label: string}>
     * }
     */
    public function handle(): array
    {
        return Cache::remember(
            'search:discovery-filter-options',
            now()->addMinutes(10),
            fn (): array => [
                'genres' => Genre::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
                'titleTypes' => TitleType::cases(),
                'minimumRatings' => range(10, 1),
                'sortOptions' => [
                    ['value' => 'popular', 'label' => 'Popularity'],
                    ['value' => 'rating', 'label' => 'Rating'],
                    ['value' => 'year', 'label' => 'Year'],
                    ['value' => 'name', 'label' => 'Name'],
                ],
            ],
        );
    }
}
