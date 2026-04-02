<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Episode;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoadAwardsArchiveAction
{
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
        return Cache::remember('catalog:awards-archive:v1', now()->addMinutes(10), function (): array {
            $events = AwardEvent::query()
                ->select([
                    'id',
                    'award_id',
                    'name',
                    'slug',
                    'year',
                    'edition',
                    'event_date',
                    'location',
                ])
                ->whereHas('award', fn ($awardQuery) => $awardQuery->where('is_published', true))
                ->whereHas('nominations')
                ->with([
                    'award:id,name,slug,country_code',
                    'nominations' => fn ($nominationQuery) => $nominationQuery
                        ->select([
                            'id',
                            'award_event_id',
                            'award_category_id',
                            'title_id',
                            'person_id',
                            'company_id',
                            'episode_id',
                            'credited_name',
                            'is_winner',
                            'sort_order',
                        ])
                        ->with([
                            'awardCategory:id,award_id,name,slug,recipient_scope',
                            'title:id,name,slug,title_type,release_year,is_published',
                            'person:id,name,slug,known_for_department,is_published',
                            'company:id,name',
                            'episode:id,title_id,series_id,season_id,season_number,episode_number',
                            'episode.title:id,name,slug,is_published',
                            'episode.series:id,name,slug,is_published',
                            'episode.season:id,series_id,name,slug,season_number',
                        ])
                        ->orderByDesc('is_winner')
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ])
                ->orderByDesc('year')
                ->orderByDesc('event_date')
                ->orderBy('name')
                ->get();

            $summary = [
                'eventCount' => $events->count(),
                'awardCount' => $events->pluck('award_id')->filter()->unique()->count(),
                'categoryCount' => $events
                    ->flatMap(fn (AwardEvent $event): Collection => $event->nominations->pluck('award_category_id'))
                    ->filter()
                    ->unique()
                    ->count(),
                'honoreeCount' => $events
                    ->flatMap(fn (AwardEvent $event): Collection => $event->nominations->map(fn (AwardNomination $nomination): ?string => $this->honoreeKey($nomination)))
                    ->filter()
                    ->unique()
                    ->count(),
            ];

            $featuredAwards = $events
                ->groupBy('award_id')
                ->map(function (Collection $awardEvents): array {
                    /** @var AwardEvent $leadEvent */
                    $leadEvent = $awardEvents->first();
                    $years = $awardEvents
                        ->pluck('year')
                        ->filter(fn (mixed $year): bool => is_numeric($year))
                        ->map(fn (mixed $year): int => (int) $year)
                        ->sort()
                        ->values();
                    $eventCount = $awardEvents->count();
                    $summaryParts = collect([
                        $this->formatYearRange($years),
                        $eventCount > 0 ? sprintf('%d recorded %s', $eventCount, Str::plural('event', $eventCount)) : null,
                        $awardEvents->pluck('location')->filter()->unique()->first(),
                    ])->filter();

                    return [
                        'name' => $leadEvent->award?->name ?: $leadEvent->name,
                        'summary' => $summaryParts->implode(' · '),
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $mappedEvents = $events
                ->map(fn (AwardEvent $event): array => $this->mapEvent($event))
                ->values();

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
        $groupedCategories = $event->nominations
            ->sortBy(fn (AwardNomination $nomination): string => sprintf(
                '%s-%d-%08d-%08d',
                Str::lower($nomination->awardCategory?->name ?? 'zzz'),
                $nomination->is_winner ? 0 : 1,
                $nomination->sort_order ?? PHP_INT_MAX,
                $nomination->id,
            ))
            ->groupBy(fn (AwardNomination $nomination): string => (string) ($nomination->award_category_id ?? 'uncategorized'))
            ->map(fn (Collection $categoryNominations): array => $this->mapCategory($categoryNominations))
            ->values();

        return [
            'name' => $event->name,
            'awardName' => $event->award?->name ?: 'Award Event',
            'year' => is_numeric($event->year) ? (int) $event->year : null,
            'edition' => filled($event->edition) ? (string) $event->edition : null,
            'dateLabel' => $event->event_date?->format('M j, Y'),
            'location' => filled($event->location) ? (string) $event->location : null,
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
            'scopeLabel' => $this->scopeLabel($category?->recipient_scope),
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
        if ($nomination->episode instanceof Episode && $nomination->episode->title instanceof Title) {
            return $nomination->episode->title->name;
        }

        if ($nomination->person instanceof Person) {
            return $nomination->person->name;
        }

        if ($nomination->title instanceof Title) {
            return $nomination->title->name;
        }

        if ($nomination->company !== null && filled($nomination->company->name)) {
            return (string) $nomination->company->name;
        }

        if (filled($nomination->credited_name)) {
            return (string) $nomination->credited_name;
        }

        return 'Archived entry';
    }

    private function entryLink(AwardNomination $nomination): ?string
    {
        if ($nomination->episode instanceof Episode
            && $nomination->episode->title instanceof Title
            && $nomination->episode->series instanceof Title
            && $nomination->episode->season !== null
        ) {
            return route('public.episodes.show', [
                'series' => $nomination->episode->series,
                'season' => $nomination->episode->season,
                'episode' => $nomination->episode->title,
            ]);
        }

        if ($nomination->person instanceof Person) {
            return route('public.people.show', $nomination->person);
        }

        if ($nomination->title instanceof Title) {
            return route('public.titles.show', $nomination->title);
        }

        return null;
    }

    private function entryMeta(AwardNomination $nomination): ?string
    {
        if ($nomination->episode instanceof Episode) {
            $parts = collect();

            if ($nomination->episode->series instanceof Title) {
                $parts->push($nomination->episode->series->name);
            }

            $parts->push(sprintf('S%d · E%d', $nomination->episode->season_number, $nomination->episode->episode_number));

            return $parts->implode(' · ');
        }

        if ($nomination->person instanceof Person) {
            return filled($nomination->person->known_for_department)
                ? Str::headline($nomination->person->known_for_department)
                : 'Person';
        }

        if ($nomination->title instanceof Title) {
            return collect([
                Str::headline($nomination->title->title_type->value),
                $nomination->title->release_year,
            ])->filter()->implode(' · ');
        }

        if ($nomination->company !== null) {
            return 'Company';
        }

        return null;
    }

    private function creditedAs(AwardNomination $nomination, string $label): ?string
    {
        if (! filled($nomination->credited_name)) {
            return null;
        }

        $creditedName = trim((string) $nomination->credited_name);

        return strcasecmp($creditedName, $label) === 0 ? null : $creditedName;
    }

    private function scopeLabel(?string $scope): string
    {
        return match ($scope) {
            'title' => 'Title recipients',
            'person' => 'People recipients',
            'episode' => 'Episode recipients',
            'company' => 'Company recipients',
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

        $startYear = $years->first();
        $endYear = $years->last();

        if ($startYear === $endYear) {
            return (string) $startYear;
        }

        return sprintf('%d–%d', $startYear, $endYear);
    }

    private function honoreeKey(AwardNomination $nomination): ?string
    {
        if ($nomination->episode_id !== null) {
            return 'episode:'.$nomination->episode_id;
        }

        if ($nomination->person_id !== null) {
            return 'person:'.$nomination->person_id;
        }

        if ($nomination->title_id !== null) {
            return 'title:'.$nomination->title_id;
        }

        if ($nomination->company_id !== null) {
            return 'company:'.$nomination->company_id;
        }

        return filled($nomination->credited_name)
            ? 'credited:'.Str::lower(trim((string) $nomination->credited_name))
            : null;
    }
}
