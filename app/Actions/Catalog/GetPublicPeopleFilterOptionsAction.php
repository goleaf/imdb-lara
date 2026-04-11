<?php

namespace App\Actions\Catalog;

use App\Models\Profession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GetPublicPeopleFilterOptionsAction
{
    /**
     * @return array{
     *     professions: Collection<int, string>,
     *     sortOptions: Collection<int, array{value: string, label: string}>
     * }
     */
    public function handle(): array
    {
        return Cache::remember(
            'catalog:people-filter-options',
            now()->addMinutes(10),
            fn (): array => [
                'professions' => Profession::query()
                    ->select(['professions.id', 'professions.name'])
                    ->whereNotNull('professions.name')
                    ->whereHas('nameBasics', fn ($query) => $query->whereNotNull('name_basics.primaryname'))
                    ->pluck('name')
                    ->sort()
                    ->values(),
                'sortOptions' => collect([
                    ['value' => 'popular', 'label' => 'Most popular'],
                    ['value' => 'name', 'label' => 'Alphabetical'],
                    ['value' => 'credits', 'label' => 'Most credits'],
                    ['value' => 'awards', 'label' => 'Most awards'],
                ]),
            ],
        );
    }
}
