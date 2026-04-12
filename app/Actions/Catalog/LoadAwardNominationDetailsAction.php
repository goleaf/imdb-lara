<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\AwardNomination;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadAwardNominationDetailsAction
{
    /**
     * @return array{
     *     awardNomination: AwardNomination,
     *     headlineLabel: string,
     *     linkedNominees: Collection<int, Person>,
     *     linkedTitles: Collection<int, Title>,
     *     summaryItems: Collection<int, array{label: string, value: string}>,
     *     cohortEntries: Collection<int, array{
     *         href: string,
     *         label: string,
     *         meta: string|null,
     *         creditedAs: string|null,
     *         statusLabel: string,
     *         isWinner: bool,
     *         isCurrent: bool
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(AwardNomination $awardNomination): array
    {
        $awardNomination->loadMissing([
            'awardEvent:imdb_id,name',
            'awardCategory:id,name',
            'title' => fn ($titleQuery) => $titleQuery
                ->selectCatalogCardColumns()
                ->publishedCatalog()
                ->withCatalogCardRelations(),
            'movieAwardNominationNominees' => fn ($nomineeQuery) => $nomineeQuery
                ->select(['movie_award_nomination_id', 'name_basic_id', 'position'])
                ->with(
                    AwardNomination::catalogNomineePeopleAvailable()
                        ? [
                            'person' => fn ($personQuery) => $personQuery->select(Person::directoryColumns()),
                        ]
                        : []
                )
                ->orderBy('position'),
            'movieAwardNominationTitles' => fn ($nominationTitleQuery) => $nominationTitleQuery
                ->select(['movie_award_nomination_id', 'nominated_movie_id', 'position'])
                ->with([
                    'title' => fn ($titleQuery) => $titleQuery
                        ->selectCatalogCardColumns()
                        ->publishedCatalog()
                        ->withCatalogCardRelations(),
                ])
                ->orderBy('position'),
        ]);

        $linkedNominees = $awardNomination->movieAwardNominationNominees
            ->map(fn ($nomineeRow) => $nomineeRow->person)
            ->filter(fn ($person): bool => $person instanceof Person)
            ->unique('id')
            ->values();

        $linkedTitles = collect([$awardNomination->title])
            ->concat($awardNomination->movieAwardNominationTitles->map(fn ($nominationTitleRow) => $nominationTitleRow->title))
            ->filter(fn ($title): bool => $title instanceof Title)
            ->unique('id')
            ->values();

        $headlineLabel = $this->entryLabel($awardNomination);
        $summaryItems = collect([
            ['label' => 'Event', 'value' => (string) ($awardNomination->awardEvent?->name ?? 'Archive event')],
            ['label' => 'Category', 'value' => (string) ($awardNomination->awardCategory?->name ?? 'Uncategorized')],
            ['label' => 'Year', 'value' => $awardNomination->award_year ? (string) $awardNomination->award_year : 'Unscheduled'],
            ['label' => 'Status', 'value' => $awardNomination->is_winner ? 'Winner' : 'Nominee'],
            ['label' => 'Winner rank', 'value' => $awardNomination->winner_rank ? '#'.number_format($awardNomination->winner_rank) : 'Not ranked'],
        ])->values();

        $cohortEntries = $this->loadCohortNominations($awardNomination)
            ->map(fn (AwardNomination $cohortNomination): array => $this->mapCohortEntry($cohortNomination, $awardNomination))
            ->values();

        $pageTitle = collect([
            $awardNomination->awardCategory?->name,
            $awardNomination->awardEvent?->name,
            $awardNomination->award_year,
        ])->filter()->implode(' · ') ?: 'Award nomination';
        $openGraphAsset = $linkedTitles->first()?->preferredPoster() ?: $linkedNominees->first()?->preferredHeadshot();

        return [
            'awardNomination' => $awardNomination,
            'headlineLabel' => $headlineLabel,
            'linkedNominees' => $linkedNominees,
            'linkedTitles' => $linkedTitles,
            'summaryItems' => $summaryItems,
            'cohortEntries' => $cohortEntries,
            'seo' => new PageSeoData(
                title: $pageTitle,
                description: 'Browse nominees, linked titles, and same-category archive entries for '.$pageTitle.'.',
                canonical: route('public.awards.nominations.show', $awardNomination),
                openGraphType: 'article',
                openGraphImage: $openGraphAsset?->url,
                openGraphImageAlt: $openGraphAsset?->alt_text,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Awards', 'href' => route('public.awards.index')],
                    ['label' => $awardNomination->awardCategory?->name ?? 'Award nomination'],
                ],
            ),
        ];
    }

    /**
     * @return Collection<int, AwardNomination>
     */
    private function loadCohortNominations(AwardNomination $awardNomination): Collection
    {
        if (! filled($awardNomination->event_imdb_id) || ! filled($awardNomination->award_category_id)) {
            return collect([$awardNomination]);
        }

        return AwardNomination::query()
            ->select([
                'id',
                'movie_id',
                'event_imdb_id',
                'award_category_id',
                'award_year',
                'text',
                'is_winner',
                'winner_rank',
                'position',
            ])
            ->forAwardCohort($awardNomination)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
                ...(
                    AwardNomination::catalogNomineePeopleAvailable()
                        ? [
                            'people' => fn ($personQuery) => $personQuery->select(Person::directoryColumns()),
                        ]
                        : []
                ),
            ])
            ->orderByDesc('is_winner')
            ->orderBy('winner_rank')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{
     *     href: string,
     *     label: string,
     *     meta: string|null,
     *     creditedAs: string|null,
     *     statusLabel: string,
     *     isWinner: bool,
     *     isCurrent: bool
     * }
     */
    private function mapCohortEntry(AwardNomination $awardNomination, AwardNomination $currentNomination): array
    {
        $label = $this->entryLabel($awardNomination);

        return [
            'href' => route('public.awards.nominations.show', $awardNomination),
            'label' => $label,
            'meta' => $this->entryMeta($awardNomination),
            'creditedAs' => $this->creditedAs($awardNomination, $label),
            'statusLabel' => $awardNomination->is_winner ? 'Winner' : 'Nominee',
            'isWinner' => (bool) $awardNomination->is_winner,
            'isCurrent' => $awardNomination->getKey() === $currentNomination->getKey(),
        ];
    }

    private function entryLabel(AwardNomination $awardNomination): string
    {
        if ($awardNomination->title instanceof Title) {
            return $awardNomination->title->name;
        }

        $peopleLabel = $this->peopleLabel($awardNomination);

        if ($peopleLabel !== null) {
            return $peopleLabel;
        }

        if (filled($awardNomination->text)) {
            return (string) $awardNomination->text;
        }

        return 'Archived entry';
    }

    private function entryMeta(AwardNomination $awardNomination): ?string
    {
        if ($awardNomination->title instanceof Title) {
            return collect([
                $this->peopleLabel($awardNomination),
                $awardNomination->title->typeLabel(),
                $awardNomination->title->release_year,
            ])->filter()->implode(' · ');
        }

        if ($awardNomination->loadedPeople()->isNotEmpty()) {
            return collect([
                number_format($awardNomination->loadedPeople()->count()).' '.str('person')->plural($awardNomination->loadedPeople()->count()),
                $awardNomination->award_year,
            ])->filter()->implode(' · ');
        }

        return $awardNomination->award_year ? (string) $awardNomination->award_year : null;
    }

    private function creditedAs(AwardNomination $awardNomination, string $label): ?string
    {
        if (! filled($awardNomination->text)) {
            return null;
        }

        $creditedName = trim((string) $awardNomination->text);

        return strcasecmp($creditedName, $label) === 0 ? null : $creditedName;
    }

    private function peopleLabel(AwardNomination $awardNomination): ?string
    {
        $people = $awardNomination->loadedPeople()
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
