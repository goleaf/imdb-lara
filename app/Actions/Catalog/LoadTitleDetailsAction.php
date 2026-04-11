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
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MovieAka;
use App\Models\MovieAkaAttribute;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
     *     akaAttributeRows: Collection<int, AkaAttribute>,
     *     akaTypeRows: Collection<int, AkaType>,
     *     awardCategoryRows: Collection<int, AwardCategory>,
     *     awardEventRows: Collection<int, AwardEvent>,
     *     certificateAttributeRows: Collection<int, CertificateAttribute>,
     *     certificateRatingRows: Collection<int, CertificateRating>,
     *     companyRows: Collection<int, Company>,
     *     companyCreditAttributeRows: Collection<int, CompanyCreditAttribute>,
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
        $title->load([
            'genres:id,name',
            'statistic:movie_id,aggregate_rating,vote_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'titleVideos:imdb_id,movie_id,video_type_id,name,description,width,height,runtime_seconds,position',
            'primaryImageRecord:movie_id,url,width,height,type',
            'plotRecord:movie_id,plot',
            'boxOfficeRecord:movie_id,domestic_gross_amount,worldwide_gross_amount,opening_weekend_gross_amount,production_budget_amount',
            'countries:code,name',
            'languages:code,name',
            'interests:imdb_id,name,description,is_subgenre',
            'movieAkas' => fn ($query) => $query
                ->select(['id', 'movie_id', 'text', 'country_code', 'language_code', 'position'])
                ->with([
                    'movieAkaAttributes' => fn ($movieAkaAttributeQuery) => $movieAkaAttributeQuery
                        ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
                        ->with([
                            'akaAttribute:id,name',
                        ])
                        ->orderBy('position'),
                ])
                ->orderBy('position'),
            'titleAkas' => fn ($query) => $query
                ->select(['id', 'titleid', 'ordering', 'title', 'region', 'language', 'types', 'attributes', 'isoriginaltitle'])
                ->with([
                    'titleAkaTypes' => fn ($titleAkaTypeQuery) => $titleAkaTypeQuery
                        ->select(['title_aka_id', 'aka_type_id', 'position'])
                        ->with([
                            'akaType:id,name',
                        ])
                        ->orderBy('position'),
                ])
                ->orderBy('ordering'),
            'certificateRecords:id,movie_id,certificate_rating_id,country_code,position',
            'certificateRecords.certificateRating:id,name',
            'certificateRecords.movieCertificateAttributes:movie_certificate_id,certificate_attribute_id,position',
            'certificateRecords.movieCertificateAttributes.certificateAttribute:id,name',
            'movieCompanyCredits' => fn ($query) => $query
                ->select(['id', 'movie_id', 'company_imdb_id', 'position'])
                ->with([
                    'company:imdb_id,name',
                    'movieCompanyCreditAttributes' => fn ($movieCompanyCreditAttributeQuery) => $movieCompanyCreditAttributeQuery
                        ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
                        ->with([
                            'companyCreditAttribute:id,name',
                        ])
                        ->orderBy('position'),
                ])
                ->orderBy('position'),
            'parentsGuideSections:id,movie_id,parents_guide_category_id,position',
            'credits' => fn ($query) => $query
                ->select(['name_basic_id', 'movie_id', 'category', 'episode_count', 'position'])
                ->with([
                    'person' => fn ($personQuery) => $personQuery
                        ->select([
                            'id',
                            'nconst',
                            'imdb_id',
                            'primaryname',
                            'displayName',
                            'alternativeNames',
                            'primaryProfessions',
                            'biography',
                            'birthLocation',
                            'deathLocation',
                            'primaryImage_url',
                            'primaryImage_width',
                            'primaryImage_height',
                        ])
                        ->with([
                            'personImages:name_basic_id,position,url,width,height,type',
                            'professionTerms:id,name',
                        ]),
                ])
                ->orderBy('position')
                ->limit(24),
            'awardNominations' => fn ($query) => $query
                ->select(['id', 'movie_id', 'event_imdb_id', 'award_category_id', 'award_year', 'text', 'is_winner', 'winner_rank', 'position'])
                ->with([
                    'awardEvent:imdb_id,name',
                    'awardCategory:id,name',
                    'people' => fn ($peopleQuery) => $peopleQuery->select([
                        'name_basics.id',
                        'name_basics.nconst',
                        'name_basics.imdb_id',
                        'name_basics.primaryname',
                        'name_basics.displayName',
                        'name_basics.primaryImage_url',
                        'name_basics.primaryImage_width',
                        'name_basics.primaryImage_height',
                    ]),
                ])
                ->orderByDesc('is_winner')
                ->orderByDesc('award_year')
                ->orderBy('position')
                ->limit(8),
            'seasons:movie_id,season,episode_count',
        ]);
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
        $akaAttributeRows = $title->movieAkas
            ->flatMap(fn (MovieAka $movieAka): Collection => $movieAka->movieAkaAttributes)
            ->map(fn (MovieAkaAttribute $movieAkaAttribute): ?AkaAttribute => $movieAkaAttribute->akaAttribute)
            ->filter(fn (mixed $akaAttribute): bool => $akaAttribute instanceof AkaAttribute && filled($akaAttribute->name))
            ->unique('id')
            ->values();
        $akaTypeRows = $title->resolvedAkaTypes();
        $awardCategoryRows = $title->resolvedAwardCategories();
        $awardEventRows = $title->resolvedAwardEvents();
        $certificateAttributeRows = $title->resolvedCertificateAttributes();
        $certificateRatingRows = $title->resolvedCertificateRatings();
        $companyRows = $title->resolvedCompanies();
        $companyCreditAttributeRows = $title->resolvedCompanyCreditAttributes();
        $countries = $title->countries
            ->map(fn ($country): array => [
                'code' => strtoupper((string) $country->code),
                'label' => filled($country->name) ? (string) $country->name : strtoupper((string) $country->code),
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
            ->map(fn ($interest): array => [
                'name' => (string) $interest->name,
                'href' => route('public.search', ['q' => (string) $interest->name]),
                'isSubgenre' => (bool) $interest->is_subgenre,
            ])
            ->values();
        $certificateItems = $title->certificateRecords
            ->map(fn ($certificate): ?array => $certificate->certificateRating?->name
                ? [
                    'rating' => $certificate->certificateRating->name,
                    'country' => $certificate->country_code,
                ]
                : null)
            ->filter()
            ->values();
        $detailItems = collect([
            ['label' => 'Original title', 'value' => $title->original_name !== $title->name ? (string) $title->original_name : null],
            ['label' => 'Release year', 'value' => $title->release_year ? (string) $title->release_year : null],
            ['label' => 'Runtime', 'value' => $title->runtime_minutes ? $title->runtime_minutes.' min' : null],
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
                ])
                ->addSelect([
                    'popularity_rank' => TitleStatistic::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1),
                ])
                ->publishedCatalog()
                ->whereKeyNot($title->getKey())
                ->whereHas('genres', fn ($genreQuery) => $genreQuery->whereIn('genres.id', $genreIds))
                ->with([
                    'genres:id,name',
                    'statistic:movie_id,aggregate_rating,vote_count',
                    'titleImages:id,movie_id,position,url,width,height,type',
                    'primaryImageRecord:movie_id,url,width,height,type',
                ])
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
            'akaAttributeRows' => $akaAttributeRows,
            'akaTypeRows' => $akaTypeRows,
            'awardCategoryRows' => $awardCategoryRows,
            'awardEventRows' => $awardEventRows,
            'certificateAttributeRows' => $certificateAttributeRows,
            'certificateRatingRows' => $certificateRatingRows,
            'companyRows' => $companyRows,
            'companyCreditAttributeRows' => $companyCreditAttributeRows,
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
                    ])
                    ->with([
                        'statistic:movie_id,aggregate_rating,vote_count',
                        'titleImages:id,movie_id,position,url,width,height,type',
                        'primaryImageRecord:movie_id,url,width,height,type',
                        'plotRecord:movie_id,plot',
                    ]),
            ]);

        if (is_int($seasonNumber)) {
            $query->where('movie_episodes.season', $seasonNumber);
        }

        return $query;
    }
}
