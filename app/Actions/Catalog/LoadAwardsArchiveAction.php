<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoadAwardsArchiveAction
{
    private const EVENT_LIMIT = 40;

    /**
     * @return array{
     *     summary: array{eventCount: int, awardCount: int, categoryCount: int, honoreeCount: int},
     *     featuredAwards: Collection<int, array{name: string, summary: string}>,
     *     events: Collection<int, array{
     *         name: string,
     *         awardName: string,
     *         year: int|null,
     *         edition: string|null,
     *         dateLabel: string|null,
     *         location: string|null,
     *         categoryCount: int,
     *         winnerCount: int,
     *         categories: Collection<int, array{
     *             name: string,
     *             scopeLabel: string,
     *             winnerCount: int,
     *             entryCount: int,
     *             entries: Collection<int, array{
     *                 label: string,
     *                 href: string|null,
     *                 meta: string|null,
     *                 creditedAs: string|null,
     *                 statusLabel: string,
     *                 isWinner: bool
     *             }>
     *         }>
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(): array
    {
        return Cache::remember('catalog:awards-archive:v2', now()->addMinutes(10), function (): array {
            $eventIds = AwardNomination::query()
                ->select(['event_imdb_id'])
                ->whereNotNull('event_imdb_id')
                ->distinct()
                ->orderBy('event_imdb_id')
                ->limit(self::EVENT_LIMIT)
                ->pluck('event_imdb_id')
                ->filter()
                ->values();

            $events = AwardEvent::query()
                ->select([
                    'imdb_id',
                    'name',
                ])
                ->whereIn('imdb_id', $eventIds)
                ->with([
                    'nominations' => fn ($nominationQuery) => $nominationQuery
                        ->select([
                            'id',
                            'event_imdb_id',
                            'movie_id',
                            'award_category_id',
                            'award_year',
                            'text',
                            'is_winner',
                            'winner_rank',
                            'position',
                        ])
                        ->with([
                            'awardCategory:id,name',
                            'title' => fn ($titleQuery) => $titleQuery
                                ->select([
                                    'movies.id',
                                    'movies.tconst',
                                    'movies.imdb_id',
                                    'movies.primarytitle',
                                    'movies.originaltitle',
                                    'movies.titletype',
                                    'movies.isadult',
                                    'movies.startyear',
                                    'movies.endyear',
                                    'movies.runtimeminutes',
                                    'movies.title_type_id',
                                    'movies.runtimeSeconds',
                                ])
                                ->publishedCatalog(),
                            'people' => fn ($personQuery) => $personQuery->select([
                                'name_basics.id',
                                'name_basics.nconst',
                                'name_basics.imdb_id',
                                'name_basics.primaryname',
                                'name_basics.displayName',
                                'name_basics.primaryprofession',
                            ]),
                        ])
                        ->orderByDesc('is_winner')
                        ->orderBy('position')
                        ->orderBy('id'),
                ])
                ->orderBy('name')
                ->get();

            $mappedEvents = $events
                ->map(fn (AwardEvent $event): array => $this->mapEvent($event))
                ->sort(function (array $left, array $right): int {
                    return ($right['year'] ?? 0) <=> ($left['year'] ?? 0)
                        ?: strcmp(mb_strtolower($left['name']), mb_strtolower($right['name']));
                })
                ->values();

            $featuredAwards = $mappedEvents
                ->groupBy('name')
                ->map(function (Collection $eventGroup, string $eventName): array {
                    $categoryCount = (int) $eventGroup->sum('categoryCount');
                    $winnerCount = (int) $eventGroup->sum('winnerCount');
                    $years = $eventGroup
                        ->pluck('year')
                        ->filter(fn (mixed $year): bool => is_int($year))
                        ->values();

                    return [
                        'name' => $eventName,
                        'summary' => collect([
                            $this->formatYearRange($years),
                            sprintf('%d %s', $categoryCount, Str::plural('category', $categoryCount)),
                            sprintf('%d %s', $winnerCount, Str::plural('winner', $winnerCount)),
                        ])->filter()->implode(' · '),
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $summary = [
                'eventCount' => $mappedEvents->count(),
                'awardCount' => $featuredAwards->count(),
                'categoryCount' => $events
                    ->flatMap(fn (AwardEvent $event): Collection => $event->nominations->pluck('award_category_id'))
                    ->filter()
                    ->unique()
                    ->count(),
                'honoreeCount' => $events
                    ->flatMap(fn (AwardEvent $event): Collection => $event->nominations->flatMap(
                        fn (AwardNomination $nomination): Collection => $this->honoreeKeys($nomination),
                    ))
                    ->filter()
                    ->unique()
                    ->count(),
            ];

            return [
                'summary' => $summary,
                'featuredAwards' => $featuredAwards,
                'events' => $mappedEvents,
                'seo' => new PageSeoData(
                    title: 'Awards',
                    description: 'Browse published award events, categories, winners, and nominated titles or people in the Screenbase awards archive.',
                    canonical: route('public.awards.index'),
                    breadcrumbs: [
                        ['label' => 'Home', 'href' => route('public.home')],
                        ['label' => 'Awards'],
                    ],
                ),
            ];
        });
    }

    /**
     * @return array{
     *     name: string,
     *     awardName: string,
     *     year: int|null,
     *     edition: string|null,
     *     dateLabel: string|null,
     *     location: string|null,
     *     categoryCount: int,
     *     winnerCount: int,
     *     categories: Collection<int, array{
     *         name: string,
     *         scopeLabel: string,
     *         winnerCount: int,
     *         entryCount: int,
     *         entries: Collection<int, array{
     *             label: string,
     *             href: string|null,
     *             meta: string|null,
     *             creditedAs: string|null,
     *             statusLabel: string,
     *             isWinner: bool
     *         }>
     *     }>
     * }
     */
    private function mapEvent(AwardEvent $event): array
    {
        $eventYear = $event->nominations
            ->pluck('award_year')
            ->filter(fn (mixed $year): bool => is_numeric($year))
            ->map(fn (mixed $year): int => (int) $year)
            ->max();

        $groupedCategories = $event->nominations
            ->sortBy(fn (AwardNomination $nomination): string => sprintf(
                '%s-%d-%08d-%08d',
                Str::lower($nomination->awardCategory?->name ?? 'zzz'),
                $nomination->is_winner ? 0 : 1,
                $nomination->position ?? PHP_INT_MAX,
                $nomination->id,
            ))
            ->groupBy(fn (AwardNomination $nomination): string => (string) ($nomination->award_category_id ?? 'uncategorized'))
            ->map(fn (Collection $categoryNominations): array => $this->mapCategory($categoryNominations))
            ->values();

        return [
            'name' => $event->name,
            'awardName' => $event->name,
            'year' => is_int($eventYear) ? $eventYear : null,
            'edition' => null,
            'dateLabel' => null,
            'location' => null,
            'categoryCount' => $groupedCategories->count(),
            'winnerCount' => $event->nominations->where('is_winner', true)->count(),
            'categories' => $groupedCategories,
        ];
    }

    /**
     * @param  Collection<int, AwardNomination>  $nominations
     * @return array{
     *     name: string,
     *     scopeLabel: string,
     *     winnerCount: int,
     *     entryCount: int,
     *     entries: Collection<int, array{
     *         label: string,
     *         href: string|null,
     *         meta: string|null,
     *         creditedAs: string|null,
     *         statusLabel: string,
     *         isWinner: bool
     *     }>
     * }
     */
    private function mapCategory(Collection $nominations): array
    {
        /** @var AwardNomination $leadNomination */
        $leadNomination = $nominations->first();
        $category = $leadNomination->awardCategory;

        return [
            'name' => $category?->name ?: 'Uncategorized',
            'scopeLabel' => $this->inferScopeLabel($nominations),
            'winnerCount' => $nominations->where('is_winner', true)->count(),
            'entryCount' => $nominations->count(),
            'entries' => $nominations
                ->map(fn (AwardNomination $nomination): array => $this->mapEntry($nomination))
                ->values(),
        ];
    }

    /**
     * @return array{
     *     label: string,
     *     href: string|null,
     *     meta: string|null,
     *     creditedAs: string|null,
     *     statusLabel: string,
     *     isWinner: bool
     * }
     */
    private function mapEntry(AwardNomination $nomination): array
    {
        $label = $this->entryLabel($nomination);

        return [
            'label' => $label,
            'href' => $this->entryLink($nomination),
            'meta' => $this->entryMeta($nomination),
            'creditedAs' => $this->creditedAs($nomination, $label),
            'statusLabel' => $nomination->is_winner ? 'Winner' : 'Nominee',
            'isWinner' => $nomination->is_winner,
        ];
    }

    private function entryLabel(AwardNomination $nomination): string
    {
        if ($nomination->title instanceof Title) {
            return $nomination->title->name;
        }

        $peopleLabel = $this->peopleLabel($nomination);

        if ($peopleLabel !== null) {
            return $peopleLabel;
        }

        if (filled($nomination->text)) {
            return (string) $nomination->text;
        }

        return 'Archived entry';
    }

    private function entryLink(AwardNomination $nomination): ?string
    {
        if ($nomination->title instanceof Title) {
            return route('public.titles.show', $nomination->title);
        }

        if ($nomination->people->count() === 1 && $nomination->person instanceof Person) {
            return route('public.people.show', $nomination->person);
        }

        return null;
    }

    private function entryMeta(AwardNomination $nomination): ?string
    {
        if ($nomination->title instanceof Title) {
            return collect([
                $this->peopleLabel($nomination),
                Str::headline($nomination->title->title_type->value),
                $nomination->title->release_year,
            ])->filter()->implode(' · ');
        }

        if ($nomination->people->isNotEmpty()) {
            return 'People';
        }

        return null;
    }

    private function creditedAs(AwardNomination $nomination, string $label): ?string
    {
        if (! filled($nomination->text)) {
            return null;
        }

        $creditedName = trim((string) $nomination->text);

        return strcasecmp($creditedName, $label) === 0 ? null : $creditedName;
    }

    /**
     * @param  Collection<int, AwardNomination>  $nominations
     */
    private function inferScopeLabel(Collection $nominations): string
    {
        $hasTitles = $nominations->contains(fn (AwardNomination $nomination): bool => $nomination->title instanceof Title);
        $hasPeople = $nominations->contains(fn (AwardNomination $nomination): bool => $nomination->people->isNotEmpty());

        return match (true) {
            $hasTitles && $hasPeople => 'Title and people recipients',
            $hasTitles => 'Title recipients',
            $hasPeople => 'People recipients',
            default => 'Archive entries',
        };
    }

    /**
     * @param  Collection<int, int>  $years
     */
    private function formatYearRange(Collection $years): ?string
    {
        if ($years->isEmpty()) {
            return null;
        }

        $startYear = $years->min();
        $endYear = $years->max();

        if ($startYear === $endYear) {
            return (string) $startYear;
        }

        return sprintf('%d–%d', $startYear, $endYear);
    }

    /**
     * @return Collection<int, string>
     */
    private function honoreeKeys(AwardNomination $nomination): Collection
    {
        $keys = collect();

        if ($nomination->movie_id !== null) {
            $keys->push('title:'.$nomination->movie_id);
        }

        foreach ($nomination->people as $person) {
            $keys->push('person:'.$person->getKey());
        }

        if ($keys->isNotEmpty()) {
            return $keys;
        }

        return filled($nomination->text)
            ? collect(['credited:'.Str::lower(trim((string) $nomination->text))])
            : collect();
    }

    private function peopleLabel(AwardNomination $nomination): ?string
    {
        $people = $nomination->people
            ->map(fn (Person $person): string => $person->name)
            ->filter()
            ->values();

        if ($people->isEmpty()) {
            return null;
        }

        if ($people->count() <= 2) {
            return $people->implode(', ');
        }

        return $people->take(2)->implode(', ').' +'.($people->count() - 2);
    }
}
