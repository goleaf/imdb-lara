<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\TitleType as CatalogTitleType;
use App\Models\Concerns\FormatsRuntimeLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class Title extends Model
{
    use FormatsRuntimeLabels;

    protected $connection = 'imdb_mysql';

    protected $table = 'movies';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tconst',
        'titletype',
        'primarytitle',
        'originaltitle',
        'isadult',
        'startyear',
        'endyear',
        'runtimeminutes',
        'genres',
        'title_type_id',
        'imdb_id',
        'runtimeSeconds',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'isadult' => 'integer',
            'startyear' => 'integer',
            'endyear' => 'integer',
            'runtimeminutes' => 'integer',
            'title_type_id' => 'integer',
            'runtimeSeconds' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public static function catalogCardColumns(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogHeroRelations(): array
    {
        return [
            'statistic:movie_id,aggregate_rating,vote_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'plotRecord:movie_id,plot',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogCardRelations(): array
    {
        return [
            ...self::catalogHeroRelations(),
            'genres:id,name',
            'countries:code,name',
            'languages:code,name',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogListRelations(): array
    {
        return [
            ...self::catalogHeroRelations(),
            'genres:id,name',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogMediaRelations(): array
    {
        return [
            ...self::catalogHeroRelations(),
            'titleVideos:imdb_id,movie_id,video_type_id,name,description,width,height,runtime_seconds,position',
            'titleVideos.videoType:id,name',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogBoxOfficeRelations(): array
    {
        return [
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'boxOfficeRecord' => fn ($query) => $query->select([
                'movie_id',
                'domestic_gross_amount',
                'domestic_gross_currency_code',
                'worldwide_gross_amount',
                'worldwide_gross_currency_code',
                'opening_weekend_gross_amount',
                'opening_weekend_gross_currency_code',
                'opening_weekend_end_year',
                'opening_weekend_end_month',
                'opening_weekend_end_day',
                'production_budget_amount',
                'production_budget_currency_code',
            ]),
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogDetailRelations(): array
    {
        return [
            ...self::catalogMediaRelations(),
            'boxOfficeRecord' => fn ($query) => $query
                ->select([
                    'movie_id',
                    'domestic_gross_amount',
                    'domestic_gross_currency_code',
                    'worldwide_gross_amount',
                    'worldwide_gross_currency_code',
                    'opening_weekend_gross_amount',
                    'opening_weekend_gross_currency_code',
                    'production_budget_amount',
                    'production_budget_currency_code',
                ])
                ->with([
                    'productionBudget:code',
                    'domesticGross:code',
                    'openingWeekendGross:code',
                    'worldwideGross:code',
                ]),
            'genres' => fn ($genreQuery) => $genreQuery
                ->select(['genres.id', 'genres.name'])
                ->withCount([
                    'titles as published_titles_count' => fn (Builder $titleQuery) => $titleQuery->publishedCatalog(),
                ]),
            'movieGenres:movie_id,genre_id,position',
            'countries:code,name',
            'languages:code,name',
            'interests:imdb_id,name,description,is_subgenre',
            'interests.interestCategoryInterests:interest_category_id,interest_imdb_id,position',
            'interests.interestCategoryInterests.interestCategory' => fn ($interestCategoryQuery) => $interestCategoryQuery
                ->selectDirectoryColumns()
                ->withDirectoryMetrics()
                ->withDirectoryPreviewImage(),
            'interests.interestPrimaryImages:interest_imdb_id,url,width,height,type',
            'interests.interestSimilarInterests:interest_imdb_id,similar_interest_imdb_id,position',
            'interests.interestSimilarInterests.similar:imdb_id,name,description,is_subgenre',
            'movieAkas' => fn ($query) => $query
                ->select(['id', 'movie_id', 'text', 'country_code', 'language_code', 'position'])
                ->with([
                    'language:code,name',
                    'movieAkaAttributes' => fn ($movieAkaAttributeQuery) => $movieAkaAttributeQuery
                        ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
                        ->with([
                            'akaAttribute:id,name',
                        ])
                        ->orderBy('position'),
                ])
                ->orderBy('position'),
            'imageSummary:movie_id,total_count,next_page_token',
            'certificateRecords:id,movie_id,certificate_rating_id,country_code,position',
            'certificateRecords.certificateRating:id,name',
            'certificateRecords.movieCertificateAttributes' => fn ($movieCertificateAttributeQuery) => $movieCertificateAttributeQuery
                ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
                ->with([
                    'certificateAttribute:id,name',
                    'movieCertificate:id,movie_id,certificate_rating_id',
                    'movieCertificate.certificateRating:id,name',
                ])
                ->orderBy('position'),
            'certificateSummary:movie_id,total_count',
            'movieEpisodes:episode_movie_id,movie_id,season,episode_number,release_year,release_month,release_day',
            'episodeSummary:movie_id,total_count,next_page_token',
            'companyCreditSummary:movie_id,total_count,next_page_token',
            'movieDirectors' => fn ($movieDirectorQuery) => $movieDirectorQuery
                ->select(['movie_id', 'name_basic_id', 'position'])
                ->with([
                    'nameBasic:id,primaryname,displayName',
                    'person' => fn ($personQuery) => $personQuery
                        ->selectDirectoryColumns()
                        ->withDirectoryRelations()
                        ->withDirectoryMetrics(),
                ])
                ->orderBy('position'),
            'movieCompanyCredits' => fn ($query) => $query
                ->select(['id', 'movie_id', 'company_imdb_id', 'company_credit_category_id', 'start_year', 'end_year', 'position'])
                ->with([
                    'company:imdb_id,name',
                    'companyCreditCategory:id,name',
                    'movieCompanyCreditAttributes' => fn ($movieCompanyCreditAttributeQuery) => $movieCompanyCreditAttributeQuery
                        ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
                        ->with([
                            'companyCreditAttribute:id,name',
                            'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year',
                            'movieCompanyCredit.company:imdb_id,name',
                            'movieCompanyCredit.companyCreditCategory:id,name',
                        ])
                        ->orderBy('position'),
                    'movieCompanyCreditCountries' => fn ($movieCompanyCreditCountryQuery) => $movieCompanyCreditCountryQuery
                        ->select(['movie_company_credit_id', 'country_code', 'position'])
                        ->with([
                            'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year',
                            'movieCompanyCredit.company:imdb_id,name',
                            'movieCompanyCredit.companyCreditCategory:id,name',
                        ])
                        ->orderBy('position'),
                ])
                ->orderBy('position'),
            'movieGenres' => fn ($movieGenreQuery) => $movieGenreQuery
                ->select(['movie_id', 'genre_id', 'position'])
                ->with([
                    'genre' => fn ($genreQuery) => $genreQuery
                        ->select(['genres.id', 'genres.name'])
                        ->withCount([
                            'titles as published_titles_count' => fn (Builder $titleQuery) => $titleQuery->publishedCatalog(),
                        ]),
                ])
                ->orderBy('position'),
            'parentsGuideSections:id,movie_id,parents_guide_category_id,position',
            'awardNominationSummary:movie_id,nomination_count,win_count,next_page_token',
            'seasons:movie_id,season,episode_count',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if (preg_match('/-(?P<imdb>tt\d+)$/', (string) $value, $matches) === 1) {
            return $query->where('tconst', $matches['imdb']);
        }

        return $query
            ->where('tconst', (string) $value)
            ->orWhere('imdb_id', (string) $value);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('isadult', 0);
    }

    public function scopeWithoutEpisodes(Builder $query): Builder
    {
        return $query->whereNotIn('titletype', self::remoteTypesForCatalogType(CatalogTitleType::Episode));
    }

    public function scopePublishedCatalog(Builder $query): Builder
    {
        return $query->published()->withoutEpisodes();
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^tt\d+$/i', $search) === 1) {
            return $query->where(function (Builder $titleQuery) use ($search): void {
                $titleQuery
                    ->where('tconst', $search)
                    ->orWhere('imdb_id', $search);
            });
        }

        return $query->where(function (Builder $titleQuery) use ($search): void {
            $titleQuery
                ->where('primarytitle', 'like', '%'.$search.'%')
                ->orWhere('originaltitle', 'like', '%'.$search.'%')
                ->orWhere('tconst', 'like', '%'.$search.'%')
                ->orWhere('imdb_id', 'like', '%'.$search.'%')
                ->orWhereHas('plotRecord', fn (Builder $plotQuery) => $plotQuery->where('plot', 'like', '%'.$search.'%'));
        });
    }

    public function scopeMatchingDiscoverySearch(Builder $query, string $search): Builder
    {
        $search = Str::of($search)->squish()->toString();

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^tt\d+$/i', $search) === 1) {
            return $query->where(function (Builder $titleQuery) use ($search): void {
                $titleQuery
                    ->where('tconst', $search)
                    ->orWhere('imdb_id', $search);
            });
        }

        $prefixSearch = $search.'%';

        return $query->where(function (Builder $titleQuery) use ($prefixSearch, $search): void {
            $titleQuery
                ->where('primarytitle', $search)
                ->orWhere('originaltitle', $search)
                ->orWhere('primarytitle', 'like', $prefixSearch)
                ->orWhere('originaltitle', 'like', $prefixSearch);
        });
    }

    public function scopeForType(Builder $query, CatalogTitleType $type): Builder
    {
        return $query->whereIn('titletype', self::remoteTypesForCatalogType($type));
    }

    public function scopeRatedAtLeast(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query->whereHas(
            'statistic',
            fn (Builder $statisticQuery) => $statisticQuery->where('vote_count', '>=', $minimumVotes),
        );
    }

    public function scopeOrderByTopRated(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query->ratedAtLeast($minimumVotes)
            ->orderByDesc(self::statisticColumnSubquery('aggregate_rating'))
            ->orderByDesc(self::statisticColumnSubquery('vote_count'))
            ->orderBy('primarytitle');
    }

    public function scopeOrderByTrending(Builder $query): Builder
    {
        return $query
            ->orderByDesc(self::statisticColumnSubquery('vote_count'))
            ->orderByDesc('startyear')
            ->orderBy('primarytitle');
    }

    public function scopeSelectCatalogCardColumns(Builder $query): Builder
    {
        return $query->select(self::catalogCardColumns());
    }

    public function scopeWithCatalogCardRelations(Builder $query): Builder
    {
        return $query->with(self::catalogCardRelations());
    }

    public function scopeWithCatalogListRelations(Builder $query): Builder
    {
        return $query->with(self::catalogListRelations());
    }

    public function scopeWithCatalogHeroRelations(Builder $query): Builder
    {
        return $query->with(self::catalogHeroRelations());
    }

    public function scopeWithCatalogMediaRelations(Builder $query): Builder
    {
        return $query->with(self::catalogMediaRelations());
    }

    public function scopeWithCatalogBoxOfficeRelations(Builder $query): Builder
    {
        return $query->with(self::catalogBoxOfficeRelations());
    }

    public function scopeWithCatalogDetailRelations(Builder $query): Builder
    {
        return $query->with(self::catalogDetailRelations());
    }

    public function scopeInGenre(Builder $query, string|int|Genre $genre): Builder
    {
        $genreId = match (true) {
            $genre instanceof Genre => (int) $genre->getKey(),
            is_int($genre) => $genre,
            preg_match('/-g(?P<id>\d+)$/', $genre, $matches) === 1 => (int) $matches['id'],
            ctype_digit($genre) => (int) $genre,
            default => null,
        };

        if ($genreId === null) {
            return $query;
        }

        return $query->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->where('genres.id', $genreId));
    }

    public function scopeReleasedBetweenYears(Builder $query, ?int $yearFrom = null, ?int $yearTo = null): Builder
    {
        if ($yearFrom !== null) {
            $query->where('startyear', '>=', $yearFrom);
        }

        if ($yearTo !== null) {
            $query->where('startyear', '<=', $yearTo);
        }

        return $query;
    }

    public function scopeProducedInCountry(Builder $query, string $countryCode): Builder
    {
        return $query->whereHas(
            'countries',
            fn (Builder $countryQuery) => $countryQuery->where('countries.code', strtoupper($countryCode)),
        );
    }

    public function scopeSpokenInLanguage(Builder $query, string $languageCode): Builder
    {
        return $query->whereHas(
            'languages',
            fn (Builder $languageQuery) => $languageQuery->where('languages.code', strtoupper($languageCode)),
        );
    }

    public function scopeWithinRuntimeBucket(Builder $query, string $runtime): Builder
    {
        return match ($runtime) {
            'under-30' => $query->whereNotNull('runtimeminutes')->where('runtimeminutes', '<', 30),
            '30-60' => $query->whereBetween('runtimeminutes', [30, 60]),
            '60-90' => $query->whereBetween('runtimeminutes', [60, 90]),
            '90-120' => $query->whereBetween('runtimeminutes', [90, 120]),
            '120-plus' => $query->where('runtimeminutes', '>=', 120),
            default => $query,
        };
    }

    public function scopeForInterestCategory(Builder $query, InterestCategory|int $interestCategory): Builder
    {
        $interestCategoryId = $interestCategory instanceof InterestCategory
            ? (int) $interestCategory->getKey()
            : (int) $interestCategory;

        return $query->whereHas(
            'interests.interestCategories',
            fn (Builder $categoryQuery) => $categoryQuery->where('interest_categories.id', $interestCategoryId),
        );
    }

    public function scopeWithMatchedInterestCount(Builder $query, InterestCategory|int $interestCategory): Builder
    {
        $interestCategoryId = $interestCategory instanceof InterestCategory
            ? (int) $interestCategory->getKey()
            : (int) $interestCategory;

        return $query->withCount([
            'interests as matched_interest_count' => fn (Builder $interestQuery) => $interestQuery
                ->whereHas(
                    'interestCategories',
                    fn (Builder $categoryQuery) => $categoryQuery->where('interest_categories.id', $interestCategoryId),
                ),
        ]);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id', 'id', 'id')
            ->orderBy('movie_genres.position');
    }

    public function movieGenres(): HasMany
    {
        return $this->hasMany(MovieGenre::class, 'movie_id', 'id')->orderBy('position');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'movie_origin_countries', 'movie_id', 'country_code', 'id', 'code')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'movie_spoken_languages', 'movie_id', 'language_code', 'id', 'code')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'movie_interests', 'movie_id', 'interest_imdb_id', 'id', 'imdb_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class, 'movie_id', 'id')->orderBy('position');
    }

    public function movieAkas(): HasMany
    {
        return $this->hasMany(MovieAka::class, 'movie_id', 'id')->orderBy('position');
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class, 'movie_id', 'id')
            ->orderByDesc('award_year')
            ->orderBy('position');
    }

    public function awardNominationSummary(): HasOne
    {
        return $this->hasOne(MovieAwardNominationSummary::class, 'movie_id', 'id');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class, 'movie_id', 'id')->orderBy('season');
    }

    public function seriesEpisodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'movie_id', 'id')
            ->orderBy('season')
            ->orderBy('episode_number');
    }

    public function episodeMeta(): HasOne
    {
        return $this->hasOne(Episode::class, 'episode_movie_id', 'id');
    }

    public function titleImages(): HasMany
    {
        return $this->hasMany(TitleImage::class, 'movie_id', 'id')->orderBy('position');
    }

    public function imageSummary(): HasOne
    {
        return $this->hasOne(MovieImageSummary::class, 'movie_id', 'id');
    }

    public function titleVideos(): HasMany
    {
        return $this->hasMany(TitleVideo::class, 'movie_id', 'id')->orderBy('position');
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(TitleStatistic::class, 'movie_id', 'id');
    }

    public function boxOfficeRecord(): HasOne
    {
        return $this->hasOne(MovieBoxOffice::class, 'movie_id', 'id');
    }

    public function plotRecord(): HasOne
    {
        return $this->hasOne(MoviePlot::class, 'movie_id', 'id');
    }

    public function primaryImageRecord(): HasOne
    {
        return $this->hasOne(MoviePrimaryImage::class, 'movie_id', 'id');
    }

    public function originCountryRecords(): HasMany
    {
        return $this->hasMany(MovieOriginCountry::class, 'movie_id', 'id')->orderBy('position');
    }

    public function spokenLanguageRecords(): HasMany
    {
        return $this->hasMany(MovieSpokenLanguage::class, 'movie_id', 'id')->orderBy('position');
    }

    public function certificateRecords(): HasMany
    {
        return $this->hasMany(MovieCertificate::class, 'movie_id', 'id')->orderBy('position');
    }

    public function certificateSummary(): HasOne
    {
        return $this->hasOne(MovieCertificateSummary::class, 'movie_id', 'id');
    }

    public function episodeSummary(): HasOne
    {
        return $this->hasOne(MovieEpisodeSummary::class, 'movie_id', 'id');
    }

    public function companyCreditSummary(): HasOne
    {
        return $this->hasOne(MovieCompanyCreditSummary::class, 'movie_id', 'id');
    }

    public function movieCompanyCredits(): HasMany
    {
        return $this->hasMany(MovieCompanyCredit::class, 'movie_id', 'id')->orderBy('position');
    }

    public function movieDirectors(): HasMany
    {
        return $this->hasMany(MovieDirector::class, 'movie_id', 'id')->orderBy('position');
    }

    public function movieEpisodes(): HasMany
    {
        return $this->hasMany(MovieEpisode::class, 'movie_id', 'id')
            ->orderBy('season')
            ->orderBy('episode_number');
    }

    public function parentsGuideSections(): HasMany
    {
        return $this->hasMany(MovieParentsGuideSection::class, 'movie_id', 'id')->orderBy('position');
    }

    public function movieInterests(): HasMany
    {
        return $this->hasMany(MovieInterest::class, 'movie_id', 'id')->orderBy('position');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function imdbPayloadSection(string $key): ?array
    {
        return null;
    }

    public function typeLabel(): string
    {
        return $this->title_type->label();
    }

    public function typeIcon(): string
    {
        return $this->title_type->icon();
    }

    public function preferredPoster(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allImageAssets(),
            MediaKind::Poster,
            MediaKind::Backdrop,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    public function preferredBackdrop(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allImageAssets(),
            MediaKind::Backdrop,
            MediaKind::Poster,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    public function preferredDisplayImage(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allImageAssets(),
            MediaKind::Still,
            MediaKind::Backdrop,
            MediaKind::Poster,
            MediaKind::Gallery,
        );
    }

    /**
     * @return EloquentCollection<int, Genre>
     */
    public function previewGenres(int $limit = 3): EloquentCollection
    {
        if (! $this->relationLoaded('genres')) {
            return new EloquentCollection;
        }

        /** @var EloquentCollection<int, Genre> $genres */
        $genres = $this->genres->take($limit);

        return $genres;
    }

    /**
     * @return SupportCollection<int, Genre>
     */
    public function resolvedGenres(): SupportCollection
    {
        if (! $this->relationLoaded('genres')) {
            return collect();
        }

        return $this->genres
            ->filter(fn (mixed $genre): bool => $genre instanceof Genre && filled($genre->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, Interest>
     */
    public function resolvedInterests(): SupportCollection
    {
        if (! $this->relationLoaded('interests')) {
            return collect();
        }

        return $this->interests
            ->filter(fn (mixed $interest): bool => $interest instanceof Interest && filled($interest->imdb_id))
            ->unique('imdb_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestCategory>
     */
    public function resolvedInterestCategories(): SupportCollection
    {
        if (! $this->relationLoaded('interests')) {
            return collect();
        }

        return $this->interests
            ->flatMap(function (Interest $interest): SupportCollection {
                if (! $interest->relationLoaded('interestCategoryInterests')) {
                    return collect();
                }

                return $interest->interestCategoryInterests;
            })
            ->map(function (InterestCategoryInterest $interestCategoryInterest): ?InterestCategory {
                if (! $interestCategoryInterest->relationLoaded('interestCategory')) {
                    return null;
                }

                return $interestCategoryInterest->interestCategory;
            })
            ->filter(fn (mixed $interestCategory): bool => $interestCategory instanceof InterestCategory && filled($interestCategory->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestCategoryInterest>
     */
    public function resolvedInterestCategoryInterests(): SupportCollection
    {
        if (! $this->relationLoaded('interests')) {
            return collect();
        }

        return $this->interests
            ->flatMap(function (Interest $interest): SupportCollection {
                if (! $interest->relationLoaded('interestCategoryInterests')) {
                    return collect();
                }

                return $interest->interestCategoryInterests;
            })
            ->filter(fn (mixed $interestCategoryInterest): bool => $interestCategoryInterest instanceof InterestCategoryInterest)
            ->unique(fn (InterestCategoryInterest $interestCategoryInterest): string => $interestCategoryInterest->interest_category_id.'|'.$interestCategoryInterest->interest_imdb_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestPrimaryImage>
     */
    public function resolvedInterestPrimaryImages(): SupportCollection
    {
        if (! $this->relationLoaded('interests')) {
            return collect();
        }

        return $this->interests
            ->flatMap(function (Interest $interest): SupportCollection {
                if (! $interest->relationLoaded('interestPrimaryImages')) {
                    return collect();
                }

                return $interest->interestPrimaryImages;
            })
            ->filter(fn (mixed $interestPrimaryImage): bool => $interestPrimaryImage instanceof InterestPrimaryImage && filled($interestPrimaryImage->url))
            ->unique(fn (InterestPrimaryImage $interestPrimaryImage): string => $interestPrimaryImage->interest_imdb_id.'|'.$interestPrimaryImage->url)
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestSimilarInterest>
     */
    public function resolvedInterestSimilarInterests(): SupportCollection
    {
        if (! $this->relationLoaded('interests')) {
            return collect();
        }

        return $this->interests
            ->flatMap(function (Interest $interest): SupportCollection {
                if (! $interest->relationLoaded('interestSimilarInterests')) {
                    return collect();
                }

                return $interest->interestSimilarInterests;
            })
            ->filter(fn (mixed $interestSimilarInterest): bool => $interestSimilarInterest instanceof InterestSimilarInterest)
            ->unique(fn (InterestSimilarInterest $interestSimilarInterest): string => $interestSimilarInterest->interest_imdb_id.'|'.$interestSimilarInterest->similar_interest_imdb_id)
            ->values();
    }

    public function displayAverageRating(): ?float
    {
        if (! $this->relationLoaded('statistic')) {
            return null;
        }

        return $this->statistic?->average_rating;
    }

    public function displayRatingCount(): int
    {
        if (! $this->relationLoaded('statistic')) {
            return 0;
        }

        return $this->statistic?->rating_count ?? 0;
    }

    public function displayReviewCount(): int
    {
        return 0;
    }

    public function originCountryCode(): ?string
    {
        if (! filled($this->origin_country)) {
            return null;
        }

        return Str::of((string) $this->origin_country)
            ->before(',')
            ->trim()
            ->upper()
            ->toString();
    }

    public function originCountryLabel(): ?string
    {
        return Country::labelForCode($this->originCountryCode());
    }

    public function preferredVideo(): ?CatalogMediaAsset
    {
        if (! $this->relationLoaded('titleVideos')) {
            return null;
        }

        return CatalogMediaAsset::preferredFrom(
            $this->titleVideos,
            MediaKind::Trailer,
            MediaKind::Featurette,
            MediaKind::Clip,
        );
    }

    public function summaryText(): ?string
    {
        $summary = $this->getAttributeFromArray('tagline')
            ?: $this->getAttributeFromArray('synopsis')
            ?: $this->getAttributeFromArray('plot_outline');

        if (! filled($summary) && $this->relationLoaded('plotRecord')) {
            $summary = $this->plotRecord?->plot;
        }

        return filled($summary) ? (string) $summary : null;
    }

    /**
     * @return SupportCollection<int, AwardCategory>
     */
    public function resolvedAwardCategories(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominations')) {
            return collect();
        }

        return $this->awardNominations
            ->map(function (AwardNomination $awardNomination): ?AwardCategory {
                if (! $awardNomination->relationLoaded('awardCategory')) {
                    return null;
                }

                return $awardNomination->awardCategory;
            })
            ->filter(fn (mixed $awardCategory): bool => $awardCategory instanceof AwardCategory && filled($awardCategory->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, AwardEvent>
     */
    public function resolvedAwardEvents(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominations')) {
            return collect();
        }

        return $this->awardNominations
            ->map(function (AwardNomination $awardNomination): ?AwardEvent {
                if (! $awardNomination->relationLoaded('awardEvent')) {
                    return null;
                }

                return $awardNomination->awardEvent;
            })
            ->filter(fn (mixed $awardEvent): bool => $awardEvent instanceof AwardEvent && filled($awardEvent->name))
            ->unique('imdb_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, AwardNomination>
     */
    public function resolvedMovieAwardNominations(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominations')) {
            return collect();
        }

        return $this->awardNominations
            ->filter(fn (mixed $awardNomination): bool => $awardNomination instanceof AwardNomination && filled($awardNomination->id))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieAwardNominationNominee>
     */
    public function resolvedMovieAwardNominationNominees(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominations')) {
            return collect();
        }

        return $this->awardNominations
            ->flatMap(function (AwardNomination $awardNomination): SupportCollection {
                if (! $awardNomination->relationLoaded('movieAwardNominationNominees')) {
                    return collect();
                }

                return $awardNomination->movieAwardNominationNominees;
            })
            ->filter(fn (mixed $nominee): bool => $nominee instanceof MovieAwardNominationNominee)
            ->unique(fn (MovieAwardNominationNominee $nominee): string => $nominee->movie_award_nomination_id.'|'.$nominee->name_basic_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieAwardNominationTitle>
     */
    public function resolvedMovieAwardNominationTitles(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominations')) {
            return collect();
        }

        return $this->awardNominations
            ->flatMap(function (AwardNomination $awardNomination): SupportCollection {
                if (! $awardNomination->relationLoaded('movieAwardNominationTitles')) {
                    return collect();
                }

                return $awardNomination->movieAwardNominationTitles;
            })
            ->filter(fn (mixed $title): bool => $title instanceof MovieAwardNominationTitle)
            ->unique(fn (MovieAwardNominationTitle $title): string => $title->movie_award_nomination_id.'|'.$title->nominated_movie_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieAwardNominationSummary>
     */
    public function resolvedMovieAwardNominationSummaries(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominationSummary') || ! $this->awardNominationSummary instanceof MovieAwardNominationSummary) {
            return collect();
        }

        return collect([$this->awardNominationSummary])
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieAwardNominationSummary && filled($summary->movie_id))
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCertificate>
     */
    public function resolvedMovieCertificates(): SupportCollection
    {
        if (! $this->relationLoaded('certificateRecords')) {
            return collect();
        }

        return $this->certificateRecords
            ->filter(
                fn (mixed $certificate): bool => $certificate instanceof MovieCertificate
                    && (filled($certificate->certificate_rating_id) || filled($certificate->country_code) || filled($certificate->id)),
            )
            ->unique(function (MovieCertificate $certificate): string {
                $countryCode = strtoupper((string) $certificate->country_code);

                return ($certificate->certificate_rating_id ?? 'missing-rating').'|'.$countryCode;
            })
            ->sortBy(function (MovieCertificate $certificate): string {
                $rating = $certificate->relationLoaded('certificateRating') && filled($certificate->certificateRating?->name)
                    ? (string) $certificate->certificateRating?->name
                    : (string) ($certificate->certificate_rating_id ?? '');
                $country = $certificate->resolvedCountryLabel() ?? strtoupper((string) $certificate->country_code);
                $position = str_pad((string) ($certificate->position ?? 9999), 6, '0', STR_PAD_LEFT);
                $identifier = str_pad((string) ($certificate->id ?? 999999), 6, '0', STR_PAD_LEFT);

                return mb_strtolower($rating.'|'.$country.'|'.$position.'|'.$identifier);
            })
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCertificateSummary>
     */
    public function resolvedMovieCertificateSummaries(): SupportCollection
    {
        if (! $this->relationLoaded('certificateSummary') || ! $this->certificateSummary instanceof MovieCertificateSummary) {
            return collect();
        }

        return collect([$this->certificateSummary])
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieCertificateSummary && filled($summary->movie_id))
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieEpisodeSummary>
     */
    public function resolvedMovieEpisodeSummaries(): SupportCollection
    {
        if (! $this->relationLoaded('episodeSummary') || ! $this->episodeSummary instanceof MovieEpisodeSummary) {
            return collect();
        }

        return collect([$this->episodeSummary])
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieEpisodeSummary && filled($summary->movie_id))
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieImageSummary>
     */
    public function resolvedMovieImageSummaries(): SupportCollection
    {
        if (! $this->relationLoaded('imageSummary') || ! $this->imageSummary instanceof MovieImageSummary) {
            return collect();
        }

        return collect([$this->imageSummary])
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieImageSummary && filled($summary->movie_id))
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCompanyCreditSummary>
     */
    public function resolvedMovieCompanyCreditSummaries(): SupportCollection
    {
        if (! $this->relationLoaded('companyCreditSummary') || ! $this->companyCreditSummary instanceof MovieCompanyCreditSummary) {
            return collect();
        }

        return collect([$this->companyCreditSummary])
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieCompanyCreditSummary && filled($summary->movie_id))
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCertificateAttribute>
     */
    public function resolvedMovieCertificateAttributes(): SupportCollection
    {
        if (! $this->relationLoaded('certificateRecords')) {
            return collect();
        }

        return $this->certificateRecords
            ->flatMap(function (MovieCertificate $certificate): SupportCollection {
                if (! $certificate->relationLoaded('movieCertificateAttributes')) {
                    return collect();
                }

                return $certificate->movieCertificateAttributes;
            })
            ->filter(fn (mixed $movieCertificateAttribute): bool => $movieCertificateAttribute instanceof MovieCertificateAttribute)
            ->unique(fn (MovieCertificateAttribute $movieCertificateAttribute): string => $movieCertificateAttribute->movie_certificate_id.'|'.$movieCertificateAttribute->certificate_attribute_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, CertificateAttribute>
     */
    public function resolvedCertificateAttributes(): SupportCollection
    {
        if (! $this->relationLoaded('certificateRecords')) {
            return collect();
        }

        return $this->certificateRecords
            ->flatMap(function (MovieCertificate $certificate): SupportCollection {
                if (! $certificate->relationLoaded('movieCertificateAttributes')) {
                    return collect();
                }

                return $certificate->movieCertificateAttributes;
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
    }

    /**
     * @return SupportCollection<int, CertificateRating>
     */
    public function resolvedCertificateRatings(): SupportCollection
    {
        if (! $this->relationLoaded('certificateRecords')) {
            return collect();
        }

        return $this->certificateRecords
            ->map(function (MovieCertificate $certificate): ?CertificateRating {
                if (! $certificate->relationLoaded('certificateRating')) {
                    return null;
                }

                return $certificate->certificateRating;
            })
            ->filter(fn (mixed $certificateRating): bool => $certificateRating instanceof CertificateRating && filled($certificateRating->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCompanyCredit>
     */
    public function resolvedMovieCompanyCredits(): SupportCollection
    {
        if (! $this->relationLoaded('movieCompanyCredits')) {
            return collect();
        }

        return $this->movieCompanyCredits
            ->filter(fn (mixed $movieCompanyCredit): bool => $movieCompanyCredit instanceof MovieCompanyCredit && filled($movieCompanyCredit->id))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieDirector>
     */
    public function resolvedMovieDirectors(): SupportCollection
    {
        if (! $this->relationLoaded('movieDirectors')) {
            return collect();
        }

        return $this->movieDirectors
            ->filter(fn (mixed $movieDirector): bool => $movieDirector instanceof MovieDirector)
            ->unique(fn (MovieDirector $movieDirector): string => $movieDirector->movie_id.'|'.$movieDirector->name_basic_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieEpisode>
     */
    public function resolvedMovieEpisodes(): SupportCollection
    {
        if (! $this->relationLoaded('movieEpisodes')) {
            return collect();
        }

        return $this->movieEpisodes
            ->filter(fn (mixed $movieEpisode): bool => $movieEpisode instanceof MovieEpisode && filled($movieEpisode->episode_movie_id))
            ->unique('episode_movie_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieGenre>
     */
    public function resolvedMovieGenres(): SupportCollection
    {
        if (! $this->relationLoaded('movieGenres')) {
            return collect();
        }

        return $this->movieGenres
            ->filter(fn (mixed $movieGenre): bool => $movieGenre instanceof MovieGenre && filled($movieGenre->movie_id) && filled($movieGenre->genre_id))
            ->unique(fn (MovieGenre $movieGenre): string => $movieGenre->movie_id.'|'.$movieGenre->genre_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, Company>
     */
    public function resolvedCompanies(): SupportCollection
    {
        if (! $this->relationLoaded('movieCompanyCredits')) {
            return collect();
        }

        return $this->movieCompanyCredits
            ->map(function (MovieCompanyCredit $movieCompanyCredit): ?Company {
                if (! $movieCompanyCredit->relationLoaded('company')) {
                    return null;
                }

                return $movieCompanyCredit->company;
            })
            ->filter(fn (mixed $company): bool => $company instanceof Company && filled($company->name))
            ->unique('imdb_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCompanyCreditAttribute>
     */
    public function resolvedMovieCompanyCreditAttributes(): SupportCollection
    {
        if (! $this->relationLoaded('movieCompanyCredits')) {
            return collect();
        }

        return $this->movieCompanyCredits
            ->flatMap(function (MovieCompanyCredit $movieCompanyCredit): SupportCollection {
                if (! $movieCompanyCredit->relationLoaded('movieCompanyCreditAttributes')) {
                    return collect();
                }

                return $movieCompanyCredit->movieCompanyCreditAttributes;
            })
            ->filter(fn (mixed $movieCompanyCreditAttribute): bool => $movieCompanyCreditAttribute instanceof MovieCompanyCreditAttribute)
            ->unique(fn (MovieCompanyCreditAttribute $movieCompanyCreditAttribute): string => $movieCompanyCreditAttribute->movie_company_credit_id.'|'.$movieCompanyCreditAttribute->company_credit_attribute_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieCompanyCreditCountry>
     */
    public function resolvedMovieCompanyCreditCountries(): SupportCollection
    {
        if (! $this->relationLoaded('movieCompanyCredits')) {
            return collect();
        }

        return $this->movieCompanyCredits
            ->flatMap(function (MovieCompanyCredit $movieCompanyCredit): SupportCollection {
                if (! $movieCompanyCredit->relationLoaded('movieCompanyCreditCountries')) {
                    return collect();
                }

                return $movieCompanyCredit->movieCompanyCreditCountries;
            })
            ->filter(fn (mixed $movieCompanyCreditCountry): bool => $movieCompanyCreditCountry instanceof MovieCompanyCreditCountry)
            ->unique(fn (MovieCompanyCreditCountry $movieCompanyCreditCountry): string => $movieCompanyCreditCountry->movie_company_credit_id.'|'.$movieCompanyCreditCountry->country_code)
            ->values();
    }

    /**
     * @return SupportCollection<int, CompanyCreditAttribute>
     */
    public function resolvedCompanyCreditAttributes(): SupportCollection
    {
        if (! $this->relationLoaded('movieCompanyCredits')) {
            return collect();
        }

        return $this->movieCompanyCredits
            ->flatMap(function (MovieCompanyCredit $movieCompanyCredit): SupportCollection {
                if (! $movieCompanyCredit->relationLoaded('movieCompanyCreditAttributes')) {
                    return collect();
                }

                return $movieCompanyCredit->movieCompanyCreditAttributes;
            })
            ->map(function (MovieCompanyCreditAttribute $movieCompanyCreditAttribute): ?CompanyCreditAttribute {
                if (! $movieCompanyCreditAttribute->relationLoaded('companyCreditAttribute')) {
                    return null;
                }

                return $movieCompanyCreditAttribute->companyCreditAttribute;
            })
            ->filter(fn (mixed $companyCreditAttribute): bool => $companyCreditAttribute instanceof CompanyCreditAttribute && filled($companyCreditAttribute->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, CompanyCreditCategory>
     */
    public function resolvedCompanyCreditCategories(): SupportCollection
    {
        if (! $this->relationLoaded('movieCompanyCredits')) {
            return collect();
        }

        return $this->movieCompanyCredits
            ->map(function (MovieCompanyCredit $movieCompanyCredit): ?CompanyCreditCategory {
                if (! $movieCompanyCredit->relationLoaded('companyCreditCategory')) {
                    return null;
                }

                return $movieCompanyCredit->companyCreditCategory;
            })
            ->filter(fn (mixed $companyCreditCategory): bool => $companyCreditCategory instanceof CompanyCreditCategory && filled($companyCreditCategory->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, Country>
     */
    public function resolvedCountries(): SupportCollection
    {
        if (! $this->relationLoaded('countries')) {
            return collect();
        }

        return $this->countries
            ->filter(fn (mixed $country): bool => $country instanceof Country && filled($country->code))
            ->unique('code')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieBoxOffice>
     */
    public function resolvedMovieBoxOfficeRows(): SupportCollection
    {
        if (! $this->relationLoaded('boxOfficeRecord') || ! $this->boxOfficeRecord instanceof MovieBoxOffice) {
            return collect();
        }

        return collect([$this->boxOfficeRecord])
            ->filter(fn (mixed $boxOfficeRecord): bool => $boxOfficeRecord instanceof MovieBoxOffice && filled($boxOfficeRecord->movie_id))
            ->values();
    }

    /**
     * @return SupportCollection<int, Currency>
     */
    public function resolvedCurrencies(): SupportCollection
    {
        if (! $this->relationLoaded('boxOfficeRecord') || ! $this->boxOfficeRecord instanceof MovieBoxOffice) {
            return collect();
        }

        return collect([
            'productionBudget',
            'domesticGross',
            'openingWeekendGross',
            'worldwideGross',
        ])
            ->map(function (string $relation): ?Currency {
                if (! $this->boxOfficeRecord->relationLoaded($relation)) {
                    return null;
                }

                return $this->boxOfficeRecord->getRelation($relation);
            })
            ->filter(fn (mixed $currency): bool => $currency instanceof Currency && filled($currency->code))
            ->unique('code')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieAka>
     */
    public function resolvedMovieAkas(): SupportCollection
    {
        if (! $this->relationLoaded('movieAkas')) {
            return collect();
        }

        return $this->movieAkas
            ->filter(fn (mixed $movieAka): bool => $movieAka instanceof MovieAka && filled($movieAka->id))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieAkaAttribute>
     */
    public function resolvedMovieAkaAttributes(): SupportCollection
    {
        if (! $this->relationLoaded('movieAkas')) {
            return collect();
        }

        return $this->movieAkas
            ->flatMap(function (MovieAka $movieAka): SupportCollection {
                if (! $movieAka->relationLoaded('movieAkaAttributes')) {
                    return collect();
                }

                return $movieAka->movieAkaAttributes;
            })
            ->filter(fn (mixed $movieAkaAttribute): bool => $movieAkaAttribute instanceof MovieAkaAttribute)
            ->unique(fn (MovieAkaAttribute $movieAkaAttribute): string => $movieAkaAttribute->movie_aka_id.'|'.$movieAkaAttribute->aka_attribute_id)
            ->values();
    }

    /**
     * @return SupportCollection<int, AkaType>
     */
    public function resolvedAkaTypes(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<string, SupportCollection<int, CatalogMediaAsset>>
     */
    public function groupedMediaAssetsByKind(): SupportCollection
    {
        return $this->allMediaAssets()->groupBy(
            fn (CatalogMediaAsset $mediaAsset): string => $mediaAsset->kind->value,
        );
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name).'-'.($this->tconst ?: $this->imdb_id ?: $this->id);
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->primarytitle ?: $this->originaltitle ?: $this->tconst ?: 'Untitled');
    }

    public function getOriginalNameAttribute(): ?string
    {
        return filled($this->originaltitle) ? (string) $this->originaltitle : null;
    }

    /**
     * @return EloquentCollection<int, Genre>
     */
    public function getGenresAttribute(mixed $value): EloquentCollection
    {
        if ($this->relationLoaded('genres')) {
            $genres = $this->getRelation('genres');

            if ($genres instanceof EloquentCollection) {
                return $genres;
            }
        }

        return new EloquentCollection;
    }

    public function getTitleTypeAttribute(): CatalogTitleType
    {
        $remoteType = $this->getAttributeFromArray('titletype');

        return self::catalogTypeFromRemote(is_string($remoteType) ? $remoteType : null);
    }

    public function getReleaseYearAttribute(): ?int
    {
        return $this->startyear ? (int) $this->startyear : null;
    }

    public function getEndYearAttribute(): ?int
    {
        return $this->endyear ? (int) $this->endyear : null;
    }

    public function getRuntimeMinutesAttribute(): ?int
    {
        $runtimeMinutes = $this->getAttributeFromArray('runtimeminutes');

        return $runtimeMinutes ? (int) $runtimeMinutes : null;
    }

    public function getRuntimeSecondsAttribute(): ?int
    {
        $runtimeSeconds = $this->getAttributeFromArray('runtimeSeconds');

        return $runtimeSeconds ? (int) $runtimeSeconds : null;
    }

    public function runtimeMinutesLabel(): ?string
    {
        return self::formatMinutesLabel($this->runtime_minutes);
    }

    public function getReleaseDateAttribute(): mixed
    {
        return null;
    }

    public function getAgeRatingAttribute(): ?string
    {
        if (! $this->relationLoaded('certificateRecords')) {
            return null;
        }

        /** @var MovieCertificate|null $certificate */
        $certificate = $this->certificateRecords->first();

        return $certificate?->certificateRating?->name;
    }

    public function getPlotOutlineAttribute(): ?string
    {
        if ($this->relationLoaded('plotRecord')) {
            return $this->plotRecord?->plot;
        }

        return null;
    }

    public function getSynopsisAttribute(): ?string
    {
        return $this->plot_outline;
    }

    public function getTaglineAttribute(): ?string
    {
        return null;
    }

    public function getOriginCountryAttribute(): ?string
    {
        if ($this->relationLoaded('countries')) {
            return $this->countries->first()?->code;
        }

        if (! $this->relationLoaded('originCountryRecords')) {
            return null;
        }

        /** @var MovieOriginCountry|null $country */
        $country = $this->originCountryRecords->first();

        return $country?->country_code;
    }

    public function getOriginalLanguageAttribute(): ?string
    {
        if ($this->relationLoaded('languages')) {
            return $this->languages->first()?->code;
        }

        if (! $this->relationLoaded('spokenLanguageRecords')) {
            return null;
        }

        /** @var MovieSpokenLanguage|null $language */
        $language = $this->spokenLanguageRecords->first();

        return $language?->language_code;
    }

    public function getPopularityRankAttribute(): ?int
    {
        $value = $this->getAttributeFromArray('popularity_rank');

        return $value !== null ? (int) $value : null;
    }

    public function getMetaTitleAttribute(): string
    {
        return $this->release_year ? $this->name.' ('.$this->release_year.')' : $this->name;
    }

    public function getMetaDescriptionAttribute(): string
    {
        return $this->plot_outline ?: 'Browse cast, awards, genres, ratings, and release details for '.$this->name.'.';
    }

    public function getSearchKeywordsAttribute(): ?string
    {
        return null;
    }

    public function getIsPublishedAttribute(): bool
    {
        return (int) ($this->isadult ?? 0) === 0;
    }

    public function getUpdatedAtAttribute(): mixed
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public static function remoteTypesForCatalogType(CatalogTitleType $type): array
    {
        return match ($type) {
            CatalogTitleType::Movie => ['movie', 'tvMovie', 'tvmovie', 'video'],
            CatalogTitleType::Series => ['tvSeries', 'tvseries', 'tvPilot', 'tvpilot', 'tvShortSeries', 'tvshortseries'],
            CatalogTitleType::MiniSeries => ['tvMiniSeries', 'tvminiseries'],
            CatalogTitleType::Documentary => ['documentary'],
            CatalogTitleType::Special => ['tvSpecial', 'tvspecial', 'special'],
            CatalogTitleType::Short => ['short'],
            CatalogTitleType::Episode => ['tvEpisode', 'tvepisode'],
        };
    }

    private static function catalogTypeFromRemote(?string $remoteType): CatalogTitleType
    {
        return match (Str::lower((string) $remoteType)) {
            'tvseries', 'tvpilot', 'tvshortseries' => CatalogTitleType::Series,
            'tvminiseries' => CatalogTitleType::MiniSeries,
            'tvepisode' => CatalogTitleType::Episode,
            'short' => CatalogTitleType::Short,
            'documentary' => CatalogTitleType::Documentary,
            'tvspecial', 'special' => CatalogTitleType::Special,
            default => CatalogTitleType::Movie,
        };
    }

    /**
     * @return SupportCollection<int, CatalogMediaAsset>
     */
    private function allImageAssets(): SupportCollection
    {
        $assets = collect();

        if ($this->relationLoaded('titleImages')) {
            $assets = $assets->concat($this->titleImages);
        }

        if ($primaryImage = $this->primaryImageAsset()) {
            $assets->prepend($primaryImage);
        }

        return $assets
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->unique('url')
            ->values();
    }

    /**
     * @return SupportCollection<int, CatalogMediaAsset>
     */
    private function allMediaAssets(): SupportCollection
    {
        $assets = $this->allImageAssets();

        if ($this->relationLoaded('titleVideos')) {
            $assets = $assets->concat($this->titleVideos);
        }

        return $assets
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->values();
    }

    private function primaryImageAsset(): ?CatalogMediaAsset
    {
        if (! $this->relationLoaded('primaryImageRecord') || ! $this->primaryImageRecord?->url) {
            return null;
        }

        return CatalogMediaAsset::fromCatalog([
            'kind' => $this->primaryImageRecord->type === 'poster' ? MediaKind::Poster : MediaKind::Gallery,
            'url' => $this->primaryImageRecord->url,
            'alt_text' => $this->name,
            'width' => $this->primaryImageRecord->width,
            'height' => $this->primaryImageRecord->height,
            'position' => 0,
            'is_primary' => true,
        ]);
    }

    private static function statisticColumnSubquery(string $column): Builder
    {
        return TitleStatistic::query()
            ->select($column)
            ->whereColumn('movie_ratings.movie_id', 'movies.id')
            ->limit(1);
    }
}
