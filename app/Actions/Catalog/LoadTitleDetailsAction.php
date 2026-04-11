<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\AkaAttribute;
use App\Models\AkaType;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\CertificateAttribute;
use App\Models\CertificateRating;
use App\Models\Company;
use App\Models\CompanyCreditAttribute;
use App\Models\CompanyCreditCategory;
use App\Models\Country;
use App\Models\Credit;
use App\Models\Currency;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\InterestPrimaryImage;
use App\Models\InterestSimilarInterest;
use App\Models\MovieAka;
use App\Models\MovieAkaAttribute;
use App\Models\MovieAwardNominationNominee;
use App\Models\MovieAwardNominationSummary;
use App\Models\MovieAwardNominationTitle;
use App\Models\MovieBoxOffice;
use App\Models\MovieCertificate;
use App\Models\MovieCertificateAttribute;
use App\Models\MovieCertificateSummary;
use App\Models\MovieCompanyCredit;
use App\Models\MovieCompanyCreditAttribute;
use App\Models\MovieCompanyCreditCountry;
use App\Models\MovieCompanyCreditSummary;
use App\Models\MovieDirector;
use App\Models\MovieEpisode;
use App\Models\MovieEpisodeSummary;
use App\Models\MovieGenre;
use App\Models\MovieImageSummary;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class LoadTitleDetailsAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: mixed,
     *     backdrop: mixed,
     *     primaryVideo: mixed,
     *     galleryAssets: Collection<int, mixed>,
     *     castPreview: Collection<int, Credit>,
     *     crewGroups: Collection<int, array{role: string, credits: Collection<int, Credit>}>,
     *     movieAkaRows: Collection<int, MovieAka>,
     *     movieAkaAttributeRows: Collection<int, MovieAkaAttribute>,
     *     akaAttributeRows: Collection<int, AkaAttribute>,
     *     akaAttributeEntries: Collection<int, array{
     *         id: int,
     *         label: string,
     *         description: string,
     *         href: string,
     *         linkedAkaCount: int,
     *         linkedAkas: Collection<int, array{text: string, meta: string|null}>
     *     }>,
     *     akaTypeRows: Collection<int, AkaType>,
     *     awardCategoryRows: Collection<int, AwardCategory>,
     *     awardEventRows: Collection<int, AwardEvent>,
     *     movieAwardNominationRows: Collection<int, AwardNomination>,
     *     movieAwardNominationNomineeRows: Collection<int, MovieAwardNominationNominee>,
     *     movieAwardNominationTitleRows: Collection<int, MovieAwardNominationTitle>,
     *     movieAwardNominationSummaryRows: Collection<int, MovieAwardNominationSummary>,
     *     movieCertificateRows: Collection<int, MovieCertificate>,
     *     movieCertificateSummaryRows: Collection<int, MovieCertificateSummary>,
     *     movieCertificateAttributeRows: Collection<int, MovieCertificateAttribute>,
     *     movieCompanyCreditRows: Collection<int, MovieCompanyCredit>,
     *     movieCompanyCreditAttributeRows: Collection<int, MovieCompanyCreditAttribute>,
     *     movieCompanyCreditAttributeEntries: Collection<int, array{
     *         companyLabel: string|null,
     *         companyHref: string|null,
     *         categoryLabel: string|null,
     *         categoryHref: string|null,
     *         attributeLabel: string|null,
     *         attributeHref: string|null,
     *         activeYearsLabel: string|null,
     *         countryBadges: Collection<int, array{code: string, label: string}>
     *     }>,
     *     movieCompanyCreditCountryRows: Collection<int, MovieCompanyCreditCountry>,
     *     movieCompanyCreditSummaryRows: Collection<int, MovieCompanyCreditSummary>,
     *     movieDirectorRows: Collection<int, MovieDirector>,
     *     movieEpisodeRows: Collection<int, MovieEpisode>,
     *     movieEpisodeSummaryRows: Collection<int, MovieEpisodeSummary>,
     *     movieGenreRows: Collection<int, MovieGenre>,
     *     movieImageSummaryRows: Collection<int, MovieImageSummary>,
     *     certificateAttributeRows: Collection<int, CertificateAttribute>,
     *     certificateRatingRows: Collection<int, CertificateRating>,
     *     certificateAttributeEntries: Collection<int, array{
     *         attribute: CertificateAttribute,
     *         usageCount: int,
     *         countries: Collection<int, array{code: string, label: string}>,
     *         ratings: Collection<int, CertificateRating>
     *     }>,
     *     certificateRatingEntries: Collection<int, array{
     *         rating: CertificateRating,
     *         usageCount: int,
     *         countries: Collection<int, array{code: string, label: string}>,
     *         attributes: Collection<int, CertificateAttribute>
     *     }>,
     *     certificateTitleEntries: Collection<int, array{
     *         rating: CertificateRating|null,
     *         meaning: string,
     *         country: array{code: string, label: string}|null,
     *         attributes: Collection<int, CertificateAttribute>
     *     }>,
     *     companyEntries: Collection<int, array{
     *         company: Company,
     *         creditCount: int,
     *         categories: Collection<int, CompanyCreditCategory>,
     *         attributes: Collection<int, CompanyCreditAttribute>,
     *         countries: Collection<int, array{code: string, label: string}>,
     *         activeYears: Collection<int, string>
     *     }>,
     *     companyRows: Collection<int, Company>,
     *     companyCreditAttributeRows: Collection<int, CompanyCreditAttribute>,
     *     companyCreditCategoryRows: Collection<int, CompanyCreditCategory>,
     *     movieBoxOfficeRows: Collection<int, MovieBoxOffice>,
     *     currencyRows: Collection<int, Currency>,
     *     countryRows: Collection<int, Country>,
     *     genreRows: Collection<int, Genre>,
     *     genreEntries: Collection<int, array{
     *         genre: Genre,
     *         href: string,
     *         description: string,
     *         titleCountLabel: string|null,
     *         previewUrl: string|null,
     *         previewAlt: string|null
     *     }>,
     *     interestRows: Collection<int, Interest>,
     *     interestCategoryRows: Collection<int, InterestCategory>,
     *     interestCategoryEntries: Collection<int, array{
     *         interestCategory: InterestCategory,
     *         matchedInterestCountLabel: string,
     *         matchedInterests: Collection<int, array{
     *             name: string,
     *             href: string,
     *             isSubgenre: bool
     *         }>
     *     }>,
     *     interestPrimaryImageRows: Collection<int, InterestPrimaryImage>,
     *     interestSimilarInterestRows: Collection<int, InterestSimilarInterest>,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     certificateItems: Collection<int, array{rating: string, country: string|null}>,
     *     awardHighlights: Collection<int, AwardNomination>,
     *     relatedTitles: Collection<int, Title>,
     *     seasonNavigation: Collection<int, Season>,
     *     seasons: Collection<int, mixed>,
     *     latestSeason: Season|null,
     *     latestSeasonEpisodes: Collection<int, Episode>,
     *     topRatedEpisodes: Collection<int, Episode>,
     *     countries: Collection<int, array{code: string, label: string}>,
     *     languages: Collection<int, array{code: string, label: string}>,
     *     interestHighlights: Collection<int, array{name: string, href: string, isSubgenre: bool}>,
     *     archiveLinks: Collection<int, array{label: string, href: string, icon: string, copy: string, status: string}>,
     *     shareModalId: string,
     *     shareUrl: string,
     *     isSeriesLike: bool,
     *     ratingCount: int,
     *     heroStats: Collection<int, array{label: string, value: string, copy: string}>,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->loadMissing(Title::catalogDetailRelations());
        $this->loadCreditPreview($title);
        $this->loadAwardHighlights($title);
        $this->hydrateMovieCompanyCreditRelations($title);
        $title->loadCount('credits');

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $primaryVideo = $title->preferredVideo();
        $galleryAssets = $title->groupedMediaAssetsByKind()
            ->flatten(1)
            ->unique('url')
            ->take(8)
            ->values();
        $castPreview = $title->credits
            ->filter(fn (Credit $credit): bool => $credit->department === 'Cast')
            ->take(8)
            ->values();
        $crewGroups = $title->credits
            ->reject(fn (Credit $credit): bool => $credit->department === 'Cast')
            ->groupBy(fn (Credit $credit): string => $credit->job ?: $credit->department)
            ->map(fn (Collection $credits, string $role): array => [
                'role' => $role,
                'credits' => $credits->take(4)->values(),
            ])
            ->take(6)
            ->values();
        $movieAkaRows = $title->resolvedMovieAkas();
        $movieAkaAttributeRows = $title->resolvedMovieAkaAttributes();
        $akaAttributeRows = $movieAkaAttributeRows
            ->map(fn (MovieAkaAttribute $movieAkaAttribute): ?AkaAttribute => $movieAkaAttribute->akaAttribute)
            ->filter(fn (mixed $akaAttribute): bool => $akaAttribute instanceof AkaAttribute && filled($akaAttribute->name))
            ->unique('id')
            ->values();
        $akaAttributeEntries = $this->buildAkaAttributeEntries($movieAkaRows, $movieAkaAttributeRows);
        $akaTypeRows = $title->resolvedAkaTypes();
        $awardCategoryRows = $title->resolvedAwardCategories();
        $awardEventRows = $title->resolvedAwardEvents();
        $movieAwardNominationRows = $title->resolvedMovieAwardNominations();
        $movieAwardNominationNomineeRows = $title->resolvedMovieAwardNominationNominees();
        $movieAwardNominationTitleRows = $title->resolvedMovieAwardNominationTitles();
        $movieAwardNominationSummaryRows = $title->resolvedMovieAwardNominationSummaries();
        $movieCertificateRows = $title->resolvedMovieCertificates();
        $movieCertificateSummaryRows = $title->resolvedMovieCertificateSummaries();
        $movieCertificateAttributeRows = $title->resolvedMovieCertificateAttributes();
        $movieCompanyCreditRows = $title->resolvedMovieCompanyCredits();
        $movieCompanyCreditAttributeRows = $title->resolvedMovieCompanyCreditAttributes();
        $movieCompanyCreditAttributeEntries = $this->buildMovieCompanyCreditAttributeEntries($movieCompanyCreditAttributeRows);
        $movieCompanyCreditCountryRows = $title->resolvedMovieCompanyCreditCountries();
        $movieCompanyCreditSummaryRows = $title->resolvedMovieCompanyCreditSummaries();
        $movieDirectorRows = $title->resolvedMovieDirectors();
        $movieEpisodeRows = $title->resolvedMovieEpisodes();
        $movieEpisodeSummaryRows = $title->resolvedMovieEpisodeSummaries();
        $movieGenreRows = $title->resolvedMovieGenres();
        $movieImageSummaryRows = $title->resolvedMovieImageSummaries();

        if ($movieCompanyCreditSummaryRows->isEmpty()) {
            $title->load([
                'companyCreditSummary:movie_id,total_count,next_page_token',
            ]);

            $movieCompanyCreditSummaryRows = $title->resolvedMovieCompanyCreditSummaries();
        }

        if ($movieEpisodeSummaryRows->isEmpty()) {
            $title->load([
                'episodeSummary:movie_id,total_count,next_page_token',
            ]);

            $movieEpisodeSummaryRows = $title->resolvedMovieEpisodeSummaries();
        }

        if ($movieImageSummaryRows->isEmpty()) {
            $title->load([
                'imageSummary:movie_id,total_count,next_page_token',
            ]);

            $movieImageSummaryRows = $title->resolvedMovieImageSummaries();
        }

        $certificateAttributeRows = $title->resolvedCertificateAttributes();
        $certificateRatingRows = $title->resolvedCertificateRatings();
        $certificateAttributeEntries = $this->buildCertificateAttributeEntries(
            $certificateAttributeRows,
            $movieCertificateAttributeRows,
        );
        $certificateRatingEntries = $this->buildCertificateRatingEntries(
            $certificateRatingRows,
            $movieCertificateRows,
        );
        $certificateTitleEntries = $this->buildCertificateTitleEntries($movieCertificateRows);
        $companyEntries = $this->buildCompanyEntries($movieCompanyCreditRows);
        $companyRows = $title->resolvedCompanies();
        $companyCreditAttributeRows = $title->resolvedCompanyCreditAttributes();
        $companyCreditCategoryRows = $title->resolvedCompanyCreditCategories();
        $movieBoxOfficeRows = $title->resolvedMovieBoxOfficeRows();
        $currencyRows = $title->resolvedCurrencies();
        $countryRows = $title->resolvedCountries();
        $genreRows = $title->resolvedGenres();
        $genreEntries = $this->buildGenreEntries($title, $movieGenreRows, $poster, $backdrop);
        $interestRows = $title->resolvedInterests();
        $interestCategoryRows = $title->resolvedInterestCategories();
        $interestCategoryEntries = $this->buildInterestCategoryEntries($title, $interestCategoryRows);
        $interestPrimaryImageRows = $title->resolvedInterestPrimaryImages();
        $interestSimilarInterestRows = $title->resolvedInterestSimilarInterests();
        $countries = $countryRows
            ->map(fn ($country): array => [
                'code' => strtoupper((string) $country->code),
                'label' => $country->resolvedLabel(),
            ])
            ->filter(fn (array $country): bool => $country['code'] !== '')
            ->unique('code')
            ->values();
        $languages = $title->languages
            ->map(fn ($language): array => [
                'code' => strtoupper((string) $language->code),
                'label' => filled($language->name) ? (string) $language->name : strtoupper((string) $language->code),
            ])
            ->filter(fn (array $language): bool => $language['code'] !== '')
            ->unique('code')
            ->values();
        $interestHighlights = $title->interests
            ->filter(fn ($interest): bool => filled($interest->name))
            ->take(8)
            ->map(function ($interest): array {
                $interestCategory = $interest->interestCategoryInterests
                    ->map(fn ($interestCategoryInterest) => $interestCategoryInterest->interestCategory)
                    ->first();

                return [
                    'name' => (string) $interest->name,
                    'href' => $interestCategory
                        ? route('public.interest-categories.show', $interestCategory)
                        : route('public.search', ['q' => (string) $interest->name]),
                    'isSubgenre' => (bool) $interest->is_subgenre,
                ];
            })
            ->values();
        $certificateItems = $movieCertificateRows
            ->map(fn ($certificate): ?array => $certificate->certificateRating?->name
                ? [
                    'rating' => $certificate->certificateRating->resolvedLabel(),
                    'country' => $certificate->resolvedCountryLabel(),
                ]
                : null)
            ->filter()
            ->values();
        $detailItems = collect([
            ['label' => 'Original title', 'value' => $title->original_name !== $title->name ? (string) $title->original_name : null],
            ['label' => 'Release year', 'value' => $title->release_year ? (string) $title->release_year : null],
            ['label' => 'Runtime', 'value' => $title->runtimeMinutesLabel()],
            ['label' => 'Country of origin', 'value' => $countries->pluck('label')->implode(', ') ?: null],
            ['label' => 'Primary language', 'value' => $languages->pluck('label')->implode(', ') ?: null],
            ['label' => 'Certification', 'value' => $certificateItems->first()['rating'] ?? null],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();
        $mediaAssetCount = $title->groupedMediaAssetsByKind()
            ->flatten(1)
            ->unique('url')
            ->count();
        $hasBoxOfficeRecord = filled($title->boxOfficeRecord?->worldwide_gross_amount)
            || filled($title->boxOfficeRecord?->domestic_gross_amount)
            || filled($title->boxOfficeRecord?->opening_weekend_gross_amount)
            || filled($title->boxOfficeRecord?->production_budget_amount);
        $archiveLinks = collect([
            [
                'label' => 'Full Cast & Crew',
                'href' => route('public.titles.cast', $title),
                'icon' => 'users',
                'copy' => 'Billing order, cast listings, and department-grouped crew pulled from title-linked name credits.',
                'status' => number_format((int) ($title->credits_count ?? 0)).' credits',
            ],
            [
                'label' => 'Media Gallery',
                'href' => route('public.titles.media', $title),
                'icon' => 'photo',
                'copy' => 'Posters, stills, backdrops, and title-linked videos from the imported catalog.',
                'status' => number_format($mediaAssetCount).' assets',
            ],
            [
                'label' => 'Box Office',
                'href' => route('public.titles.box-office', $title),
                'icon' => 'banknotes',
                'copy' => 'Commercial reporting, ranked positions, and derived comparisons when box office rows exist.',
                'status' => $hasBoxOfficeRecord ? 'Gross data available' : 'Awaiting import',
            ],
            [
                'label' => 'Parents Guide',
                'href' => route('public.titles.parents-guide', $title),
                'icon' => 'shield-check',
                'copy' => 'Certification and content-concern sections built from the imported advisory tables.',
                'status' => number_format($title->parentsGuideSections->count()).' sections',
            ],
            [
                'label' => 'Keywords & Connections',
                'href' => route('public.titles.metadata', $title),
                'icon' => 'tag',
                'copy' => 'Interest tags, adjacent themes, and connected titles discovered from catalog metadata.',
                'status' => number_format($title->interests->count()).' interests',
            ],
            [
                'label' => 'Trivia & Goofs',
                'href' => route('public.titles.trivia', $title),
                'icon' => 'sparkles',
                'copy' => 'Archive shell reserved for title trivia and goofs when that import feed is attached.',
                'status' => 'Catalog-ready',
            ],
        ]);
        $relatedTitles = collect();
        $genreIds = $title->genres->pluck('id')->all();
        $isSeriesLike = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true);
        $seasonNavigation = $title->seasons->values();
        $latestSeason = null;
        $latestSeasonEpisodes = collect();
        $topRatedEpisodes = collect();

        if ($genreIds !== []) {
            $relatedTitles = Title::query()
                ->selectCatalogCardColumns()
                ->addSelect([
                    'popularity_rank' => TitleStatistic::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1),
                ])
                ->publishedCatalog()
                ->whereKeyNot($title->getKey())
                ->whereHas('genres', fn ($genreQuery) => $genreQuery->whereIn('genres.id', $genreIds))
                ->withCatalogCardRelations()
                ->orderByDesc('popularity_rank')
                ->limit(6)
                ->get();
        }

        if ($isSeriesLike && $seasonNavigation->isNotEmpty()) {
            /** @var Season|null $latestSeason */
            $latestSeason = $seasonNavigation->last();

            if ($latestSeason instanceof Season) {
                $latestSeasonEpisodes = $this->episodeGuideQuery($title, $latestSeason->season_number)
                    ->orderBy('episode_number')
                    ->limit(4)
                    ->get()
                    ->values();
            }

            $topRatedEpisodes = $this->episodeGuideQuery($title)
                ->addSelect([
                    'episode_rating' => TitleStatistic::query()
                        ->select('aggregate_rating')
                        ->whereColumn('movie_ratings.movie_id', 'movie_episodes.episode_movie_id')
                        ->limit(1),
                    'episode_vote_count' => TitleStatistic::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movie_episodes.episode_movie_id')
                        ->limit(1),
                ])
                ->orderByDesc('episode_rating')
                ->orderByDesc('episode_vote_count')
                ->orderBy('season')
                ->orderBy('episode_number')
                ->limit(5)
                ->get()
                ->values();
        }

        $shareUrl = route('public.titles.show', $title);
        $shareModalId = 'share-title-'.$title->id;
        $heroStats = collect([
            [
                'label' => 'Audience rating',
                'value' => $title->statistic?->average_rating
                    ? number_format((float) $title->statistic->average_rating, 1)
                    : 'N/A',
                'copy' => 'IMDb catalog average',
            ],
            [
                'label' => 'Votes',
                'value' => number_format((int) ($title->statistic?->rating_count ?? 0)),
                'copy' => 'Recorded audience votes',
            ],
            [
                'label' => 'Awards',
                'value' => number_format($title->awardNominations->count()),
                'copy' => 'Linked nominations and wins',
            ],
            [
                'label' => $isSeriesLike ? 'Seasons' : 'Gallery',
                'value' => $isSeriesLike
                    ? number_format($title->seasons->count())
                    : number_format($galleryAssets->count()),
                'copy' => $isSeriesLike ? 'Published season records' : 'Images and trailers available',
            ],
        ]);

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'primaryVideo' => $primaryVideo,
            'galleryAssets' => $galleryAssets,
            'castPreview' => $castPreview,
            'crewGroups' => $crewGroups,
            'movieAkaRows' => $movieAkaRows,
            'movieAkaAttributeRows' => $movieAkaAttributeRows,
            'akaAttributeRows' => $akaAttributeRows,
            'akaAttributeEntries' => $akaAttributeEntries,
            'akaTypeRows' => $akaTypeRows,
            'awardCategoryRows' => $awardCategoryRows,
            'awardEventRows' => $awardEventRows,
            'movieAwardNominationRows' => $movieAwardNominationRows,
            'movieAwardNominationNomineeRows' => $movieAwardNominationNomineeRows,
            'movieAwardNominationTitleRows' => $movieAwardNominationTitleRows,
            'movieAwardNominationSummaryRows' => $movieAwardNominationSummaryRows,
            'movieCertificateRows' => $movieCertificateRows,
            'movieCertificateSummaryRows' => $movieCertificateSummaryRows,
            'movieCertificateAttributeRows' => $movieCertificateAttributeRows,
            'movieCompanyCreditRows' => $movieCompanyCreditRows,
            'movieCompanyCreditAttributeRows' => $movieCompanyCreditAttributeRows,
            'movieCompanyCreditAttributeEntries' => $movieCompanyCreditAttributeEntries,
            'movieCompanyCreditCountryRows' => $movieCompanyCreditCountryRows,
            'movieCompanyCreditSummaryRows' => $movieCompanyCreditSummaryRows,
            'movieDirectorRows' => $movieDirectorRows,
            'movieEpisodeRows' => $movieEpisodeRows,
            'movieEpisodeSummaryRows' => $movieEpisodeSummaryRows,
            'movieGenreRows' => $movieGenreRows,
            'movieImageSummaryRows' => $movieImageSummaryRows,
            'certificateAttributeRows' => $certificateAttributeRows,
            'certificateRatingRows' => $certificateRatingRows,
            'certificateAttributeEntries' => $certificateAttributeEntries,
            'certificateRatingEntries' => $certificateRatingEntries,
            'certificateTitleEntries' => $certificateTitleEntries,
            'companyEntries' => $companyEntries,
            'companyRows' => $companyRows,
            'companyCreditAttributeRows' => $companyCreditAttributeRows,
            'companyCreditCategoryRows' => $companyCreditCategoryRows,
            'movieBoxOfficeRows' => $movieBoxOfficeRows,
            'currencyRows' => $currencyRows,
            'countryRows' => $countryRows,
            'genreRows' => $genreRows,
            'genreEntries' => $genreEntries,
            'interestRows' => $interestRows,
            'interestCategoryRows' => $interestCategoryRows,
            'interestCategoryEntries' => $interestCategoryEntries,
            'interestPrimaryImageRows' => $interestPrimaryImageRows,
            'interestSimilarInterestRows' => $interestSimilarInterestRows,
            'detailItems' => $detailItems,
            'certificateItems' => $certificateItems,
            'awardHighlights' => $title->awardNominations->values(),
            'relatedTitles' => $relatedTitles,
            'seasonNavigation' => $seasonNavigation,
            'seasons' => $title->seasons->values(),
            'latestSeason' => $latestSeason,
            'latestSeasonEpisodes' => $latestSeasonEpisodes,
            'topRatedEpisodes' => $topRatedEpisodes,
            'countries' => $countries,
            'languages' => $languages,
            'interestHighlights' => $interestHighlights,
            'archiveLinks' => $archiveLinks,
            'shareModalId' => $shareModalId,
            'shareUrl' => $shareUrl,
            'isSeriesLike' => $isSeriesLike,
            'ratingCount' => (int) ($title->statistic?->rating_count ?? 0),
            'heroStats' => $heroStats,
            'seo' => new PageSeoData(
                title: $title->meta_title ?: $title->name,
                description: $title->meta_description ?: ($title->plot_outline ?: 'Browse cast, awards, genres, ratings, and release details for '.$title->name.'.'),
                canonical: $shareUrl,
                openGraphType: $isSeriesLike ? 'video.tv_show' : 'video.movie',
                openGraphImage: ($backdrop ?? $poster)?->url,
                openGraphImageAlt: ($backdrop ?? $poster)?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name],
                ],
            ),
        ];
    }

    /**
     * @param  Collection<int, MovieAka>  $movieAkaRows
     * @param  Collection<int, MovieAkaAttribute>  $movieAkaAttributeRows
     * @return Collection<int, array{
     *     id: int,
     *     label: string,
     *     description: string,
     *     href: string,
     *     linkedAkaCount: int,
     *     linkedAkas: Collection<int, array{text: string, meta: string|null}>
     * }>
     */
    private function buildAkaAttributeEntries(Collection $movieAkaRows, Collection $movieAkaAttributeRows): Collection
    {
        $movieAkasById = $movieAkaRows->keyBy('id');

        return $movieAkaAttributeRows
            ->map(fn (MovieAkaAttribute $movieAkaAttribute): ?AkaAttribute => $movieAkaAttribute->akaAttribute)
            ->filter(fn (mixed $akaAttribute): bool => $akaAttribute instanceof AkaAttribute && filled($akaAttribute->name))
            ->unique('id')
            ->map(function (AkaAttribute $akaAttribute) use ($movieAkaAttributeRows, $movieAkasById): array {
                $linkedAkas = $movieAkaAttributeRows
                    ->filter(fn (MovieAkaAttribute $movieAkaAttribute): bool => (int) $movieAkaAttribute->aka_attribute_id === (int) $akaAttribute->getKey())
                    ->map(fn (MovieAkaAttribute $movieAkaAttribute): ?MovieAka => $movieAkasById->get($movieAkaAttribute->movie_aka_id))
                    ->filter(fn (mixed $movieAka): bool => $movieAka instanceof MovieAka && filled($movieAka->text))
                    ->unique(fn (MovieAka $movieAka): string => implode('|', [
                        $movieAka->text,
                        $movieAka->country_code,
                        $movieAka->language_code,
                    ]))
                    ->sortBy('position')
                    ->values();

                return [
                    'id' => (int) $akaAttribute->getKey(),
                    'label' => $akaAttribute->resolvedLabel(),
                    'description' => $akaAttribute->shortDescription(),
                    'href' => route('public.aka-attributes.show', $akaAttribute),
                    'linkedAkaCount' => $linkedAkas->count(),
                    'linkedAkas' => $linkedAkas
                        ->map(fn (MovieAka $movieAka): array => [
                            'text' => (string) $movieAka->text,
                            'meta' => collect([
                                $movieAka->resolvedCountryLabel(),
                                $movieAka->resolvedLanguageLabel(),
                            ])->filter()->implode(' · ') ?: null,
                        ])
                        ->values(),
                ];
            })
            ->sortBy('label')
            ->values();
    }

    private function hydrateMovieCompanyCreditRelations(Title $title): void
    {
        if (! $title->relationLoaded('movieCompanyCredits')) {
            return;
        }

        $title->movieCompanyCredits->loadMissing([
            'company:imdb_id,name',
            'companyCreditCategory:id,name',
            'movieCompanyCreditAttributes' => fn (Builder $query) => $query
                ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
                ->with([
                    'companyCreditAttribute:id,name',
                    'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year',
                    'movieCompanyCredit.company:imdb_id,name',
                    'movieCompanyCredit.companyCreditCategory:id,name',
                ])
                ->orderBy('position'),
            'movieCompanyCreditCountries' => fn (Builder $query) => $query
                ->select(['movie_company_credit_id', 'country_code', 'position'])
                ->with([
                    'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year',
                    'movieCompanyCredit.company:imdb_id,name',
                    'movieCompanyCredit.companyCreditCategory:id,name',
                ])
                ->orderBy('position'),
        ]);
    }

    /**
     * @param  Collection<int, MovieCompanyCredit>  $movieCompanyCreditRows
     * @return Collection<int, array{
     *     company: Company,
     *     creditCount: int,
     *     categories: Collection<int, CompanyCreditCategory>,
     *     attributes: Collection<int, CompanyCreditAttribute>,
     *     countries: Collection<int, array{code: string, label: string}>,
     *     activeYears: Collection<int, string>
     * }>
     */
    private function buildCompanyEntries(Collection $movieCompanyCreditRows): Collection
    {
        return $movieCompanyCreditRows
            ->filter(fn (MovieCompanyCredit $movieCompanyCredit): bool => $movieCompanyCredit->company instanceof Company)
            ->groupBy('company_imdb_id')
            ->map(function (Collection $creditsForCompany): ?array {
                /** @var MovieCompanyCredit|null $leadCredit */
                $leadCredit = $creditsForCompany->first();

                if (! $leadCredit instanceof MovieCompanyCredit || ! $leadCredit->company instanceof Company) {
                    return null;
                }

                $categories = $creditsForCompany
                    ->map(fn (MovieCompanyCredit $movieCompanyCredit): ?CompanyCreditCategory => $movieCompanyCredit->companyCreditCategory)
                    ->filter(fn (mixed $companyCreditCategory): bool => $companyCreditCategory instanceof CompanyCreditCategory && filled($companyCreditCategory->name))
                    ->unique('id')
                    ->values();

                $attributes = $creditsForCompany
                    ->flatMap(function (MovieCompanyCredit $movieCompanyCredit): Collection {
                        if (! $movieCompanyCredit->relationLoaded('movieCompanyCreditAttributes')) {
                            return collect();
                        }

                        return $movieCompanyCredit->movieCompanyCreditAttributes;
                    })
                    ->map(fn (MovieCompanyCreditAttribute $movieCompanyCreditAttribute): ?CompanyCreditAttribute => $movieCompanyCreditAttribute->companyCreditAttribute)
                    ->filter(fn (mixed $companyCreditAttribute): bool => $companyCreditAttribute instanceof CompanyCreditAttribute && filled($companyCreditAttribute->name))
                    ->unique('id')
                    ->values();

                $countries = $creditsForCompany
                    ->flatMap(function (MovieCompanyCredit $movieCompanyCredit): Collection {
                        if (! $movieCompanyCredit->relationLoaded('movieCompanyCreditCountries')) {
                            return collect();
                        }

                        return $movieCompanyCredit->movieCompanyCreditCountries;
                    })
                    ->map(function (MovieCompanyCreditCountry $movieCompanyCreditCountry): ?array {
                        if (! filled($movieCompanyCreditCountry->country_code)) {
                            return null;
                        }

                        return [
                            'code' => strtoupper((string) $movieCompanyCreditCountry->country_code),
                            'label' => $movieCompanyCreditCountry->resolvedCountryLabel() ?? strtoupper((string) $movieCompanyCreditCountry->country_code),
                        ];
                    })
                    ->filter()
                    ->unique('code')
                    ->sortBy('label')
                    ->values();

                $activeYears = $creditsForCompany
                    ->map(fn (MovieCompanyCredit $movieCompanyCredit): ?string => $movieCompanyCredit->activeYearsLabel())
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'company' => $leadCredit->company,
                    'creditCount' => $creditsForCompany->count(),
                    'categories' => $categories,
                    'attributes' => $attributes,
                    'countries' => $countries,
                    'activeYears' => $activeYears,
                ];
            })
            ->filter()
            ->sortBy(fn (array $companyEntry): string => Str::lower((string) $companyEntry['company']->name))
            ->values();
    }

    /**
     * @param  Collection<int, CertificateAttribute>  $certificateAttributeRows
     * @param  Collection<int, MovieCertificateAttribute>  $movieCertificateAttributeRows
     * @return Collection<int, array{
     *     attribute: CertificateAttribute,
     *     usageCount: int,
     *     countries: Collection<int, array{code: string, label: string}>,
     *     ratings: Collection<int, CertificateRating>
     * }>
     */
    private function buildCertificateAttributeEntries(Collection $certificateAttributeRows, Collection $movieCertificateAttributeRows): Collection
    {
        return $certificateAttributeRows
            ->map(function (CertificateAttribute $certificateAttribute) use ($movieCertificateAttributeRows): array {
                $matchingRows = $movieCertificateAttributeRows
                    ->filter(fn (MovieCertificateAttribute $movieCertificateAttribute): bool => $movieCertificateAttribute->certificate_attribute_id === $certificateAttribute->getKey())
                    ->values();

                $countries = $matchingRows
                    ->map(function (MovieCertificateAttribute $movieCertificateAttribute): ?array {
                        $movieCertificate = $movieCertificateAttribute->movieCertificate;

                        if (! $movieCertificate instanceof MovieCertificate || ! filled($movieCertificate->country_code)) {
                            return null;
                        }

                        return [
                            'code' => strtoupper((string) $movieCertificate->country_code),
                            'label' => $movieCertificate->resolvedCountryLabel() ?? strtoupper((string) $movieCertificate->country_code),
                        ];
                    })
                    ->filter()
                    ->unique('code')
                    ->values();

                $ratings = $matchingRows
                    ->map(function (MovieCertificateAttribute $movieCertificateAttribute): ?CertificateRating {
                        $movieCertificate = $movieCertificateAttribute->movieCertificate;

                        if (! $movieCertificate instanceof MovieCertificate || ! $movieCertificate->relationLoaded('certificateRating')) {
                            return null;
                        }

                        return $movieCertificate->certificateRating;
                    })
                    ->filter(fn (mixed $certificateRating): bool => $certificateRating instanceof CertificateRating && filled($certificateRating->name))
                    ->unique('id')
                    ->values();

                return [
                    'attribute' => $certificateAttribute,
                    'usageCount' => $matchingRows->count(),
                    'countries' => $countries,
                    'ratings' => $ratings,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, CertificateRating>  $certificateRatingRows
     * @param  Collection<int, MovieCertificate>  $movieCertificateRows
     * @return Collection<int, array{
     *     rating: CertificateRating,
     *     usageCount: int,
     *     countries: Collection<int, array{code: string, label: string}>,
     *     attributes: Collection<int, CertificateAttribute>
     * }>
     */
    private function buildCertificateRatingEntries(Collection $certificateRatingRows, Collection $movieCertificateRows): Collection
    {
        return $certificateRatingRows
            ->map(function (CertificateRating $certificateRating) use ($movieCertificateRows): array {
                $matchingRows = $movieCertificateRows
                    ->filter(fn (MovieCertificate $movieCertificate): bool => $movieCertificate->certificate_rating_id === $certificateRating->getKey())
                    ->values();

                $countries = $matchingRows
                    ->map(function (MovieCertificate $movieCertificate): ?array {
                        if (! filled($movieCertificate->country_code)) {
                            return null;
                        }

                        return [
                            'code' => strtoupper((string) $movieCertificate->country_code),
                            'label' => $movieCertificate->resolvedCountryLabel() ?? strtoupper((string) $movieCertificate->country_code),
                        ];
                    })
                    ->filter()
                    ->unique('code')
                    ->values();

                $attributes = $matchingRows
                    ->flatMap(function (MovieCertificate $movieCertificate): Collection {
                        if (! $movieCertificate->relationLoaded('movieCertificateAttributes')) {
                            return collect();
                        }

                        return $movieCertificate->movieCertificateAttributes;
                    })
                    ->map(function (MovieCertificateAttribute $movieCertificateAttribute): ?CertificateAttribute {
                        if (! $movieCertificateAttribute->relationLoaded('certificateAttribute')) {
                            return null;
                        }

                        return $movieCertificateAttribute->certificateAttribute;
                    })
                    ->filter(fn (mixed $certificateAttribute): bool => $certificateAttribute instanceof CertificateAttribute && filled($certificateAttribute->name))
                    ->unique('id')
                    ->values();

                return [
                    'rating' => $certificateRating,
                    'usageCount' => $matchingRows->count(),
                    'countries' => $countries,
                    'attributes' => $attributes,
                ];
            })
            ->values();
    }

    private function buildCertificateTitleEntries(Collection $movieCertificateRows): Collection
    {
        return $movieCertificateRows
            ->map(function (MovieCertificate $movieCertificate): array {
                $country = filled($movieCertificate->country_code)
                    ? [
                        'code' => strtoupper((string) $movieCertificate->country_code),
                        'label' => $movieCertificate->resolvedCountryLabel() ?? strtoupper((string) $movieCertificate->country_code),
                    ]
                    : null;

                $attributes = $movieCertificate->relationLoaded('movieCertificateAttributes')
                    ? $movieCertificate->movieCertificateAttributes
                        ->map(function (MovieCertificateAttribute $movieCertificateAttribute): ?CertificateAttribute {
                            if (! $movieCertificateAttribute->relationLoaded('certificateAttribute')) {
                                return null;
                            }

                            return $movieCertificateAttribute->certificateAttribute;
                        })
                        ->filter(fn (mixed $certificateAttribute): bool => $certificateAttribute instanceof CertificateAttribute && filled($certificateAttribute->name))
                        ->unique('id')
                        ->values()
                    : collect();

                return [
                    'rating' => $movieCertificate->relationLoaded('certificateRating') ? $movieCertificate->certificateRating : null,
                    'meaning' => $movieCertificate->ratingDescription() ?? 'Regional age classification attached to this title.',
                    'country' => $country,
                    'attributes' => $attributes,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, MovieCompanyCreditAttribute>  $movieCompanyCreditAttributeRows
     * @return Collection<int, array{
     *     companyLabel: string|null,
     *     companyHref: string|null,
     *     categoryLabel: string|null,
     *     categoryHref: string|null,
     *     attributeLabel: string|null,
     *     attributeHref: string|null,
     *     activeYearsLabel: string|null,
     *     countryBadges: Collection<int, array{code: string, label: string}>
     * }>
     */
    private function buildMovieCompanyCreditAttributeEntries(Collection $movieCompanyCreditAttributeRows): Collection
    {
        return $movieCompanyCreditAttributeRows
            ->map(function (MovieCompanyCreditAttribute $movieCompanyCreditAttribute): ?array {
                $movieCompanyCredit = $movieCompanyCreditAttribute->movieCompanyCredit;
                $company = $movieCompanyCredit?->company;
                $category = $movieCompanyCredit?->companyCreditCategory;
                $attribute = $movieCompanyCreditAttribute->companyCreditAttribute;

                if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $attribute instanceof CompanyCreditAttribute || ! filled($attribute->name)) {
                    return null;
                }

                $countryBadges = $movieCompanyCredit->relationLoaded('movieCompanyCreditCountries')
                    ? $movieCompanyCredit->movieCompanyCreditCountries
                        ->map(function (MovieCompanyCreditCountry $movieCompanyCreditCountry): ?array {
                            if (! filled($movieCompanyCreditCountry->country_code)) {
                                return null;
                            }

                            return [
                                'code' => strtoupper((string) $movieCompanyCreditCountry->country_code),
                                'label' => $movieCompanyCreditCountry->resolvedCountryLabel() ?? strtoupper((string) $movieCompanyCreditCountry->country_code),
                            ];
                        })
                        ->filter()
                        ->unique('code')
                        ->values()
                    : collect();

                return [
                    'companyLabel' => $company?->name,
                    'companyHref' => $company ? route('public.companies.show', $company) : null,
                    'categoryLabel' => $category?->name,
                    'categoryHref' => $company && $category ? route('public.companies.show', ['company' => $company, 'category' => (string) $category->getKey()]) : null,
                    'attributeLabel' => (string) $attribute->name,
                    'attributeHref' => route('public.company-credit-attributes.show', $attribute),
                    'activeYearsLabel' => $movieCompanyCredit->activeYearsLabel(),
                    'countryBadges' => $countryBadges,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, MovieGenre>  $movieGenreRows
     * @return Collection<int, array{
     *     genre: Genre,
     *     href: string,
     *     description: string,
     *     titleCountLabel: string|null,
     *     previewUrl: string|null,
     *     previewAlt: string|null
     * }>
     */
    private function buildGenreEntries(Title $title, Collection $movieGenreRows, mixed $poster, mixed $backdrop): Collection
    {
        $previewAsset = $backdrop ?? $poster;
        $previewUrl = filled($previewAsset?->url) ? (string) $previewAsset->url : null;
        $previewAlt = $previewUrl !== null
            ? (filled($previewAsset?->alt_text) ? (string) $previewAsset->alt_text : $title->name.' preview image')
            : null;

        return $movieGenreRows
            ->map(fn (MovieGenre $movieGenre): ?Genre => $movieGenre->genre)
            ->filter(fn (mixed $genre): bool => $genre instanceof Genre && filled($genre->name))
            ->unique('id')
            ->values()
            ->map(function (Genre $genre) use ($previewAlt, $previewUrl): array {
                return [
                    'genre' => $genre,
                    'href' => route('public.genres.show', $genre),
                    'description' => $genre->descriptionText(),
                    'titleCountLabel' => $genre->publishedTitleCount() > 0 ? $genre->publishedTitleCountBadgeLabel() : null,
                    'previewUrl' => $previewUrl,
                    'previewAlt' => $previewAlt,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, InterestCategory>  $interestCategoryRows
     * @return Collection<int, array{
     *     interestCategory: InterestCategory,
     *     matchedInterestCountLabel: string,
     *     matchedInterests: Collection<int, array{
     *         name: string,
     *         href: string,
     *         isSubgenre: bool
     *     }>
     * }>
     */
    private function buildInterestCategoryEntries(Title $title, Collection $interestCategoryRows): Collection
    {
        $interestRows = $title->resolvedInterests();

        return $interestCategoryRows
            ->map(function (InterestCategory $interestCategory) use ($interestRows): array {
                $matchedInterests = $interestRows
                    ->filter(function (Interest $interest) use ($interestCategory): bool {
                        if (! $interest->relationLoaded('interestCategoryInterests')) {
                            return false;
                        }

                        return $interest->interestCategoryInterests->contains(
                            fn ($interestCategoryInterest): bool => (int) $interestCategoryInterest->interest_category_id === (int) $interestCategory->getKey(),
                        );
                    })
                    ->map(fn (Interest $interest): array => [
                        'name' => (string) $interest->name,
                        'href' => route('public.search', ['q' => (string) $interest->name]),
                        'isSubgenre' => (bool) $interest->is_subgenre,
                    ])
                    ->values();

                return [
                    'interestCategory' => $interestCategory,
                    'matchedInterestCountLabel' => Number::format($matchedInterests->count()).' matched '.Str::plural('interest', $matchedInterests->count()),
                    'matchedInterests' => $matchedInterests->take(3)->values(),
                ];
            })
            ->values();
    }

    private function loadCreditPreview(Title $title): void
    {
        $title->setRelation('credits', $title->credits()
            ->select(['id', 'name_basic_id', 'movie_id', 'category', 'episode_count', 'position'])
            ->with([
                'nameCreditCharacters:name_credit_id,position,character_name',
                'person' => fn ($personQuery) => $personQuery
                    ->select(Person::directoryColumns())
                    ->with(Person::directoryRelations()),
            ])
            ->orderBy('position')
            ->limit(24)
            ->get());
    }

    private function loadAwardHighlights(Title $title): void
    {
        $title->setRelation('awardNominations', $title->awardNominations()
            ->select(['id', 'movie_id', 'event_imdb_id', 'award_category_id', 'award_year', 'text', 'is_winner', 'winner_rank', 'position'])
            ->with([
                'awardEvent:imdb_id,name',
                'awardCategory:id,name',
                'movieAwardNominationNominees' => fn ($nomineeQuery) => $nomineeQuery
                    ->select(['movie_award_nomination_id', 'name_basic_id', 'position'])
                    ->with([
                        'person' => fn ($personQuery) => $personQuery->select([
                            'name_basics.id',
                            'name_basics.nconst',
                            'name_basics.imdb_id',
                            'name_basics.primaryname',
                            'name_basics.displayName',
                            'name_basics.primaryImage_url',
                            'name_basics.primaryImage_width',
                            'name_basics.primaryImage_height',
                        ]),
                        'awardNomination:id,event_imdb_id,award_category_id,award_year',
                        'awardNomination.awardEvent:imdb_id,name',
                        'awardNomination.awardCategory:id,name',
                    ])
                    ->orderBy('position'),
                'movieAwardNominationTitles' => fn ($nominationTitleQuery) => $nominationTitleQuery
                    ->select(['movie_award_nomination_id', 'nominated_movie_id', 'position'])
                    ->with([
                        'title:id,primarytitle,originaltitle,tconst',
                        'movieAwardNomination:id,movie_id,event_imdb_id,award_category_id,award_year',
                        'movieAwardNomination.event:imdb_id,name',
                        'movieAwardNomination.awardCategory:id,name',
                    ])
                    ->orderBy('position'),
                'people' => fn ($peopleQuery) => $peopleQuery->select(Person::directoryColumns()),
            ])
            ->orderByDesc('is_winner')
            ->orderByDesc('award_year')
            ->orderBy('position')
            ->limit(8)
            ->get());
    }

    private function episodeGuideQuery(Title $series, ?int $seasonNumber = null): Builder
    {
        $query = Episode::query()
            ->select([
                'movie_episodes.episode_movie_id',
                'movie_episodes.movie_id',
                'movie_episodes.season',
                'movie_episodes.episode_number',
                'movie_episodes.release_year',
                'movie_episodes.release_month',
                'movie_episodes.release_day',
            ])
            ->where('movie_episodes.movie_id', $series->getKey())
            ->whereHas('title', fn (Builder $titleQuery) => $titleQuery
                ->published()
                ->whereNotNull('movies.primarytitle'))
            ->with([
                'title' => fn (Builder $titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->withCatalogHeroRelations(),
            ]);

        if (is_int($seasonNumber)) {
            $query->where('movie_episodes.season', $seasonNumber);
        }

        return $query;
    }
}
