<?php

namespace App\Actions\Catalog;

use App\Models\Person;
use App\Models\PersonProfession;
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
                    ->select([Person::usesCatalogOnlySchema() ? 'name_basics.id' : 'people.id'])
                    ->published();

                $publishedProfessionRows = Person::usesCatalogOnlySchema()
                    ? Profession::query()
                        ->select(['professions.id', 'professions.name'])
                        ->withCount([
                            'persons as people_count' => fn ($query) => $query->published(),
                        ])
                        ->whereHas('persons', fn ($query) => $query->published())
                        ->orderByDesc('people_count')
                        ->orderBy('professions.name')
                        ->get()
                        ->map(fn (Profession $profession): array => [
                            'name' => (string) $profession->name,
                            'peopleCount' => (int) ($profession->people_count ?? 0),
                        ])
                        ->values()
                    : PersonProfession::query()
                        ->select(['id', 'person_id', 'profession'])
                        ->whereNotNull('profession')
                        ->whereHas('person', fn ($query) => $query->published())
                        ->get()
                        ->groupBy(fn (PersonProfession $profession): string => (string) $profession->profession)
                        ->map(fn ($rows, string $profession): array => [
                            'name' => $profession,
                            'peopleCount' => $rows
                                ->pluck('person_id')
                                ->filter(fn (mixed $personId): bool => is_numeric($personId))
                                ->unique()
                                ->count(),
                        ])
                        ->sortByDesc('peopleCount')
                        ->values();

                $topProfessions = $publishedProfessionRows
                    ->take(5)
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
                    'professionCount' => $publishedProfessionRows->count(),
                    'topProfessions' => $topProfessions,
                ];
            },
        );
    }
}
