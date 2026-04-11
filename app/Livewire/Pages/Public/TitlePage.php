<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Credit;
use App\Models\MovieAwardNominationNominee;
use App\Models\MovieDirector;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class TitlePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    private const COLLECTION_KEYS = [
        'galleryAssets',
        'castPreview',
        'crewGroups',
        'movieAkaRows',
        'movieAkaAttributeRows',
        'akaAttributeRows',
        'akaAttributeEntries',
        'akaTypeRows',
        'awardCategoryRows',
        'awardEventRows',
        'movieAwardNominationRows',
        'movieAwardNominationNomineeRows',
        'movieAwardNominationTitleRows',
        'movieAwardNominationSummaryRows',
        'movieCertificateRows',
        'movieCertificateSummaryRows',
        'movieCertificateAttributeRows',
        'movieCompanyCreditRows',
        'movieCompanyCreditAttributeRows',
        'movieCompanyCreditCountryRows',
        'movieCompanyCreditSummaryRows',
        'movieDirectorRows',
        'movieEpisodeRows',
        'movieEpisodeSummaryRows',
        'movieGenreRows',
        'movieImageSummaryRows',
        'certificateAttributeRows',
        'certificateRatingRows',
        'certificateAttributeEntries',
        'certificateRatingEntries',
        'certificateTitleEntries',
        'companyEntries',
        'companyRows',
        'companyCreditAttributeRows',
        'companyCreditCategoryRows',
        'movieBoxOfficeRows',
        'currencyRows',
        'countryRows',
        'genreRows',
        'genreEntries',
        'interestRows',
        'interestCategoryRows',
        'interestCategoryEntries',
        'interestPrimaryImageRows',
        'interestSimilarInterestRows',
        'detailItems',
        'certificateItems',
        'awardHighlights',
        'relatedTitles',
        'seasonNavigation',
        'seasons',
        'latestSeasonEpisodes',
        'topRatedEpisodes',
        'countries',
        'languages',
        'interestHighlights',
        'archiveLinks',
        'heroStats',
    ];

    public ?Title $title = null;

    public function mount(Title $title): void
    {
        abort_unless($title->is_published, 404);

        $this->title = $title;

        $this->redirectCanonicalEpisode($title);
    }

    public function render(LoadTitleDetailsAction $loadTitleDetails): View
    {
        abort_unless($this->title instanceof Title, 404);

        return $this->renderPageView('titles.show', $this->normalizeTitleViewData(
            $loadTitleDetails->handle($this->title),
        ));
    }

    private function redirectCanonicalEpisode(Title $title): void
    {
        if ($title->title_type !== TitleType::Episode) {
            return;
        }

        $title->loadMissing([
            'episodeMeta:episode_movie_id,movie_id,season,episode_number,release_year,release_month,release_day',
            'episodeMeta.series' => fn ($query) => $query->select([
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
            ]),
        ]);

        if ($title->episodeMeta?->series instanceof Title) {
            $this->redirectRoute('public.episodes.show', [
                'series' => $title->episodeMeta->series,
                'season' => 'season-'.$title->episodeMeta->season_number,
                'episode' => $title,
            ]);

            return;
        }

        abort(404);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTitleViewData(array $data): array
    {
        $data = $this->withCollectionDefaults($data, self::COLLECTION_KEYS);
        $data = $this->withDefaultValues($data, [
            'poster' => null,
            'backdrop' => null,
            'primaryVideo' => null,
            'latestSeason' => null,
            'shareModalId' => 'title-share-'.$this->title?->getKey(),
            'shareUrl' => $this->title instanceof Title ? route('public.titles.show', $this->title) : null,
            'isSeriesLike' => false,
            'ratingCount' => 0,
        ]);
        $catalogInternalSectionCount = $this->countCatalogInternalSections($data);

        return [
            ...$data,
            'posterLightboxModalId' => 'title-poster-lightbox-'.$this->title?->getKey(),
            'featuredCastEntries' => $this->mapFeaturedCastEntries($data['castPreview']),
            'awardNomineeEntries' => $this->mapAwardNomineeEntries($data['movieAwardNominationNomineeRows']),
            'directorEntries' => $this->mapDirectorEntries($data['movieDirectorRows']),
            'catalogInternalSectionCount' => $catalogInternalSectionCount,
            'hasCatalogInternals' => $catalogInternalSectionCount > 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function countCatalogInternalSections(array $data): int
    {
        return collect([
            $data['movieAkaRows']->isNotEmpty(),
            $data['akaAttributeEntries']->isNotEmpty(),
            $data['akaTypeRows']->isNotEmpty(),
            $data['awardCategoryRows']->isNotEmpty(),
            $data['awardEventRows']->isNotEmpty(),
            $data['movieAwardNominationRows']->isNotEmpty(),
            $data['movieAwardNominationNomineeRows']->isNotEmpty(),
            $data['movieAwardNominationSummaryRows']->isNotEmpty(),
            $data['movieAwardNominationTitleRows']->isNotEmpty(),
            $data['movieCertificateRows']->isNotEmpty(),
            $data['movieCertificateAttributeRows']->isNotEmpty(),
            $data['movieCertificateSummaryRows']->isNotEmpty(),
            $data['certificateAttributeEntries']->isNotEmpty(),
            $data['certificateRatingEntries']->isNotEmpty() || $data['certificateTitleEntries']->isNotEmpty(),
            $data['movieCompanyCreditRows']->isNotEmpty(),
            $data['movieDirectorRows']->isNotEmpty(),
            $data['companyEntries']->isNotEmpty(),
            $data['movieCompanyCreditAttributeRows']->isNotEmpty(),
            $data['movieCompanyCreditCountryRows']->isNotEmpty(),
            $data['movieCompanyCreditSummaryRows']->isNotEmpty(),
            $data['movieEpisodeSummaryRows']->isNotEmpty(),
            $data['movieEpisodeRows']->isNotEmpty(),
            $data['companyCreditAttributeRows']->isNotEmpty(),
            $data['companyCreditCategoryRows']->isNotEmpty(),
            $data['countryRows']->isNotEmpty(),
            $data['movieBoxOfficeRows']->isNotEmpty(),
            $data['currencyRows']->isNotEmpty(),
            $data['movieImageSummaryRows']->isNotEmpty(),
            $data['genreEntries']->isNotEmpty(),
            $data['interestCategoryEntries']->isNotEmpty(),
            $data['interestPrimaryImageRows']->isNotEmpty(),
            $data['interestSimilarInterestRows']->isNotEmpty(),
            $data['interestRows']->isNotEmpty(),
        ])->filter()->count();
    }

    /**
     * @param  Collection<int, Credit>  $rows
     * @return Collection<int, array{
     *     name: string,
     *     profileHref: string,
     *     headshotUrl: string|null,
     *     headshotAlt: string|null,
     *     roleLabel: string,
     *     summary: string|null,
     *     nationality: string|null,
     *     creditsBadgeLabel: string|null
     * }>
     */
    private function mapFeaturedCastEntries(Collection $rows): Collection
    {
        return $rows
            ->map(function (Credit $credit): ?array {
                $castPerson = $credit->person;

                if (! $castPerson instanceof Person) {
                    return null;
                }

                $headshot = $castPerson->preferredHeadshot();

                return [
                    'name' => $castPerson->name,
                    'profileHref' => route('public.people.show', $castPerson),
                    'headshotUrl' => $headshot?->url,
                    'headshotAlt' => $headshot?->alt_text ?: $castPerson->name,
                    'roleLabel' => $credit->character_name ?: $credit->job ?: 'Cast',
                    'summary' => $castPerson->summaryText(),
                    'nationality' => $castPerson->nationality,
                    'creditsBadgeLabel' => $castPerson->credits_count ? $castPerson->creditsBadgeLabel() : null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, MovieAwardNominationNominee>  $rows
     * @return Collection<int, array{
     *     awardHref: string|null,
     *     awardLabel: string,
     *     awardMeta: string|null,
     *     nomineeHref: string|null,
     *     nomineeName: string|null,
     *     nomineeHeadshotUrl: string|null,
     *     nomineeHeadshotAlt: string|null
     * }>
     */
    private function mapAwardNomineeEntries(Collection $rows): Collection
    {
        return $rows
            ->map(function (MovieAwardNominationNominee $row): array {
                $awardNomination = $row->awardNomination;
                $nomineePerson = $row->person;
                $nomineeHeadshot = $nomineePerson?->preferredHeadshot();

                return [
                    'awardHref' => $awardNomination ? route('public.awards.nominations.show', $awardNomination) : null,
                    'awardLabel' => $awardNomination?->awardCategory?->name ?: 'Award nomination',
                    'awardMeta' => $awardNomination
                        ? collect([
                            $awardNomination->awardEvent?->name ?: 'Event',
                            $awardNomination->award_year,
                        ])->filter()->implode(' · ')
                        : null,
                    'nomineeHref' => $nomineePerson ? route('public.people.show', $nomineePerson) : null,
                    'nomineeName' => $nomineePerson?->name,
                    'nomineeHeadshotUrl' => $nomineeHeadshot?->url,
                    'nomineeHeadshotAlt' => $nomineeHeadshot?->alt_text ?: $nomineePerson?->name,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, MovieDirector>  $rows
     * @return Collection<int, array{
     *     name: string,
     *     headshotUrl: string|null,
     *     headshotAlt: string|null,
     *     profileHref: string|null,
     *     archiveHref: string|null,
     *     summary: string|null,
     *     professionLabels: list<string>,
     *     nationality: string|null,
     *     creditsBadgeLabel: string|null
     * }>
     */
    private function mapDirectorEntries(Collection $rows): Collection
    {
        return $rows
            ->map(function (MovieDirector $row): array {
                $directorPerson = $row->person;
                $directorHeadshot = $directorPerson?->preferredHeadshot();
                $directorName = $directorPerson?->name
                    ?? $row->nameBasic?->displayName
                    ?? $row->nameBasic?->primaryname
                    ?? (string) $row->name_basic_id;

                return [
                    'name' => $directorName,
                    'headshotUrl' => $directorHeadshot?->url,
                    'headshotAlt' => $directorHeadshot?->alt_text ?: $directorName,
                    'profileHref' => $directorPerson ? route('public.people.show', $directorPerson) : null,
                    'archiveHref' => $directorPerson
                        ? route('public.people.show', ['person' => $directorPerson, 'job' => 'Directing']).'#person-filmography'
                        : null,
                    'summary' => $directorPerson?->summaryText(),
                    'professionLabels' => $directorPerson instanceof Person ? $directorPerson->professionLabels() : [],
                    'nationality' => $directorPerson?->nationality,
                    'creditsBadgeLabel' => $directorPerson?->credits_count ? $directorPerson->creditsBadgeLabel() : null,
                ];
            })
            ->values();
    }
}
