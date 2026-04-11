<?php

namespace App\Actions\Catalog;

use App\Models\Person;
use App\Models\Profession;
use Illuminate\Support\Facades\Cache;

class GetPeopleDirectorySnapshotAction
{
    /**
     * @return array{
     *     publishedPeopleCount: int,
     *     awardLinkedPeopleCount: int,
     *     creditedPeopleCount: int,
     *     professionCount: int,
     *     topProfessions: list<array{name: string, peopleCount: int}>
     * }
     */
    public function handle(): array
    {
        return Cache::remember(
            'catalog:people-directory-snapshot',
            now()->addMinutes(10),
            function (): array {
                $publishedPeopleQuery = Person::query()
                    ->select(['name_basics.id'])
                    ->published()
                    ->whereNotNull('name_basics.primaryname');

                $topProfessions = Profession::query()
                    ->select(['professions.id', 'professions.name'])
                    ->whereNotNull('professions.name')
                    ->whereHas('persons', fn ($query) => $query->whereNotNull('name_basics.primaryname'))
                    ->withCount([
                        'persons as published_people_count' => fn ($query) => $query->whereNotNull('name_basics.primaryname'),
                    ])
                    ->orderByDesc('published_people_count')
                    ->orderBy('professions.name')
                    ->limit(5)
                    ->get()
                    ->map(fn (Profession $profession): array => [
                        'name' => (string) $profession->name,
                        'peopleCount' => (int) ($profession->published_people_count ?? 0),
                    ])
                    ->values()
                    ->all();

                return [
                    'publishedPeopleCount' => (clone $publishedPeopleQuery)->count(),
                    'awardLinkedPeopleCount' => (clone $publishedPeopleQuery)
                        ->whereHas('awardNominations')
                        ->count(),
                    'creditedPeopleCount' => (clone $publishedPeopleQuery)
                        ->whereHas('credits')
                        ->count(),
                    'professionCount' => Profession::query()
                        ->select(['professions.id'])
                        ->whereNotNull('professions.name')
                        ->whereHas('persons', fn ($query) => $query->whereNotNull('name_basics.primaryname'))
                        ->count(),
                    'topProfessions' => $topProfessions,
                ];
            },
        );
    }
}
