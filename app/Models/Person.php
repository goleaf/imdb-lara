<?php

namespace App\Models;

use App\Enums\MediaKind;
use Database\Factories\PersonFactory;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'alternate_names',
        'slug',
        'biography',
        'short_biography',
        'known_for_department',
        'birth_date',
        'death_date',
        'birth_place',
        'death_place',
        'nationality',
        'popularity_rank',
        'meta_title',
        'meta_description',
        'search_keywords',
        'is_published',
        'imdb_id',
        'imdb_alternative_names',
        'imdb_primary_professions',
        'imdb_payload',
        'nconst',
        'primaryname',
        'displayName',
        'alternativeNames',
        'primaryprofession',
        'primaryProfessions',
        'birthLocation',
        'deathLocation',
        'primaryImage_url',
        'primaryImage_width',
        'primaryImage_height',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
            'popularity_rank' => 'integer',
            'is_published' => 'boolean',
            'imdb_alternative_names' => 'array',
            'imdb_primary_professions' => 'array',
            'imdb_payload' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $person): void {
            if (static::usesCatalogOnlySchema()) {
                $person->normalizeCatalogOnlyAttributesForPersistence();

                return;
            }

            $person->slug = $person->slug ?: Str::slug($person->name ?: 'unknown-person');
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
        return static::usesCatalogOnlySchema() ? 'name_basics' : parent::getTable();
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

        $config = $container->make('config');

        if ((bool) $config->get('screenbase.catalog_only', false)) {
            return true;
        }

        return $config->get('database.default') === 'imdb_mysql';
    }

    public static function catalogPeopleAvailable(): bool
    {
        return ! static::usesCatalogOnlySchema() || Title::catalogTablesAvailable('name_basics');
    }

    public static function catalogColumn(string $localColumn): string
    {
        if (! static::usesCatalogOnlySchema()) {
            return 'people.'.$localColumn;
        }

        return match ($localColumn) {
            'name' => 'name_basics.primaryname',
            'alternate_names' => 'name_basics.alternativeNames',
            'biography', 'short_biography' => 'name_basics.biography',
            'known_for_department' => 'name_basics.primaryprofession',
            'birth_place' => 'name_basics.birthLocation',
            'death_place' => 'name_basics.deathLocation',
            'imdb_id' => 'name_basics.imdb_id',
            default => 'name_basics.'.$localColumn,
        };
    }

    /**
     * @return list<string>
     */
    public static function directoryColumns(): array
    {
        if (static::usesCatalogOnlySchema()) {
            return [
                'name_basics.id',
                'name_basics.nconst',
                'name_basics.imdb_id',
                'name_basics.primaryname',
                'name_basics.displayName',
                'name_basics.alternativeNames',
                'name_basics.biography',
                'name_basics.primaryprofession',
                'name_basics.birthyear',
                'name_basics.deathyear',
                'name_basics.birthLocation',
                'name_basics.deathLocation',
                'name_basics.primaryImage_url',
                'name_basics.primaryImage_width',
                'name_basics.primaryImage_height',
                'name_basics.primaryProfessions',
            ];
        }

        return [
            'people.id',
            'people.imdb_id',
            'people.name',
            'people.slug',
            'people.alternate_names',
            'people.short_biography',
            'people.biography',
            'people.known_for_department',
            'people.birth_date',
            'people.death_date',
            'people.birth_place',
            'people.death_place',
            'people.nationality',
            'people.popularity_rank',
            'people.meta_title',
            'people.meta_description',
            'people.search_keywords',
            'people.is_published',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function directoryRelations(): array
    {
        if (static::usesCatalogOnlySchema()) {
            return [
                'personImages:name_basic_id,position,url,width,height,type',
                'professions' => fn ($query) => $query
                    ->select([
                        'name_basic_professions.name_basic_id',
                        'name_basic_professions.profession_id',
                        'name_basic_professions.position',
                    ])
                    ->with('professionTerm:id,name')
                    ->ordered(),
            ];
        }

        return [
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
                    'metadata',
                    'is_primary',
                    'position',
                    'published_at',
                ])
                ->ordered(),
            'professions:id,person_id,department,profession,is_primary,sort_order',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function detailRelations(): array
    {
        if (static::usesCatalogOnlySchema()) {
            $relations = [
                ...self::directoryRelations(),
            ];

            if (Title::catalogTablesAvailable('name_basic_meter_rankings')) {
                $relations[] = 'meterRanking:name_basic_id,current_rank,change_direction,difference';
            }

            if (Credit::catalogCreditsAvailable()) {
                $relations['credits'] = fn ($query) => $query
                    ->select(Credit::projectedColumns())
                    ->ordered()
                    ->with([
                        ...Credit::projectedRelations(),
                        'title' => fn ($titleQuery) => $titleQuery
                            ->select(Title::catalogCardColumns())
                            ->with(Title::catalogCardRelations()),
                    ]);
            }

            return $relations;
        }

        return [
            ...self::directoryRelations(),
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
                    'title' => fn ($titleQuery) => $titleQuery
                        ->select(Title::catalogCardColumns())
                        ->with(Title::catalogCardRelations()),
                    'profession:id,person_id,department,profession,is_primary,sort_order',
                ]),
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
            return $query->where(function (Builder $personQuery) use ($value): void {
                if (is_numeric($value)) {
                    $personQuery->whereKey((int) $value);
                }

                if (is_string($value) && $value !== '') {
                    $personQuery
                        ->orWhere('imdb_id', $value)
                        ->orWhere('nconst', $value);

                    if (preg_match('/(?P<imdb_id>nm\d+)$/i', $value, $matches) === 1) {
                        $imdbId = Str::lower((string) $matches['imdb_id']);

                        $personQuery
                            ->orWhere('imdb_id', $imdbId)
                            ->orWhere('nconst', $imdbId);
                    }
                }
            });
        }

        return $query->where(function (Builder $personQuery) use ($value): void {
            $personQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $personQuery->orWhere($this->qualifyColumn($this->getKeyName()), (int) $value);
            }

            if (is_string($value) && $value !== '') {
                $personQuery
                    ->orWhere('imdb_id', $value)
                    ->orWhere('slug', Str::slug($value));
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query->whereNotNull('name_basics.primaryname');
        }

        return $query->where('people.is_published', true);
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^nm\d+$/i', $search) === 1) {
            return $query->where(static::catalogColumn('imdb_id'), Str::lower($search));
        }

        if (static::usesCatalogOnlySchema()) {
            return $query->where(function (Builder $personQuery) use ($search): void {
                $personQuery
                    ->where('name_basics.primaryname', 'like', '%'.$search.'%')
                    ->orWhere('name_basics.displayName', 'like', '%'.$search.'%')
                    ->orWhere('name_basics.alternativeNames', 'like', '%'.$search.'%')
                    ->orWhere('name_basics.biography', 'like', '%'.$search.'%')
                    ->orWhere('name_basics.imdb_id', 'like', '%'.$search.'%')
                    ->orWhere('name_basics.nconst', 'like', '%'.$search.'%');
            });
        }

        return $query->where(function (Builder $personQuery) use ($search): void {
            $personQuery
                ->where('people.name', 'like', '%'.$search.'%')
                ->orWhere('people.alternate_names', 'like', '%'.$search.'%')
                ->orWhere('people.biography', 'like', '%'.$search.'%')
                ->orWhere('people.short_biography', 'like', '%'.$search.'%')
                ->orWhere('people.imdb_id', 'like', '%'.$search.'%');
        });
    }

    public function scopeSelectDirectoryColumns(Builder $query): Builder
    {
        return $query->select(self::directoryColumns());
    }

    public function scopeWithDirectoryRelations(Builder $query): Builder
    {
        return $query->with(self::directoryRelations());
    }

    public function scopeWithDirectoryMetrics(Builder $query): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            $metrics = [];

            if (Title::catalogTablesAvailable('name_credit_summaries')) {
                $metrics['credits_count'] = NameCreditSummary::query()
                    ->select('total_count')
                    ->whereColumn('name_credit_summaries.name_basic_id', 'name_basics.id')
                    ->limit(1);
            } else {
                $metrics['credits_count'] = 0;
            }

            if (Title::catalogTablesAvailable('name_basic_meter_rankings')) {
                $metrics['popularity_rank'] = NameBasicMeterRanking::query()
                    ->select('current_rank')
                    ->whereColumn('name_basic_meter_rankings.name_basic_id', 'name_basics.id')
                    ->limit(1);
            } else {
                $metrics['popularity_rank'] = 0;
            }

            $query->addSelect($metrics);

            if (Title::catalogTablesAvailable('movie_award_nomination_nominees')) {
                return $query->withCount('awardNominations');
            }

            return $query->addSelect(['award_nominations_count' => 0]);
        }

        return $query
            ->withCount(['credits'])
            ->addSelect(['award_nominations_count' => 0]);
    }

    public function scopeWithDetailRelations(Builder $query): Builder
    {
        return $query->with(self::detailRelations());
    }

    public function scopeInProfession(Builder $query, string $profession): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query->whereHas(
                'professionTerms',
                fn (Builder $builder) => $builder->where('professions.name', $profession),
            );
        }

        return $query->whereHas(
            'professions',
            fn (Builder $builder) => $builder->where('profession', $profession),
        );
    }

    public function credits(): HasMany
    {
        return $this->hasMany(
            Credit::class,
            static::usesCatalogOnlySchema() ? 'name_basic_id' : 'person_id',
        )->ordered();
    }

    public function professions(): HasMany
    {
        return $this->hasMany(
            PersonProfession::class,
            static::usesCatalogOnlySchema() ? 'name_basic_id' : 'person_id',
        )->ordered();
    }

    public function professionTerms(): HasMany|BelongsToMany
    {
        if (static::usesCatalogOnlySchema()) {
            return $this->belongsToMany(Profession::class, 'name_basic_professions', 'name_basic_id', 'profession_id', 'id', 'id')
                ->orderBy('professions.name');
        }

        return $this->professions();
    }

    public function meterRanking(): HasOne
    {
        return $this->hasOne(NameBasicMeterRanking::class, 'name_basic_id', 'id');
    }

    public function personImages(): HasMany
    {
        return $this->hasMany(PersonImage::class, 'name_basic_id', 'id');
    }

    public function knownForTitles(): BelongsToMany
    {
        if (static::usesCatalogOnlySchema()) {
            return $this->belongsToMany(
                Title::class,
                'name_basic_known_for_titles',
                'name_basic_id',
                'title_basic_id',
                'id',
                'id',
            )
                ->withPivot('position')
                ->select(Title::catalogCardColumns())
                ->with(Title::catalogCardRelations())
                ->publishedCatalog()
                ->orderBy('name_basic_known_for_titles.position')
                ->orderBy(Title::catalogColumn('name'));
        }

        return $this->belongsToMany(Title::class, 'credits')
            ->whereNull('credits.deleted_at')
            ->withPivot([
                'department',
                'job',
                'character_name',
                'billing_order',
                'is_principal',
                'credited_as',
            ])
            ->orderByPivot('is_principal', 'desc')
            ->orderByPivot('billing_order')
            ->orderBy('titles.name');
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable')->ordered();
    }

    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable')->latest('created_at');
    }

    public function awardNominations(): Relation
    {
        if (static::usesCatalogOnlySchema()) {
            return $this->belongsToMany(
                AwardNomination::class,
                'movie_award_nomination_nominees',
                'name_basic_id',
                'movie_award_nomination_id',
                'id',
                'id',
            );
        }

        return $this->hasMany(AwardNomination::class);
    }

    /**
     * @return list<string>
     */
    public function resolvedAlternateNames(): array
    {
        $resolvedNames = $this->normalizeAlternateNamesValue($this->getAttributeFromArray('alternate_names'));

        if ($resolvedNames !== []) {
            return $resolvedNames;
        }

        $resolvedNames = $this->normalizeAlternateNamesValue($this->getAttributeFromArray('imdb_alternative_names'));

        if ($resolvedNames !== []) {
            return $resolvedNames;
        }

        return $this->normalizeAlternateNamesValue($this->getAttributeFromArray('alternativeNames'));
    }

    public function preferredHeadshot(): ?CatalogMediaAsset
    {
        return CatalogMediaAsset::preferredFrom(
            $this->allMediaAssets(),
            MediaKind::Headshot,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    /**
     * @return EloquentCollection<int, PersonProfession>
     */
    public function previewProfessions(int $limit = 2): EloquentCollection
    {
        if (! $this->relationLoaded('professions')) {
            return new EloquentCollection;
        }

        /** @var EloquentCollection<int, PersonProfession> $professions */
        $professions = $this->professions
            ->filter(fn (PersonProfession $profession): bool => filled($profession->profession))
            ->unique('profession')
            ->take($limit)
            ->values();

        return $professions;
    }

    /**
     * @return list<string>
     */
    public function professionLabels(int $limit = 2): array
    {
        $professionLabels = $this->previewProfessions($limit)
            ->pluck('profession')
            ->filter()
            ->values()
            ->all();

        if ($professionLabels !== []) {
            return $professionLabels;
        }

        return collect($this->imdb_primary_professions)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->take($limit)
            ->map(fn (string $profession): string => $this->professionMetadata($profession)['label'])
            ->values()
            ->all();
    }

    public function primaryProfessionLabel(): string
    {
        return $this->professionLabels(1)[0] ?? 'Screenbase profile';
    }

    public function secondaryProfessionLabel(): string
    {
        return collect($this->professionLabels())
            ->skip(1)
            ->implode(' · ');
    }

    public function summaryText(): ?string
    {
        $summary = $this->short_biography ?: $this->biography;

        return filled($summary) ? (string) $summary : null;
    }

    /**
     * @return SupportCollection<string, SupportCollection<int, CatalogMediaAsset>>
     */
    public function groupedMediaAssetsByKind(): SupportCollection
    {
        return $this->allMediaAssets()
            ->unique('url')
            ->values()
            ->groupBy(fn (CatalogMediaAsset $mediaAsset): string => $mediaAsset->kind->value);
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
            return filled($this->attributes['primaryname'] ?? $this->attributes['displayName'] ?? null);
        }

        return false;
    }

    public function getNameAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        return (string) ($this->attributes['primaryname'] ?? $this->attributes['displayName'] ?? $this->attributes['imdb_id'] ?? 'Unknown person');
    }

    public function getAlternateNamesAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $names = $this->resolvedAlternateNames();

        return $names !== [] ? implode(' | ', $names) : null;
    }

    public function getShortBiographyAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $biography = $this->attributes['biography'] ?? null;

        return filled($biography) ? (string) $biography : null;
    }

    /**
     * @return list<string>
     */
    public function getImdbAlternativeNamesAttribute(mixed $value): array
    {
        $resolvedNames = $this->normalizeAlternateNamesValue($value);

        if ($resolvedNames !== []) {
            return $resolvedNames;
        }

        return $this->resolvedAlternateNames();
    }

    /**
     * @return list<string>
     */
    public function getImdbPrimaryProfessionsAttribute(mixed $value): array
    {
        $resolvedProfessions = $this->normalizeAlternateNamesValue($value);

        if ($resolvedProfessions !== []) {
            return $resolvedProfessions;
        }

        return $this->normalizeAlternateNamesValue($this->getAttributeFromArray('primaryProfessions'));
    }

    public function getKnownForDepartmentAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : $this->resolvedKnownForDepartment();
    }

    public function getBirthPlaceAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $birthLocation = $this->attributes['birthLocation'] ?? null;

        return filled($birthLocation) ? (string) $birthLocation : null;
    }

    public function getDeathPlaceAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $deathLocation = $this->attributes['deathLocation'] ?? null;

        return filled($deathLocation) ? (string) $deathLocation : null;
    }

    public function getNationalityAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    public function getPopularityRankAttribute(mixed $value): ?int
    {
        if ($value !== null) {
            return (int) $value;
        }

        $selectedValue = $this->getAttributeFromArray('current_rank');

        if ($selectedValue !== null) {
            return (int) $selectedValue;
        }

        if ($this->relationLoaded('meterRanking')) {
            $meterRanking = $this->getRelation('meterRanking');

            if ($meterRanking instanceof NameBasicMeterRanking) {
                return $meterRanking->current_rank;
            }
        }

        return null;
    }

    public function getMetaTitleAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    public function getMetaDescriptionAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }

    public function creditsCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('credits_count');

        if ($selectedValue !== null) {
            return (int) $selectedValue;
        }

        if ($this->relationLoaded('credits')) {
            return $this->credits->count();
        }

        return 0;
    }

    public function awardNominationsCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('award_nominations_count');

        if ($selectedValue !== null) {
            return (int) $selectedValue;
        }

        if ($this->relationLoaded('awardNominations')) {
            return $this->awardNominations->count();
        }

        return 0;
    }

    public function popularityRankBadgeLabel(): ?string
    {
        $rank = $this->popularity_rank;

        if (! is_int($rank) || $rank < 1) {
            return null;
        }

        return 'Rank #'.Number::format($rank);
    }

    public function awardNominationsBadgeLabel(): ?string
    {
        $count = $this->awardNominationsCount();

        if ($count < 1) {
            return null;
        }

        return Number::format($count).' '.Str::plural('award nomination', $count);
    }

    public function creditsBadgeLabel(): string
    {
        return Number::format($this->creditsCount()).' credits';
    }

    public function setNconstAttribute(?string $value): void
    {
        $this->attributes['imdb_id'] = $value;
    }

    public function setPrimarynameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function setDisplayNameAttribute(?string $value): void
    {
        if (! filled($this->attributes['name'] ?? null) && filled($value)) {
            $this->attributes['name'] = $value;
        }
    }

    public function setAlternativeNamesAttribute(?string $value): void
    {
        $this->attributes['alternate_names'] = $value;
    }

    public function setPrimaryProfessionsAttribute(mixed $value): void
    {
        $this->attributes['imdb_primary_professions'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getNconstAttribute(): ?string
    {
        return $this->imdb_id;
    }

    public function getPrimarynameAttribute(): string
    {
        return $this->name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    private function normalizeAlternateNamesValue(mixed $value): array
    {
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    $value = $decoded;
                }
            } catch (\JsonException) {
                // Fall back to pipe/comma-delimited parsing for legacy strings.
            }
        }

        if (is_array($value)) {
            return collect($value)
                ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
                ->map(fn (string $item): string => trim($item))
                ->unique()
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/\s*[|,]\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolvedKnownForDepartment(): ?string
    {
        $primaryProfession = $this->previewProfessions(1)
            ->pluck('profession')
            ->filter()
            ->first();

        if (is_string($primaryProfession) && $primaryProfession !== '') {
            return $this->professionMetadata($primaryProfession)['department'];
        }

        $imdbProfession = $this->imdb_primary_professions[0] ?? null;

        if (is_string($imdbProfession) && $imdbProfession !== '') {
            return $this->professionMetadata($imdbProfession)['department'];
        }

        return null;
    }

    /**
     * @return array{label: string, department: string}
     */
    private function professionMetadata(string $rawProfession): array
    {
        $normalized = Str::of($rawProfession)
            ->replace(['_', '-'], ' ')
            ->trim()
            ->lower()
            ->toString();

        return match ($normalized) {
            'actor', 'actress', 'self', 'archive footage', 'archive sound' => ['label' => Str::title($normalized), 'department' => 'Cast'],
            'director' => ['label' => 'Director', 'department' => 'Directing'],
            'writer', 'screenplay', 'story', 'creator' => ['label' => 'Writer', 'department' => 'Writing'],
            'producer', 'executive producer', 'associate producer', 'co producer' => ['label' => 'Producer', 'department' => 'Production'],
            'editor', 'editorial department' => ['label' => 'Editor', 'department' => 'Editing'],
            'composer', 'music department', 'soundtrack' => ['label' => 'Composer', 'department' => 'Music'],
            'cinematographer', 'camera department' => ['label' => 'Cinematographer', 'department' => 'Camera'],
            default => ['label' => Str::title($normalized), 'department' => 'Crew'],
        };
    }

    /**
     * @return SupportCollection<int, CatalogMediaAsset>
     */
    private function allMediaAssets(): SupportCollection
    {
        $assets = collect();

        if ($this->relationLoaded('mediaAssets')) {
            $assets = $assets->merge(
                $this->mediaAssets
                    ->filter(fn (mixed $asset): bool => $asset instanceof MediaAsset)
                    ->map(fn (MediaAsset $mediaAsset): CatalogMediaAsset => $mediaAsset->toCatalogMediaAsset()),
            );
        }

        if (filled($this->attributes['primaryImage_url'] ?? null)) {
            $assets->push(CatalogMediaAsset::fromCatalog([
                'kind' => MediaKind::Headshot,
                'url' => $this->attributes['primaryImage_url'],
                'alt_text' => $this->name,
                'width' => $this->attributes['primaryImage_width'] ?? null,
                'height' => $this->attributes['primaryImage_height'] ?? null,
                'is_primary' => true,
                'position' => 0,
            ]));
        }

        if ($this->relationLoaded('personImages')) {
            $assets = $assets->merge(
                $this->personImages
                    ->filter(fn (mixed $image): bool => $image instanceof PersonImage && filled($image->url))
                    ->map(function (PersonImage $personImage): CatalogMediaAsset {
                        return CatalogMediaAsset::fromCatalog([
                            'kind' => $personImage->kind,
                            'url' => $personImage->url,
                            'alt_text' => $this->name,
                            'width' => $personImage->width,
                            'height' => $personImage->height,
                            'is_primary' => $personImage->is_primary,
                            'position' => $personImage->position,
                        ]);
                    }),
            );
        }

        return $assets
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->unique('url')
            ->values();
    }

    private function normalizeCatalogOnlyAttributesForPersistence(): void
    {
        $name = $this->attributes['primaryname'] ?? $this->attributes['displayName'] ?? $this->attributes['name'] ?? null;
        $imdbId = $this->attributes['imdb_id'] ?? $this->attributes['nconst'] ?? null;

        $this->attributes['primaryname'] = $name;
        $this->attributes['displayName'] = $this->attributes['displayName'] ?? $name;
        $this->attributes['imdb_id'] = $imdbId;
        $this->attributes['nconst'] = $imdbId;
        $this->attributes['alternativeNames'] = $this->attributes['alternativeNames'] ?? $this->attributes['alternate_names'] ?? null;
        $this->attributes['primaryprofession'] = $this->attributes['primaryprofession']
            ?? $this->attributes['known_for_department']
            ?? null;
        $this->attributes['birthLocation'] = $this->attributes['birthLocation'] ?? $this->attributes['birth_place'] ?? null;
        $this->attributes['deathLocation'] = $this->attributes['deathLocation'] ?? $this->attributes['death_place'] ?? null;

        foreach ([
            'name',
            'alternate_names',
            'slug',
            'short_biography',
            'known_for_department',
            'birth_date',
            'death_date',
            'birth_place',
            'death_place',
            'nationality',
            'popularity_rank',
            'meta_title',
            'meta_description',
            'search_keywords',
            'is_published',
            'imdb_alternative_names',
            'imdb_primary_professions',
            'imdb_payload',
        ] as $attribute) {
            unset($this->attributes[$attribute]);
        }
    }
}
