<?php

namespace App\Actions\Catalog;

use App\Models\PersonProfession;
use Illuminate\Support\Collection;

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
        return [
            'professions' => PersonProfession::query()
                ->select(['profession'])
                ->whereHas('person', fn ($query) => $query->published())
                ->distinct()
                ->orderBy('profession')
                ->pluck('profession')
                ->values(),
            'sortOptions' => collect([
                ['value' => 'popular', 'label' => 'Most popular'],
                ['value' => 'name', 'label' => 'Alphabetical'],
                ['value' => 'credits', 'label' => 'Most credits'],
                ['value' => 'awards', 'label' => 'Most awards'],
            ]),
        ];
    }
}
