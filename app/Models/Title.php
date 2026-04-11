<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\TitleType as CatalogTitleType;
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

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id', 'id', 'id')
            ->orderBy('movie_genres.position');
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

    public function titleAkas(): HasMany
    {
        return $this->hasMany(TitleAka::class, 'titleid', 'tconst')->orderBy('ordering');
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class, 'movie_id', 'id')
            ->orderByDesc('award_year')
            ->orderBy('position');
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

    public function movieCompanyCredits(): HasMany
    {
        return $this->hasMany(MovieCompanyCredit::class, 'movie_id', 'id')->orderBy('position');
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

    public function displayAverageRating(): ?float
    {
        if (! $this->relationLoaded('statistic') || ! $this->statistic?->average_rating) {
            return null;
        }

        return (float) $this->statistic->average_rating;
    }

    public function displayRatingCount(): int
    {
        if (! $this->relationLoaded('statistic') || ! $this->statistic) {
            return 0;
        }

        return (int) $this->statistic->rating_count;
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
        $summary = $this->tagline ?: $this->synopsis ?: $this->plot_outline;

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
     * @return SupportCollection<int, AkaType>
     */
    public function resolvedAkaTypes(): SupportCollection
    {
        if (! $this->relationLoaded('titleAkas')) {
            return collect();
        }

        return $this->titleAkas
            ->flatMap(function (TitleAka $titleAka): SupportCollection {
                if (! $titleAka->relationLoaded('titleAkaTypes')) {
                    return collect();
                }

                return $titleAka->titleAkaTypes;
            })
            ->map(function (TitleAkaType $titleAkaType): ?AkaType {
                if (! $titleAkaType->relationLoaded('akaType')) {
                    return null;
                }

                return $titleAkaType->akaType;
            })
            ->filter(fn (mixed $akaType): bool => $akaType instanceof AkaType && filled($akaType->name))
            ->unique('id')
            ->values();
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
            CatalogTitleType::Movie => ['movie', 'tvMovie', 'video'],
            CatalogTitleType::Series => ['tvSeries'],
            CatalogTitleType::MiniSeries => ['tvMiniSeries'],
            CatalogTitleType::Documentary => ['documentary'],
            CatalogTitleType::Special => ['tvSpecial', 'special'],
            CatalogTitleType::Short => ['short'],
            CatalogTitleType::Episode => ['tvEpisode'],
        };
    }

    private static function catalogTypeFromRemote(?string $remoteType): CatalogTitleType
    {
        return match ($remoteType) {
            'tvSeries' => CatalogTitleType::Series,
            'tvMiniSeries' => CatalogTitleType::MiniSeries,
            'tvEpisode' => CatalogTitleType::Episode,
            'short' => CatalogTitleType::Short,
            'documentary' => CatalogTitleType::Documentary,
            'tvSpecial', 'special' => CatalogTitleType::Special,
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
