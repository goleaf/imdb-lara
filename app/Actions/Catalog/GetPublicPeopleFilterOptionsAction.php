<?php

namespace App\Actions\Catalog;

use App\Models\Person;
use App\Models\PersonProfession;
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
            function (): array {
                if (Person::usesCatalogOnlySchema() && ! Person::catalogPeopleAvailable()) {
                    return [
                        'professions' => collect(),
                        'sortOptions' => collect([
                            ['value' => 'popular', 'label' => 'Most popular'],
                            ['value' => 'name', 'label' => 'Alphabetical'],
                            ['value' => 'credits', 'label' => 'Most credits'],
                            ['value' => 'awards', 'label' => 'Most awards'],
                        ]),
                    ];
                }

                return [
                    'professions' => Person::usesCatalogOnlySchema()
                        ? Profession::query()
                            ->whereHas('persons', fn ($query) => $query->published())
                            ->orderBy('professions.name')
                            ->pluck('name')
                            ->unique()
                            ->values()
                        : PersonProfession::query()
                            ->whereNotNull('profession')
                            ->whereHas('person', fn ($query) => $query->published())
                            ->pluck('profession')
                            ->sort()
                            ->unique()
                            ->values(),
                    'sortOptions' => collect([
                        ['value' => 'popular', 'label' => 'Most popular'],
                        ['value' => 'name', 'label' => 'Alphabetical'],
                        ['value' => 'credits', 'label' => 'Most credits'],
                        ['value' => 'awards', 'label' => 'Most awards'],
                    ]),
                ];
            },
        );
    }
}
