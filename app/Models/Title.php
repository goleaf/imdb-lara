<?php

namespace App\Models;

use App\Enums\CountryCode;
use App\Enums\LanguageCode;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\Concerns\FormatsRuntimeLabels;
use Database\Factories\TitleFactory;
use Illuminate\Container\Container;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Schema;
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
            if (static::usesCatalogOnlySchema()) {
                $title->normalizeCatalogOnlyAttributesForPersistence();

                return;
            }

            $title->slug = $title->slug ?: Str::slug($title->name ?: 'untitled');
            $title->sort_title = $title->sort_title ?: $title->name;
            $title->imdb_id = $title->imdb_id ?: null;
            $title->imdb_type = $title->imdb_type ?: self::remoteTypesForCatalogType($title->title_type)[0] ?? null;
        });
    }

    public function newQuery(): Builder
    {
        $query = parent::newQuery();

        if (static::usesCatalogOnlySchema()) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    public function getTable(): string
    {
        return static::usesCatalogOnlySchema() ? 'movies' : parent::getTable();
    }

    public function getConnectionName(): ?string
    {
        return static::usesCatalogOnlySchema() ? 'imdb_mysql' : parent::getConnectionName();
    }

    public function usesTimestamps(): bool
    {
        return static::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
    }

    public static function usesCatalogOnlySchema(): bool
    {
        $container = Container::getInstance();

        if (! $container instanceof Container || ! $container->bound('config')) {
            return false;
        }

        return (bool) $container->make('config')->get('screenbase.catalog_only', false);
    }

    public static function catalogTable(): string
    {
        return static::usesCatalogOnlySchema() ? 'movies' : 'titles';
    }

    public static function catalogColumn(string $localColumn): string
    {
        if (! static::usesCatalogOnlySchema()) {
            return 'titles.'.$localColumn;
        }

        return match ($localColumn) {
            'name' => 'movies.primarytitle',
            'original_name' => 'movies.originaltitle',
            'title_type' => 'movies.titletype',
            'release_year' => 'movies.startyear',
            'end_year' => 'movies.endyear',
            'runtime_minutes' => 'movies.runtimeminutes',
            'runtime_seconds' => 'movies.runtimeSeconds',
            'sort_title' => 'movies.primarytitle',
            default => 'movies.'.$localColumn,
        };
    }

    /**
     * @return list<string>
     */
    public static function catalogCardColumns(): array
    {
        if (static::usesCatalogOnlySchema()) {
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
        if (static::usesCatalogOnlySchema()) {
            return [
                'statistic:movie_id,aggregate_rating,vote_count',
                'moviePrimaryImage:movie_id,url,width,height,type',
                'plotRecord:movie_id,plot',
            ];
        }

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
        if (static::usesCatalogOnlySchema()) {
            return [
                ...self::catalogHeroRelations(),
                'genres:id,name',
            ];
        }

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
        if (static::usesCatalogOnlySchema()) {
            return [
                ...self::catalogCardRelations(),
                'movieImages:id,movie_id,position,url,width,height,type',
                'titleVideos' => fn ($query) => $query
                    ->select([
                        'imdb_id',
                        'movie_id',
                        'video_type_id',
                        'name',
                        'description',
                        'width',
                        'height',
                        'runtime_seconds',
                        'position',
                    ])
                    ->with('videoType:id,name'),
            ];
        }

        return self::catalogCardRelations();
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogBoxOfficeRelations(): array
    {
        return [
            ...self::catalogHeroRelations(),
            'boxOfficeRecord',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function catalogDetailRelations(): array
    {
        if (static::usesCatalogOnlySchema()) {
            $relations = [
                ...self::catalogCardRelations(),
                'movieAkas' => fn ($query) => $query
                    ->selectArchiveColumns()
                    ->withTitleDetailRelations()
                    ->archiveOrdered(),
                'credits' => fn ($query) => $query
                    ->select(Credit::projectedColumns())
                    ->ordered()
                    ->with([
                        ...Credit::projectedRelations(),
                        'person' => fn ($personQuery) => $personQuery
                            ->select(Person::directoryColumns())
                            ->with(Person::directoryRelations()),
                    ]),
            ];

            if (static::catalogSeriesRelationsAvailable()) {
                $relations['seasons'] = fn ($query) => $query
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
                    ->orderBy('season_number');
                $relations['seriesEpisodes'] = fn ($query) => $query
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
                        'title' => fn ($titleQuery) => $titleQuery
                            ->selectCatalogCardColumns()
                            ->withCatalogHeroRelations(),
                        'season:id,series_id,name,slug,season_number',
                    ])
                    ->orderBy('season_number')
                    ->orderBy('episode_number')
                    ->orderBy('id');
            }

            return $relations;
        }

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
        if ($field !== null && (! static::usesCatalogOnlySchema() || $field !== 'slug')) {
            return $query->where($field, $value);
        }

        if (static::usesCatalogOnlySchema()) {
            return $query->where(function (Builder $titleQuery) use ($value): void {
                if (is_numeric($value)) {
                    $titleQuery->orWhere($this->qualifyColumn($this->getKeyName()), (int) $value);
                }

                if (! is_string($value) || $value === '') {
                    return;
                }

                $titleQuery
                    ->orWhere('imdb_id', $value)
                    ->orWhere('tconst', $value);

                if (preg_match('/(?P<imdb_id>tt\d+)$/i', $value, $matches) === 1) {
                    $imdbId = Str::lower((string) $matches['imdb_id']);

                    $titleQuery
                        ->orWhere('imdb_id', $imdbId)
                        ->orWhere('tconst', $imdbId);
                }
            });
        }

        return $query->where(function (Builder $titleQuery) use ($value): void {
            $titleQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $titleQuery->orWhere($this->qualifyColumn($this->getKeyName()), (int) $value);
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
        if (static::usesCatalogOnlySchema()) {
            return $query->whereNotNull('movies.primarytitle');
        }

        return $query->where('titles.is_published', true);
    }

    public function scopeWithoutEpisodes(Builder $query): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query->whereNotIn(
                'movies.titletype',
                self::remoteTypesForCatalogType(TitleType::Episode),
            );
        }

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
            return $query->where(static::catalogColumn('imdb_id'), Str::lower($search));
        }

        if (static::usesCatalogOnlySchema()) {
            return $query->where(function (Builder $titleQuery) use ($search): void {
                $titleQuery
                    ->where('movies.primarytitle', 'like', '%'.$search.'%')
                    ->orWhere('movies.originaltitle', 'like', '%'.$search.'%')
                    ->orWhere('movies.imdb_id', 'like', '%'.$search.'%')
                    ->orWhere('movies.tconst', 'like', '%'.$search.'%');
            });
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
            return $query->where(static::catalogColumn('imdb_id'), Str::lower($search));
        }

        $prefixSearch = $search.'%';

        if (static::usesCatalogOnlySchema()) {
            return $query->where(function (Builder $titleQuery) use ($search, $prefixSearch): void {
                $titleQuery
                    ->where('movies.primarytitle', $search)
                    ->orWhere('movies.originaltitle', $search)
                    ->orWhere('movies.primarytitle', 'like', $prefixSearch)
                    ->orWhere('movies.originaltitle', 'like', $prefixSearch);
            });
        }

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
        if (static::usesCatalogOnlySchema()) {
            return $query->whereIn('movies.titletype', self::remoteTypesForCatalogType($type));
        }

        return $query->where('titles.title_type', $type->value);
    }

    public function scopeRatedAtLeast(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query->whereHas(
            'statistic',
            fn (Builder $statisticQuery) => $statisticQuery->where(
                static::usesCatalogOnlySchema() ? 'vote_count' : 'rating_count',
                '>=',
                $minimumVotes,
            ),
        );
    }

    public function scopeOrderByTopRated(Builder $query, int $minimumVotes = 1): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query
                ->ratedAtLeast($minimumVotes)
                ->addSelect([
                    'average_rating_sort' => MovieRating::query()
                        ->select('aggregate_rating')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1),
                    'rating_count_sort' => MovieRating::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1),
                ])
                ->orderByDesc('average_rating_sort')
                ->orderByDesc('rating_count_sort')
                ->orderBy('movies.primarytitle');
        }

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
        if (static::usesCatalogOnlySchema()) {
            return $query
                ->addSelect([
                    'rating_count_sort' => MovieRating::query()
                        ->select('vote_count')
                        ->whereColumn('movie_ratings.movie_id', 'movies.id')
                        ->limit(1),
                ])
                ->orderByDesc('rating_count_sort')
                ->orderByDesc('movies.startyear')
                ->orderBy('movies.primarytitle');
        }

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

    public function scopeOrderByRelatedTitles(Builder $query): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query
                ->orderByDesc(static::catalogColumn('release_year'))
                ->orderBy(static::catalogColumn('name'));
        }

        return $query
            ->orderByDesc('titles.popularity_rank')
            ->orderByDesc('titles.release_year')
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
            $query->where(static::catalogColumn('release_year'), '>=', $yearFrom);
        }

        if ($yearTo !== null) {
            $query->where(static::catalogColumn('release_year'), '<=', $yearTo);
        }

        return $query;
    }

    public function scopeProducedInCountry(Builder $query, string $countryCode): Builder
    {
        $countryCode = strtoupper($countryCode);

        if (static::usesCatalogOnlySchema()) {
            return $query->whereHas(
                'countries',
                fn (Builder $countryQuery) => $countryQuery->where('countries.code', $countryCode),
            );
        }

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

        if (static::usesCatalogOnlySchema()) {
            return $query->whereHas('languages', function (Builder $languageQuery) use ($normalizedCode, $upperCode): void {
                $languageQuery->whereIn('languages.code', [$normalizedCode, $upperCode]);
            });
        }

        return $query->where(function (Builder $languageQuery) use ($normalizedCode, $upperCode): void {
            $languageQuery
                ->where('titles.original_language', $normalizedCode)
                ->orWhere('titles.original_language', $upperCode)
                ->orWhereJsonContains('titles.imdb_spoken_languages', $normalizedCode);
        });
    }

    public function scopeWithinRuntimeBucket(Builder $query, string $runtime): Builder
    {
        $runtimeColumn = static::catalogColumn('runtime_minutes');

        return match ($runtime) {
            'under-30' => $query->whereNotNull($runtimeColumn)->where($runtimeColumn, '<', 30),
            '30-60' => $query->whereBetween($runtimeColumn, [30, 60]),
            '60-90' => $query->whereBetween($runtimeColumn, [60, 90]),
            '90-120' => $query->whereBetween($runtimeColumn, [90, 120]),
            '120-plus' => $query->where($runtimeColumn, '>=', 120),
            default => $query,
        };
    }

    public function scopeForInterestCategory(Builder $query, InterestCategory|int $interestCategory): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            $interestCategoryId = $interestCategory instanceof InterestCategory
                ? (int) $interestCategory->getKey()
                : $interestCategory;

            return $query->whereHas('interests.interestCategories', function (Builder $interestCategoryQuery) use ($interestCategoryId): void {
                $interestCategoryQuery->where('interest_categories.id', $interestCategoryId);
            });
        }

        return $query;
    }

    public function scopeWithMatchedInterestCount(Builder $query, InterestCategory|int $interestCategory): Builder
    {
        $interestCategoryId = $interestCategory instanceof InterestCategory
            ? (int) $interestCategory->getKey()
            : $interestCategory;

        return $query->withCount([
            'interests as matched_interest_count' => fn (Builder $interestQuery) => $interestQuery->whereHas(
                'interestCategories',
                fn (Builder $interestCategoryQuery) => $interestCategoryQuery->where('interest_categories.id', $interestCategoryId),
            ),
        ]);
    }

    public function canonicalTitle(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_title_id');
    }

    public function genres(): BelongsToMany
    {
        if (static::usesCatalogOnlySchema()) {
            return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id', 'id', 'id')
                ->orderBy('genres.name');
        }

        return $this->belongsToMany(Genre::class)
            ->withTimestamps()
            ->orderBy('genres.name');
    }

    public function statistic(): HasOne
    {
        if (static::usesCatalogOnlySchema()) {
            return $this->hasOne(MovieRating::class, 'movie_id', 'id');
        }

        return $this->hasOne(TitleStatistic::class);
    }

    public function plotRecord(): HasOne
    {
        return $this->hasOne(MoviePlot::class, 'movie_id', 'id');
    }

    public function moviePrimaryImage(): HasOne
    {
        return $this->hasOne(MoviePrimaryImage::class, 'movie_id', 'id');
    }

    public function primaryImageRecord(): HasOne
    {
        return $this->moviePrimaryImage();
    }

    public function movieImages(): HasMany
    {
        return $this->hasMany(MovieImage::class, 'movie_id', 'id')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function titleImages(): HasMany
    {
        return $this->movieImages();
    }

    public function titleVideos(): HasMany
    {
        return $this->hasMany(TitleVideo::class, 'movie_id', 'id')
            ->orderBy('position')
            ->orderBy('imdb_id');
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'movie_interests', 'movie_id', 'interest_imdb_id', 'id', 'imdb_id');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'movie_origin_countries', 'movie_id', 'country_code', 'id', 'code');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'movie_spoken_languages', 'movie_id', 'language_code', 'id', 'code');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class, static::usesCatalogOnlySchema() ? 'movie_id' : 'title_id')->ordered();
    }

    public function boxOfficeRecord(): HasOne
    {
        return $this->hasOne(MovieBoxOffice::class, 'movie_id', 'id');
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

    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable')->latest('created_at');
    }

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(TitleRelationship::class, 'from_title_id')
            ->orderBy('relationship_type')
            ->orderBy('id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(TitleRelationship::class, 'to_title_id')
            ->orderBy('relationship_type')
            ->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(
            AwardNomination::class,
            static::usesCatalogOnlySchema() ? 'movie_id' : 'title_id',
            'id',
        )->ordered();
    }

    public function titleTranslations(): HasMany
    {
        return $this->hasMany(TitleTranslation::class);
    }

    public function translations(): HasMany
    {
        return $this->titleTranslations();
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
        $genres = $this->getRelation('genres')->take($limit);

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

        /** @var EloquentCollection<int, Genre> $genres */
        $genres = $this->getRelation('genres');

        return $genres
            ->filter(fn (mixed $genre): bool => $genre instanceof Genre && filled($genre->name))
            ->unique('id')
            ->values();
    }

    public function displayAverageRating(): ?float
    {
        if (! $this->relationLoaded('statistic')) {
            return null;
        }

        $rating = $this->statistic?->average_rating ?? $this->statistic?->aggregate_rating;

        return $rating !== null
            ? (float) $rating
            : null;
    }

    public function displayRatingCount(): int
    {
        if (! $this->relationLoaded('statistic')) {
            return 0;
        }

        return (int) ($this->statistic?->rating_count ?? $this->statistic?->vote_count ?? 0);
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
        $originCountry = $this->attributes['origin_country'] ?? null;

        if (! filled($originCountry)) {
            return null;
        }

        return Str::of((string) $originCountry)
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
        $language = $this->attributes['original_language'] ?? null;

        return filled($language) ? LanguageCode::labelFor((string) $language) : null;
    }

    public function summaryText(): ?string
    {
        $summary = $this->attributes['tagline'] ?? null;

        if (! filled($summary)) {
            $summary = $this->attributes['synopsis'] ?? null;
        }

        if (! filled($summary)) {
            $summary = $this->attributes['plot_outline'] ?? null;
        }

        if (! filled($summary) && $this->relationLoaded('plotRecord')) {
            $summary = $this->plotRecord?->plot;
        }

        return filled($summary) ? (string) $summary : null;
    }

    public function seoTitle(): string
    {
        $metaTitle = $this->attributes['meta_title'] ?? null;

        return filled($metaTitle)
            ? (string) $metaTitle
            : (string) $this->name;
    }

    public function seoDescription(): string
    {
        $metaDescription = $this->attributes['meta_description'] ?? null;

        if (filled($metaDescription)) {
            return (string) $metaDescription;
        }

        return $this->summaryText()
            ?? 'Browse cast, awards, genres, ratings, and release details for '.$this->name.'.';
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

    public function movieAkas(): HasMany
    {
        return $this->hasMany(MovieAka::class, 'movie_id', 'id');
    }

    public function runtimeMinutesLabel(): ?string
    {
        return self::formatMinutesLabel($this->runtime_minutes);
    }

    /**
     * @return SupportCollection<int, MovieAka>
     */
    public function resolvedMovieAkas(): SupportCollection
    {
        if ($this->relationLoaded('movieAkas')) {
            return $this->movieAkas
                ->filter(fn (mixed $movieAka): bool => $movieAka instanceof MovieAka)
                ->unique('id')
                ->sortBy([
                    ['position', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();
        }

        if (! static::usesCatalogOnlySchema()) {
            return collect();
        }

        return $this->movieAkas()
            ->selectArchiveColumns()
            ->withTitleDetailRelations()
            ->archiveOrdered()
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, MovieAkaAttribute>
     */
    public function resolvedMovieAkaAttributes(): SupportCollection
    {
        return $this->resolvedMovieAkas()
            ->flatMap(function (MovieAka $movieAka): SupportCollection {
                if (! $movieAka->relationLoaded('movieAkaAttributes')) {
                    return collect();
                }

                return $movieAka->movieAkaAttributes;
            })
            ->filter(fn (mixed $movieAkaAttribute): bool => $movieAkaAttribute instanceof MovieAkaAttribute)
            ->unique(fn (MovieAkaAttribute $movieAkaAttribute): string => implode('|', [
                $movieAkaAttribute->movie_aka_id,
                $movieAkaAttribute->aka_attribute_id,
            ]))
            ->sortBy([
                ['movie_aka_id', 'asc'],
                ['position', 'asc'],
                ['aka_attribute_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, AkaType>
     */
    public function resolvedAkaTypes(): SupportCollection
    {
        return $this->resolvedMovieAkas()
            ->flatMap(function (MovieAka $movieAka): SupportCollection {
                if (! $movieAka->relationLoaded('movieAkaTypes')) {
                    return collect();
                }

                return $movieAka->movieAkaTypes;
            })
            ->map(fn (MovieAkaType $movieAkaType): ?AkaType => $movieAkaType->akaType)
            ->filter(fn (mixed $akaType): bool => $akaType instanceof AkaType && filled($akaType->name))
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedAwardCategories(): SupportCollection
    {
        return $this->resolvedMovieAwardNominations()
            ->map(fn (AwardNomination $awardNomination): ?AwardCategory => $awardNomination->awardCategory)
            ->filter(fn (mixed $awardCategory): bool => $awardCategory instanceof AwardCategory && filled($awardCategory->name))
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedAwardEvents(): SupportCollection
    {
        return $this->resolvedMovieAwardNominations()
            ->map(fn (AwardNomination $awardNomination): ?AwardEvent => $awardNomination->awardEvent)
            ->filter(fn (mixed $awardEvent): bool => $awardEvent instanceof AwardEvent && filled($awardEvent->name))
            ->unique('imdb_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, AwardNomination>
     */
    public function resolvedMovieAwardNominations(): SupportCollection
    {
        if ($this->relationLoaded('awardNominations')) {
            return $this->awardNominations
                ->unique('id')
                ->values();
        }

        if (! static::usesCatalogOnlySchema()) {
            return collect();
        }

        return $this->awardNominations()
            ->selectTitleDetailColumns()
            ->withTitleDetailRelations()
            ->ordered()
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAwardNominationNominees(): SupportCollection
    {
        return $this->resolvedMovieAwardNominations()
            ->flatMap(fn (AwardNomination $awardNomination): SupportCollection => $this->loadedRelationItems($awardNomination, 'movieAwardNominationNominees'))
            ->filter(fn (mixed $nominee): bool => $nominee instanceof MovieAwardNominationNominee)
            ->unique(fn (MovieAwardNominationNominee $nominee): string => implode('|', [
                $nominee->movie_award_nomination_id,
                $nominee->name_basic_id,
            ]))
            ->sortBy([
                ['movie_award_nomination_id', 'asc'],
                ['position', 'asc'],
                ['name_basic_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAwardNominationTitles(): SupportCollection
    {
        return $this->resolvedMovieAwardNominations()
            ->flatMap(fn (AwardNomination $awardNomination): SupportCollection => $this->loadedRelationItems($awardNomination, 'movieAwardNominationTitles'))
            ->filter(fn (mixed $nominationTitle): bool => $nominationTitle instanceof MovieAwardNominationTitle)
            ->unique(fn (MovieAwardNominationTitle $nominationTitle): string => implode('|', [
                $nominationTitle->movie_award_nomination_id,
                $nominationTitle->nominated_movie_id,
            ]))
            ->sortBy([
                ['movie_award_nomination_id', 'asc'],
                ['position', 'asc'],
                ['nominated_movie_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieAwardNominationSummaries(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'awardNominationSummary')
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieAwardNominationSummary)
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCertificates(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'certificateRecords')
            ->filter(fn (mixed $certificate): bool => $certificate instanceof MovieCertificate)
            ->unique(function (MovieCertificate $certificate): string {
                if ($certificate->certificate_rating_id !== null || $certificate->country_code !== null) {
                    return implode('|', [
                        $certificate->movie_id,
                        $certificate->certificate_rating_id,
                        $certificate->country_code,
                    ]);
                }

                return 'certificate:'.$certificate->getKey();
            })
            ->sortBy([
                ['position', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCertificateAttributes(): SupportCollection
    {
        return $this->resolvedMovieCertificates()
            ->flatMap(fn (MovieCertificate $certificate): SupportCollection => $this->loadedRelationItems($certificate, 'movieCertificateAttributes'))
            ->filter(fn (mixed $attribute): bool => $attribute instanceof MovieCertificateAttribute)
            ->unique(fn (MovieCertificateAttribute $attribute): string => implode('|', [
                $attribute->movie_certificate_id,
                $attribute->certificate_attribute_id,
            ]))
            ->sortBy([
                ['movie_certificate_id', 'asc'],
                ['position', 'asc'],
                ['certificate_attribute_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCertificateSummaries(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'certificateSummary')
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieCertificateSummary)
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCredits(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'movieCompanyCredits')
            ->filter(fn (mixed $credit): bool => $credit instanceof MovieCompanyCredit)
            ->unique('id')
            ->sortBy([
                ['position', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCreditAttributes(): SupportCollection
    {
        return $this->resolvedMovieCompanyCredits()
            ->flatMap(fn (MovieCompanyCredit $credit): SupportCollection => $this->loadedRelationItems($credit, 'movieCompanyCreditAttributes'))
            ->filter(fn (mixed $attribute): bool => $attribute instanceof MovieCompanyCreditAttribute)
            ->unique(fn (MovieCompanyCreditAttribute $attribute): string => implode('|', [
                $attribute->movie_company_credit_id,
                $attribute->company_credit_attribute_id,
            ]))
            ->sortBy([
                ['movie_company_credit_id', 'asc'],
                ['position', 'asc'],
                ['company_credit_attribute_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCreditCountries(): SupportCollection
    {
        return $this->resolvedMovieCompanyCredits()
            ->flatMap(fn (MovieCompanyCredit $credit): SupportCollection => $this->loadedRelationItems($credit, 'movieCompanyCreditCountries'))
            ->filter(fn (mixed $country): bool => $country instanceof MovieCompanyCreditCountry)
            ->unique(fn (MovieCompanyCreditCountry $country): string => implode('|', [
                $country->movie_company_credit_id,
                $country->country_code,
            ]))
            ->sortBy([
                ['movie_company_credit_id', 'asc'],
                ['position', 'asc'],
                ['country_code', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieCompanyCreditSummaries(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'companyCreditSummary')
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieCompanyCreditSummary)
            ->values();
    }

    /**
     * @return SupportCollection<int, Credit>
     */
    public function resolvedMovieDirectors(): SupportCollection
    {
        if ($this->relationLoaded('movieDirectors')) {
            return $this->movieDirectors
                ->filter(fn (mixed $director): bool => $director instanceof MovieDirector)
                ->unique(fn (MovieDirector $director): string => implode('|', [
                    $director->movie_id,
                    $director->name_basic_id,
                ]))
                ->sortBy([
                    ['movie_id', 'asc'],
                    ['position', 'asc'],
                    ['name_basic_id', 'asc'],
                ])
                ->values();
        }

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
        if ($this->relationLoaded('movieEpisodes')) {
            return $this->movieEpisodes
                ->filter(fn (mixed $episode): bool => $episode instanceof MovieEpisode)
                ->unique('episode_movie_id')
                ->sortBy([
                    ['season', 'asc'],
                    ['episode_number', 'asc'],
                    ['episode_movie_id', 'asc'],
                ])
                ->values();
        }

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
        return $this->loadedRelationItems($this, 'episodeSummary')
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieEpisodeSummary)
            ->values();
    }

    /**
     * @return SupportCollection<int, Genre>
     */
    public function resolvedMovieGenres(): SupportCollection
    {
        if ($this->relationLoaded('movieGenres')) {
            return $this->movieGenres
                ->filter(fn (mixed $movieGenre): bool => $movieGenre instanceof MovieGenre)
                ->unique(fn (MovieGenre $movieGenre): string => implode('|', [
                    $movieGenre->movie_id,
                    $movieGenre->genre_id,
                ]))
                ->sortBy([
                    ['position', 'asc'],
                    ['genre_id', 'asc'],
                ])
                ->values();
        }

        return $this->resolvedGenres();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieImageSummaries(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'imageSummary')
            ->filter(fn (mixed $summary): bool => $summary instanceof MovieImageSummary)
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedCertificateAttributes(): SupportCollection
    {
        return $this->resolvedMovieCertificates()
            ->flatMap(fn (MovieCertificate $certificate): SupportCollection => $this->loadedRelationItems($certificate, 'movieCertificateAttributes'))
            ->map(fn (MovieCertificateAttribute $attribute): ?CertificateAttribute => $attribute->certificateAttribute)
            ->filter(fn (mixed $attribute): bool => $attribute instanceof CertificateAttribute)
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedCertificateRatings(): SupportCollection
    {
        return $this->resolvedMovieCertificates()
            ->map(fn (MovieCertificate $certificate): ?CertificateRating => $certificate->certificateRating)
            ->filter(fn (mixed $rating): bool => $rating instanceof CertificateRating)
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedCompanies(): SupportCollection
    {
        return $this->resolvedMovieCompanyCredits()
            ->map(fn (MovieCompanyCredit $credit): ?Company => $credit->company)
            ->filter(fn (mixed $company): bool => $company instanceof Company)
            ->unique('imdb_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedCompanyCreditAttributes(): SupportCollection
    {
        return $this->resolvedMovieCompanyCredits()
            ->flatMap(fn (MovieCompanyCredit $credit): SupportCollection => $this->loadedRelationItems($credit, 'movieCompanyCreditAttributes'))
            ->map(fn (MovieCompanyCreditAttribute $attribute): ?CompanyCreditAttribute => $attribute->companyCreditAttribute)
            ->filter(fn (mixed $attribute): bool => $attribute instanceof CompanyCreditAttribute)
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedCompanyCreditCategories(): SupportCollection
    {
        return $this->resolvedMovieCompanyCredits()
            ->map(fn (MovieCompanyCredit $credit): ?CompanyCreditCategory => $credit->companyCreditCategory)
            ->filter(fn (mixed $category): bool => $category instanceof CompanyCreditCategory)
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedMovieBoxOfficeRows(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'boxOfficeRecord')
            ->filter(fn (mixed $boxOffice): bool => $boxOffice instanceof MovieBoxOffice)
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedCurrencies(): SupportCollection
    {
        return $this->resolvedMovieBoxOfficeRows()
            ->flatMap(fn (MovieBoxOffice $boxOffice): SupportCollection => collect([
                $boxOffice->productionBudget,
                $boxOffice->domesticGross,
                $boxOffice->openingWeekendGross,
                $boxOffice->worldwideGross,
            ]))
            ->filter(fn (mixed $currency): bool => $currency instanceof Currency)
            ->unique('code')
            ->values();
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function resolvedInterests(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'interests')
            ->filter(fn (mixed $interest): bool => $interest instanceof Interest)
            ->unique('imdb_id')
            ->values();
    }

    /**
     * @return SupportCollection<int, Country>
     */
    public function resolvedCountries(): SupportCollection
    {
        return $this->loadedRelationItems($this, 'countries')
            ->filter(fn (mixed $country): bool => $country instanceof Country)
            ->unique('code')
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestCategory>
     */
    public function resolvedInterestCategories(): SupportCollection
    {
        return $this->resolvedInterests()
            ->flatMap(fn (Interest $interest): SupportCollection => $this->loadedRelationItems($interest, 'interestCategoryInterests'))
            ->map(fn (InterestCategoryInterest $interestCategoryInterest): ?InterestCategory => $interestCategoryInterest->interestCategory)
            ->filter(fn (mixed $interestCategory): bool => $interestCategory instanceof InterestCategory)
            ->unique('id')
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestCategoryInterest>
     */
    public function resolvedInterestCategoryInterests(): SupportCollection
    {
        return $this->resolvedInterests()
            ->flatMap(fn (Interest $interest): SupportCollection => $this->loadedRelationItems($interest, 'interestCategoryInterests'))
            ->filter(fn (mixed $interestCategoryInterest): bool => $interestCategoryInterest instanceof InterestCategoryInterest)
            ->unique(fn (InterestCategoryInterest $interestCategoryInterest): string => implode('|', [
                $interestCategoryInterest->interest_category_id,
                $interestCategoryInterest->interest_imdb_id,
            ]))
            ->sortBy([
                ['interest_category_id', 'asc'],
                ['position', 'asc'],
                ['interest_imdb_id', 'asc'],
            ])
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestPrimaryImage>
     */
    public function resolvedInterestPrimaryImages(): SupportCollection
    {
        return $this->resolvedInterests()
            ->flatMap(fn (Interest $interest): SupportCollection => $this->loadedRelationItems($interest, 'interestPrimaryImages'))
            ->filter(fn (mixed $image): bool => $image instanceof InterestPrimaryImage)
            ->unique(fn (InterestPrimaryImage $image): string => implode('|', [
                $image->interest_imdb_id,
                $image->url,
            ]))
            ->values();
    }

    /**
     * @return SupportCollection<int, InterestSimilarInterest>
     */
    public function resolvedInterestSimilarInterests(): SupportCollection
    {
        return $this->resolvedInterests()
            ->flatMap(fn (Interest $interest): SupportCollection => $this->loadedRelationItems($interest, 'interestSimilarInterests'))
            ->filter(fn (mixed $similarInterest): bool => $similarInterest instanceof InterestSimilarInterest)
            ->unique(fn (InterestSimilarInterest $similarInterest): string => implode('|', [
                $similarInterest->interest_imdb_id,
                $similarInterest->similar_interest_imdb_id,
            ]))
            ->sortBy([
                ['position', 'asc'],
                ['interest_imdb_id', 'asc'],
                ['similar_interest_imdb_id', 'asc'],
            ])
            ->values();
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
        $language = $this->attributes['original_language'] ?? null;

        return collect([$language])
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
     * @return SupportCollection<int, mixed>
     */
    private function loadedRelationItems(Model $model, string $relation): SupportCollection
    {
        if (! $model->relationLoaded($relation)) {
            return collect();
        }

        $relationValue = $model->getRelation($relation);

        if ($relationValue instanceof SupportCollection) {
            return $relationValue->values();
        }

        return collect([$relationValue])
            ->filter()
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

    private static function catalogSeriesRelationsAvailable(): bool
    {
        return once(function (): bool {
            $schema = Schema::connection('imdb_mysql');

            return $schema->hasTable('seasons') && $schema->hasTable('episodes');
        });
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

    public function getIsPublishedAttribute(?bool $value): bool
    {
        if ($value !== null) {
            return (bool) $value;
        }

        if (static::usesCatalogOnlySchema()) {
            return filled($this->attributes['primarytitle'] ?? null);
        }

        return false;
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

    public function getAgeRatingAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    public function getMetaTitleAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    public function getMetaDescriptionAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
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
        $year = $this->attributes['startyear'] ?? $this->attributes['release_year'] ?? null;

        return $year !== null ? (int) $year : null;
    }

    public function getEndyearAttribute(): ?int
    {
        $year = $this->attributes['endyear'] ?? $this->attributes['end_year'] ?? null;

        return $year !== null ? (int) $year : null;
    }

    public function getRuntimeminutesAttribute(): ?int
    {
        $minutes = $this->attributes['runtimeminutes'] ?? $this->attributes['runtime_minutes'] ?? null;

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

    private function normalizeCatalogOnlyAttributesForPersistence(): void
    {
        $name = $this->attributes['primarytitle'] ?? $this->attributes['name'] ?? null;
        $originalName = $this->attributes['originaltitle'] ?? $this->attributes['original_name'] ?? $name;
        $imdbId = $this->attributes['imdb_id'] ?? $this->attributes['tconst'] ?? null;
        $catalogType = $this->attributes['titletype']
            ?? $this->attributes['imdb_type']
            ?? (($this->attributes['title_type'] ?? null) !== null
                ? (self::remoteTypesForCatalogType($this->title_type)[0] ?? null)
                : null);
        $runtimeMinutes = $this->attributes['runtimeminutes'] ?? $this->attributes['runtime_minutes'] ?? null;
        $runtimeSeconds = $this->attributes['runtimeSeconds'] ?? $this->attributes['runtime_seconds'] ?? null;

        $this->attributes['primarytitle'] = $name;
        $this->attributes['originaltitle'] = $originalName;
        $this->attributes['imdb_id'] = $imdbId;
        $this->attributes['tconst'] = $imdbId;
        $this->attributes['titletype'] = $catalogType;
        $this->attributes['startyear'] = $this->attributes['startyear'] ?? $this->attributes['release_year'] ?? null;
        $this->attributes['endyear'] = $this->attributes['endyear'] ?? $this->attributes['end_year'] ?? null;
        $this->attributes['runtimeminutes'] = $runtimeMinutes;
        $this->attributes['runtimeSeconds'] = $runtimeSeconds ?? ($runtimeMinutes !== null ? ((int) $runtimeMinutes) * 60 : null);
        $this->attributes['isadult'] = (int) ($this->attributes['isadult'] ?? 0);

        foreach ([
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
            'imdb_type',
            'runtime_seconds',
            'imdb_genres',
            'imdb_interests',
            'imdb_origin_countries',
            'imdb_spoken_languages',
            'imdb_payload',
        ] as $attribute) {
            unset($this->attributes[$attribute]);
        }
    }

    /**
     * @return SupportCollection<int, CatalogMediaAsset>
     */
    private function allMediaAssets(): SupportCollection
    {
        $assets = collect();

        if ($this->relationLoaded('mediaAssets')) {
            $assets = $assets->merge($this->mediaAssets
                ->filter(fn (mixed $asset): bool => $asset instanceof MediaAsset)
                ->map(fn (MediaAsset $mediaAsset): CatalogMediaAsset => $mediaAsset->toCatalogMediaAsset()));
        }

        if ($this->relationLoaded('moviePrimaryImage') && $this->moviePrimaryImage instanceof MoviePrimaryImage && filled($this->moviePrimaryImage->url)) {
            $assets->push(CatalogMediaAsset::fromCatalog([
                'kind' => MediaKind::tryFrom((string) $this->moviePrimaryImage->type) ?? MediaKind::Poster,
                'url' => $this->moviePrimaryImage->url,
                'alt_text' => $this->name,
                'width' => $this->moviePrimaryImage->width,
                'height' => $this->moviePrimaryImage->height,
                'is_primary' => true,
                'position' => 0,
            ]));
        }

        if ($this->relationLoaded('primaryImageRecord') && $this->primaryImageRecord instanceof MoviePrimaryImage && filled($this->primaryImageRecord->url)) {
            $assets->push(CatalogMediaAsset::fromCatalog([
                'kind' => MediaKind::tryFrom((string) $this->primaryImageRecord->type) ?? MediaKind::Poster,
                'url' => $this->primaryImageRecord->url,
                'alt_text' => $this->name,
                'width' => $this->primaryImageRecord->width,
                'height' => $this->primaryImageRecord->height,
                'is_primary' => true,
                'position' => 0,
            ]));
        }

        if ($this->relationLoaded('movieImages')) {
            $assets = $assets->merge($this->movieImages
                ->filter(fn (mixed $image): bool => $image instanceof MovieImage && filled($image->url))
                ->map(function (MovieImage $movieImage): CatalogMediaAsset {
                    return CatalogMediaAsset::fromCatalog([
                        'kind' => MediaKind::tryFrom((string) $movieImage->type) ?? MediaKind::Gallery,
                        'url' => $movieImage->url,
                        'alt_text' => $this->name,
                        'width' => $movieImage->width,
                        'height' => $movieImage->height,
                        'is_primary' => false,
                        'position' => $movieImage->position,
                    ]);
                }));
        }

        if ($this->relationLoaded('titleImages')) {
            $assets = $assets->merge($this->titleImages
                ->filter(fn (mixed $image): bool => $image instanceof CatalogMediaAsset && filled($image->url))
                ->map(function (CatalogMediaAsset $titleImage): CatalogMediaAsset {
                    return CatalogMediaAsset::fromCatalog([
                        'kind' => $titleImage->kind,
                        'url' => $titleImage->url,
                        'alt_text' => $this->name,
                        'width' => $titleImage->width,
                        'height' => $titleImage->height,
                        'is_primary' => $titleImage->is_primary,
                        'position' => $titleImage->position,
                    ]);
                }));
        }

        if ($this->relationLoaded('titleVideos')) {
            $assets = $assets->merge($this->titleVideos
                ->filter(fn (mixed $video): bool => $video instanceof TitleVideo && filled($video->url))
                ->map(function (TitleVideo $titleVideo): CatalogMediaAsset {
                    return CatalogMediaAsset::fromCatalog([
                        'kind' => $titleVideo->kind,
                        'url' => $titleVideo->url,
                        'alt_text' => $titleVideo->alt_text,
                        'caption' => $titleVideo->caption,
                        'width' => $titleVideo->width,
                        'height' => $titleVideo->height,
                        'duration_seconds' => $titleVideo->duration_seconds,
                        'provider' => $titleVideo->provider,
                        'provider_key' => $titleVideo->provider_key,
                        'is_primary' => $titleVideo->is_primary,
                        'position' => $titleVideo->position,
                    ]);
                }));
        }

        return $assets
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->unique('url')
            ->values()
            ->map(function (CatalogMediaAsset $mediaAsset): CatalogMediaAsset {
                return CatalogMediaAsset::fromCatalog([
                    'kind' => $mediaAsset->kind,
                    'url' => $mediaAsset->url,
                    'alt_text' => $mediaAsset->alt_text,
                    'caption' => $mediaAsset->caption,
                    'width' => $mediaAsset->width,
                    'height' => $mediaAsset->height,
                    'duration_seconds' => $mediaAsset->duration_seconds,
                    'provider' => $mediaAsset->provider,
                    'provider_key' => $mediaAsset->provider_key,
                    'is_primary' => $mediaAsset->is_primary,
                    'position' => $mediaAsset->position,
                    'published_at' => $mediaAsset->published_at,
                    'metadata' => $mediaAsset->metadata,
                ]);
            })
            ->values();
    }
}
