<?php

namespace App\Models;

use App\Enums\CountryCode;
use App\Enums\LanguageCode;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\Concerns\FormatsRuntimeLabels;
use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class Title extends Model
{
    use FormatsRuntimeLabels;

    /** @use HasFactory<TitleFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'original_name',
        'slug',
        'sort_title',
        'title_type',
        'release_year',
        'end_year',
        'release_date',
        'runtime_minutes',
        'age_rating',
        'plot_outline',
        'synopsis',
        'tagline',
        'origin_country',
        'original_language',
        'popularity_rank',
        'canonical_title_id',
        'meta_title',
        'meta_description',
        'search_keywords',
        'is_published',
        'imdb_id',
        'imdb_type',
        'runtime_seconds',
        'imdb_genres',
        'imdb_interests',
        'imdb_origin_countries',
        'imdb_spoken_languages',
        'imdb_payload',
        'tconst',
        'titletype',
        'primarytitle',
        'originaltitle',
        'startyear',
        'endyear',
        'runtimeminutes',
        'runtimeSeconds',
        'isadult',
    ];

    protected function casts(): array
    {
        return [
            'title_type' => TitleType::class,
            'release_year' => 'integer',
            'end_year' => 'integer',
            'release_date' => 'date',
            'runtime_minutes' => 'integer',
            'popularity_rank' => 'integer',
            'canonical_title_id' => 'integer',
            'is_published' => 'boolean',
            'runtime_seconds' => 'integer',
            'imdb_genres' => 'array',
            'imdb_interests' => 'array',
            'imdb_origin_countries' => 'array',
            'imdb_spoken_languages' => 'array',
            'imdb_payload' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $title): void {
            $title->slug = $title->slug ?: Str::slug($title->name ?: 'untitled');
            $title->sort_title = $title->sort_title ?: $title->name;
            $title->imdb_id = $title->imdb_id ?: null;
            $title->imdb_type = $title->imdb_type ?: self::remoteTypesForCatalogType($title->title_type)[0] ?? null;
        });
    }

    /**
     * @return list<string>
     */
    public static function catalogCardColumns(): array
    {
        return [
            'titles.id',
            'titles.imdb_id',
            'titles.name',
            'titles.original_name',
            'titles.slug',
            'titles.title_type',
            'titles.release_year',
            'titles.end_year',
            'titles.release_date',
            'titles.runtime_minutes',
            'titles.age_rating',
            'titles.plot_outline',
            'titles.synopsis',
            'titles.tagline',
            'titles.origin_country',
            'titles.original_language',
            'titles.popularity_rank',
            'titles.is_published',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogHeroRelations(): array
    {
        return [
            'statistic:id,title_id,rating_count,average_rating,review_count,watchlist_count,episodes_count,awards_nominated_count,awards_won_count,metacritic_score,metacritic_review_count',
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'width',
                    'height',
                    'duration_seconds',
                    'provider',
                    'provider_key',
                    'language',
                    'metadata',
                    'is_primary',
                    'position',
                    'published_at',
                ])
                ->ordered(),
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogCardRelations(): array
    {
        return [
            ...self::catalogHeroRelations(),
            'genres:id,name,slug',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogListRelations(): array
    {
        return self::catalogCardRelations();
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogMediaRelations(): array
    {
        return self::catalogCardRelations();
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogBoxOfficeRelations(): array
    {
        return self::catalogHeroRelations();
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogDetailRelations(): array
    {
        return [
            ...self::catalogCardRelations(),
            'credits' => fn ($query) => $query
                ->select([
                    'id',
                    'title_id',
                    'person_id',
                    'department',
                    'job',
                    'character_name',
                    'billing_order',
                    'is_principal',
                    'person_profession_id',
                    'episode_id',
                    'credited_as',
                    'imdb_source_group',
                ])
                ->ordered()
                ->with([
                    'person:id,name,slug,short_biography,known_for_department,nationality,popularity_rank',
                    'person.mediaAssets' => fn ($mediaQuery) => $mediaQuery
                        ->select([
                            'id',
                            'mediable_type',
                            'mediable_id',
                            'kind',
                            'url',
                            'alt_text',
                            'caption',
                            'width',
                            'height',
                            'duration_seconds',
                            'metadata',
                            'is_primary',
                            'position',
                            'published_at',
                        ])
                        ->ordered(),
                    'profession:id,person_id,department,profession,is_primary,sort_order',
                    'episode:id,title_id,series_id,season_id,season_number,episode_number,absolute_number,production_code,aired_at',
                    'episode.title:id,name,slug,title_type,is_published',
                ]),
            'seasons' => fn ($query) => $query
                ->select([
                    'id',
                    'series_id',
                    'name',
                    'slug',
                    'season_number',
                    'summary',
                    'release_year',
                    'meta_title',
                    'meta_description',
                ])
                ->withCount('episodes')
                ->orderBy('season_number'),
            'seriesEpisodes' => fn ($query) => $query
                ->select([
                    'id',
                    'title_id',
                    'series_id',
                    'season_id',
                    'season_number',
                    'episode_number',
                    'absolute_number',
                    'production_code',
                    'aired_at',
                ])
                ->with([
                    'title:id,name,slug,title_type,is_published,release_year,runtime_minutes,age_rating,origin_country,original_language,plot_outline,synopsis',
                    'season:id,series_id,name,slug,season_number',
                ])
                ->orderBy('season_number')
                ->orderBy('episode_number')
                ->orderBy('id'),
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
        if ($field !== null) {
            return $query->where($field, $value);
        }

        return $query->where(function (Builder $titleQuery) use ($value): void {
            $titleQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $titleQuery->orWhereKey((int) $value);
            }

            if (is_string($value) && $value !== '') {
                $titleQuery
                    ->orWhere('imdb_id', $value)
                    ->orWhere('slug', Str::slug($value));
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('titles.is_published', true);
    }

    public function scopeWithoutEpisodes(Builder $query): Builder
    {
        return $query->where('titles.title_type', '!=', TitleType::Episode->value);
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
            return $query->where('titles.imdb_id', $search);
        }

        return $query->where(function (Builder $titleQuery) use ($search): void {
            $titleQuery
                ->where('titles.name', 'like', '%'.$search.'%')
                ->orWhere('titles.original_name', 'like', '%'.$search.'%')
                ->orWhere('titles.imdb_id', 'like', '%'.$search.'%')
                ->orWhere('titles.plot_outline', 'like', '%'.$search.'%')
                ->orWhere('titles.synopsis', 'like', '%'.$search.'%')
                ->orWhere('titles.tagline', 'like', '%'.$search.'%')
                ->orWhere('titles.search_keywords', 'like', '%'.$search.'%');
        });
    }

    public function scopeMatchingDiscoverySearch(Builder $query, string $search): Builder
    {
        $search = Str::of($search)->squish()->toString();

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^tt\d+$/i', $search) === 1) {
            return $query->where('titles.imdb_id', $search);
        }

        $prefixSearch = $search.'%';

        return $query->where(function (Builder $titleQuery) use ($search, $prefixSearch): void {
            $titleQuery
                ->where('titles.name', $search)
                ->orWhere('titles.original_name', $search)
                ->orWhere('titles.name', 'like', $prefixSearch)
                ->orWhere('titles.original_name', 'like', $prefixSearch);
        });
    }

    public function scopeForType(Builder $query, TitleType $type): Builder
    {
        return $query->where('titles.title_type', $type->value);
    }

    public function scopeRatedAtLeast(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query->whereHas(
            'statistic',
            fn (Builder $statisticQuery) => $statisticQuery->where('rating_count', '>=', $minimumVotes),
        );
    }

    public function scopeOrderByTopRated(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query
            ->ratedAtLeast($minimumVotes)
            ->addSelect([
                'average_rating_sort' => TitleStatistic::query()
                    ->select('average_rating')
                    ->whereColumn('title_statistics.title_id', 'titles.id')
                    ->limit(1),
                'rating_count_sort' => TitleStatistic::query()
                    ->select('rating_count')
                    ->whereColumn('title_statistics.title_id', 'titles.id')
                    ->limit(1),
            ])
            ->orderByDesc('average_rating_sort')
            ->orderByDesc('rating_count_sort')
            ->orderBy('titles.sort_title')
            ->orderBy('titles.name');
    }

    public function scopeOrderByTrending(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'rating_count_sort' => TitleStatistic::query()
                    ->select('rating_count')
                    ->whereColumn('title_statistics.title_id', 'titles.id')
                    ->limit(1),
            ])
            ->orderByDesc('rating_count_sort')
            ->orderByDesc('titles.release_year')
            ->orderBy('titles.sort_title')
            ->orderBy('titles.name');
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
            is_string($genre) && preg_match('/-g(?P<id>\d+)$/', $genre, $matches) === 1 => (int) $matches['id'],
            is_string($genre) && ctype_digit($genre) => (int) $genre,
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
            $query->where('titles.release_year', '>=', $yearFrom);
        }

        if ($yearTo !== null) {
            $query->where('titles.release_year', '<=', $yearTo);
        }

        return $query;
    }

    public function scopeProducedInCountry(Builder $query, string $countryCode): Builder
    {
        $countryCode = strtoupper($countryCode);

        return $query->where(function (Builder $countryQuery) use ($countryCode): void {
            $countryQuery
                ->where('titles.origin_country', $countryCode)
                ->orWhereJsonContains('titles.imdb_origin_countries', $countryCode);
        });
    }

    public function scopeSpokenInLanguage(Builder $query, string $languageCode): Builder
    {
        $normalizedCode = Str::lower($languageCode);
        $upperCode = Str::upper($languageCode);

        return $query->where(function (Builder $languageQuery) use ($normalizedCode, $upperCode): void {
            $languageQuery
                ->where('titles.original_language', $normalizedCode)
                ->orWhere('titles.original_language', $upperCode)
                ->orWhereJsonContains('titles.imdb_spoken_languages', $normalizedCode);
        });
    }

    public function scopeWithinRuntimeBucket(Builder $query, string $runtime): Builder
    {
        return match ($runtime) {
            'under-30' => $query->whereNotNull('titles.runtime_minutes')->where('titles.runtime_minutes', '<', 30),
            '30-60' => $query->whereBetween('titles.runtime_minutes', [30, 60]),
            '60-90' => $query->whereBetween('titles.runtime_minutes', [60, 90]),
            '90-120' => $query->whereBetween('titles.runtime_minutes', [90, 120]),
            '120-plus' => $query->where('titles.runtime_minutes', '>=', 120),
            default => $query,
        };
    }

    public function scopeForInterestCategory(Builder $query, InterestCategory|int $interestCategory): Builder
    {
        return $query;
    }

    public function scopeWithMatchedInterestCount(Builder $query, InterestCategory|int $interestCategory): Builder
    {
        return $query->addSelect(['matched_interest_count' => 0]);
    }

    public function canonicalTitle(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_title_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)
            ->withTimestamps()
            ->orderBy('genres.name');
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(TitleStatistic::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class)->ordered();
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class, 'series_id')->orderBy('season_number')->orderBy('id');
    }

    public function seriesEpisodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'series_id')
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->orderBy('id');
    }

    public function episodeMeta(): HasOne
    {
        return $this->hasOne(Episode::class, 'title_id');
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable')->ordered();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }

    public function titleTranslations(): HasMany
    {
        return $this->hasMany(TitleTranslation::class);
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
            $this->allMediaAssets(),
            MediaKind::Poster,
            MediaKind::Backdrop,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    public function preferredBackdrop(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allMediaAssets(),
            MediaKind::Backdrop,
            MediaKind::Poster,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    public function preferredDisplayImage(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allMediaAssets(),
            MediaKind::Still,
            MediaKind::Backdrop,
            MediaKind::Poster,
            MediaKind::Gallery,
        );
    }

    public function preferredVideo(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allMediaAssets(),
            MediaKind::Trailer,
            MediaKind::Featurette,
            MediaKind::Clip,
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

    public function displayAverageRating(): ?float
    {
        if (! $this->relationLoaded('statistic')) {
            return null;
        }

        return $this->statistic?->average_rating !== null
            ? (float) $this->statistic->average_rating
            : null;
    }

    public function displayRatingCount(): int
    {
        if (! $this->relationLoaded('statistic')) {
            return 0;
        }

        return (int) ($this->statistic?->rating_count ?? 0);
    }

    public function displayReviewCount(): int
    {
        if ($this->relationLoaded('statistic')) {
            return (int) ($this->statistic?->review_count ?? 0);
        }

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
        return CountryCode::labelFor($this->originCountryCode());
    }

    public function originalLanguageLabel(): ?string
    {
        return LanguageCode::labelFor($this->original_language);
    }

    public function summaryText(): ?string
    {
        $summary = $this->tagline ?: $this->synopsis ?: $this->plot_outline;

        return filled($summary) ? (string) $summary : null;
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

    public function runtimeMinutesLabel(): ?string
    {
        return self::formatMinutesLabel($this->runtime_minutes);
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAkas(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAkaAttributes(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedAkaTypes(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedAwardCategories(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedAwardEvents(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, AwardNomination>
     */
    public function resolvedMovieAwardNominations(): SupportCollection
    {
        if (! $this->relationLoaded('awardNominations')) {
            return collect();
        }

        return $this->awardNominations->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAwardNominationNominees(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAwardNominationTitles(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAwardNominationSummaries(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCertificates(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCertificateAttributes(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCertificateSummaries(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCredits(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCreditAttributes(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCreditCountries(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCreditSummaries(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, Credit>
     */
    public function resolvedMovieDirectors(): SupportCollection
    {
        if (! $this->relationLoaded('credits')) {
            return collect();
        }

        return $this->credits
            ->filter(fn (Credit $credit): bool => $credit->department === 'Directing')
            ->values();
    }

    /**
     * @return SupportCollection<int, Episode>
     */
    public function resolvedMovieEpisodes(): SupportCollection
    {
        if (! $this->relationLoaded('seriesEpisodes')) {
            return collect();
        }

        return $this->seriesEpisodes->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieEpisodeSummaries(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, Genre>
     */
    public function resolvedMovieGenres(): SupportCollection
    {
        return $this->resolvedGenres();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieImageSummaries(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, array{code: string, label: string}>
     */
    public function resolvedCountryItems(): SupportCollection
    {
        return collect([$this->originCountryCode()])
            ->filter()
            ->map(fn (string $code): array => [
                'code' => $code,
                'label' => CountryCode::labelFor($code) ?? $code,
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, array{code: string, label: string}>
     */
    public function resolvedLanguageItems(): SupportCollection
    {
        return collect([$this->original_language])
            ->filter()
            ->map(function (string $code): array {
                $normalizedCode = Str::lower($code);

                return [
                    'code' => $normalizedCode,
                    'label' => LanguageCode::labelFor($normalizedCode) ?? $normalizedCode,
                ];
            })
            ->values();
    }

    /**
     * @return list<string>
     */
    public static function remoteTypesForCatalogType(TitleType $type): array
    {
        return match ($type) {
            TitleType::Movie => ['movie'],
            TitleType::Series => ['series', 'tvseries', 'tvpilot', 'tvshortseries'],
            TitleType::MiniSeries => ['mini-series', 'tvminiseries'],
            TitleType::Documentary => ['documentary'],
            TitleType::Special => ['special', 'tvspecial'],
            TitleType::Short => ['short'],
            TitleType::Episode => ['episode', 'tvepisode'],
        };
    }

    public static function catalogTypeFromRemote(?string $remoteType): TitleType
    {
        $normalized = Str::of((string) $remoteType)
            ->replace('_', '-')
            ->trim()
            ->lower()
            ->toString();

        return match ($normalized) {
            'tvseries', 'tvpilot', 'tvshortseries', 'series' => TitleType::Series,
            'tvminiseries', 'mini-series', 'miniseries' => TitleType::MiniSeries,
            'documentary' => TitleType::Documentary,
            'special', 'tvspecial' => TitleType::Special,
            'short' => TitleType::Short,
            'episode', 'tvepisode' => TitleType::Episode,
            default => TitleType::Movie,
        };
    }

    public function getSlugAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        return Str::slug($this->name).'-'.($this->imdb_id ?: $this->getKey());
    }

    public function getNameAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        return (string) ($this->attributes['primarytitle'] ?? $this->attributes['originaltitle'] ?? $this->attributes['imdb_id'] ?? 'Untitled');
    }

    public function getOriginalNameAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $fallback = $this->attributes['originaltitle'] ?? null;

        return filled($fallback) ? (string) $fallback : null;
    }

    public function getTitleTypeAttribute(mixed $value): TitleType
    {
        if ($value instanceof TitleType) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return TitleType::tryFrom($value) ?? self::catalogTypeFromRemote($value);
        }

        return self::catalogTypeFromRemote($this->attributes['titletype'] ?? $this->attributes['imdb_type'] ?? null);
    }

    public function getReleaseYearAttribute(mixed $value): ?int
    {
        $year = $value ?? $this->attributes['startyear'] ?? null;

        return $year !== null ? (int) $year : null;
    }

    protected function endYear(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?int => ($value ?? $this->attributes['endyear'] ?? null) !== null
                ? (int) ($value ?? $this->attributes['endyear'])
                : null,
        );
    }

    protected function runtimeMinutes(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?int => ($value ?? $this->attributes['runtimeminutes'] ?? null) !== null
                ? (int) ($value ?? $this->attributes['runtimeminutes'])
                : null,
        );
    }

    public function getRuntimeSecondsAttribute(mixed $value): ?int
    {
        $runtime = $value ?? $this->attributes['runtimeSeconds'] ?? null;

        return $runtime !== null ? (int) $runtime : null;
    }

    public function getTconstAttribute(): ?string
    {
        return $this->imdb_id;
    }

    public function getPrimarytitleAttribute(): string
    {
        return $this->name;
    }

    public function getOriginaltitleAttribute(): ?string
    {
        return $this->original_name;
    }

    public function getStartyearAttribute(): ?int
    {
        $year = $this->attributes['release_year'] ?? null;

        return $year !== null ? (int) $year : null;
    }

    public function getEndyearAttribute(): ?int
    {
        $year = $this->attributes['end_year'] ?? null;

        return $year !== null ? (int) $year : null;
    }

    public function getRuntimeminutesAttribute(): ?int
    {
        $minutes = $this->attributes['runtime_minutes'] ?? null;

        return $minutes !== null ? (int) $minutes : null;
    }

    public function getRuntimeSecondsAttributeAlias(): ?int
    {
        return $this->runtime_seconds;
    }

    public function getRuntimeSecondsLegacyAttribute(): ?int
    {
        return $this->runtime_seconds;
    }

    public function getRuntimeSecondsRawAttribute(): ?int
    {
        return $this->runtime_seconds;
    }

    public function setTconstAttribute(?string $value): void
    {
        $this->attributes['imdb_id'] = $value;
    }

    public function setPrimarytitleAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function setOriginaltitleAttribute(?string $value): void
    {
        $this->attributes['original_name'] = $value;
    }

    public function setTitleTypeAttribute(mixed $value): void
    {
        if (! filled($value)) {
            $this->attributes['title_type'] = null;
            $this->attributes['imdb_type'] = null;

            return;
        }

        $catalogType = $value instanceof TitleType
            ? $value
            : (is_string($value) && $value !== ''
                ? (TitleType::tryFrom($value) ?? self::catalogTypeFromRemote($value))
                : null);

        $this->attributes['title_type'] = $catalogType?->value;
        $this->attributes['imdb_type'] = $catalogType !== null
            ? (self::remoteTypesForCatalogType($catalogType)[0] ?? null)
            : null;
    }

    public function setStartyearAttribute(mixed $value): void
    {
        $this->attributes['release_year'] = $value;
    }

    public function setEndyearAttribute(mixed $value): void
    {
        $this->attributes['end_year'] = $value;
    }

    public function setRuntimeminutesAttribute(mixed $value): void
    {
        $this->attributes['runtime_minutes'] = $value;
    }

    public function setRuntimeSecondsAttribute(mixed $value): void
    {
        $this->attributes['runtime_seconds'] = $value;
    }

    public function getAttribute($key): mixed
    {
        if ($key === 'titletype') {
            $titleType = parent::getAttribute('title_type');

            return $this->imdb_type
                ?: (($titleType instanceof TitleType)
                    ? (self::remoteTypesForCatalogType($titleType)[0] ?? null)
                    : null);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value): static
    {
        if ($key === 'titletype') {
            $this->setTitleTypeAttribute($value);

            return $this;
        }

        parent::setAttribute($key, $value);

        return $this;
    }

    /**
     * @return SupportCollection<int, CatalogMediaAsset>
     */
    private function allMediaAssets(): SupportCollection
    {
        if (! $this->relationLoaded('mediaAssets')) {
            return collect();
        }

        return $this->mediaAssets
            ->filter(fn (mixed $asset): bool => $asset instanceof MediaAsset)
            ->map(function (MediaAsset $mediaAsset): CatalogMediaAsset {
                return CatalogMediaAsset::fromCatalog([
                    'kind' => $mediaAsset->kind,
                    'url' => $mediaAsset->url,
                    'alt_text' => $mediaAsset->alt_text,
                    'caption' => $mediaAsset->caption,
                    'width' => $mediaAsset->width,
                    'height' => $mediaAsset->height,
                    'duration_seconds' => $mediaAsset->duration_seconds,
                    'is_primary' => $mediaAsset->is_primary,
                    'position' => $mediaAsset->position,
                    'metadata' => $mediaAsset->metadata,
                ]);
            })
            ->values();
    }
}
