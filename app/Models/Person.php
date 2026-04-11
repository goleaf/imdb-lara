<?php

namespace App\Models;

use App\Enums\MediaKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Person extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'name_basics';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nconst',
        'primaryname',
        'birthyear',
        'deathyear',
        'primaryprofession',
        'knownfortitles',
        'alternativeNames',
        'biography',
        'birthDate_day',
        'birthDate_month',
        'birthDate_year',
        'birthLocation',
        'birthName',
        'deathDate_day',
        'deathDate_month',
        'deathDate_year',
        'deathLocation',
        'deathReason',
        'displayName',
        'heightCm',
        'imdb_id',
        'primaryImage_height',
        'primaryImage_url',
        'primaryImage_width',
        'primaryProfessions',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'birthyear' => 'integer',
            'deathyear' => 'integer',
            'birthDate_day' => 'integer',
            'birthDate_month' => 'integer',
            'birthDate_year' => 'integer',
            'deathDate_day' => 'integer',
            'deathDate_month' => 'integer',
            'deathDate_year' => 'integer',
            'heightCm' => 'integer',
            'primaryImage_height' => 'integer',
            'primaryImage_width' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public static function directoryColumns(): array
    {
        return [
            'name_basics.id',
            'name_basics.nconst',
            'name_basics.imdb_id',
            'name_basics.primaryname',
            'name_basics.displayName',
            'name_basics.alternativeNames',
            'name_basics.primaryProfessions',
            'name_basics.biography',
            'name_basics.birthLocation',
            'name_basics.deathLocation',
            'name_basics.primaryImage_url',
            'name_basics.primaryImage_width',
            'name_basics.primaryImage_height',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function directoryRelations(): array
    {
        return [
            'personImages:name_basic_id,position,url,width,height,type',
            'professionTerms:id,name',
            'meterRanking:name_basic_id,current_rank,change_direction,difference',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function detailRelations(): array
    {
        return [
            ...self::directoryRelations(),
            'alternativeNameRecords:name_basic_id,alternative_name,position',
            'knownForTitles' => fn ($query) => $query
                ->selectCatalogCardColumns()
                ->publishedCatalog()
                ->withCatalogCardRelations(),
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
        if (preg_match('/-(?P<imdb>nm\d+)$/', (string) $value, $matches) === 1) {
            return $query->where('nconst', $matches['imdb']);
        }

        return $query
            ->where('nconst', (string) $value)
            ->orWhere('imdb_id', (string) $value);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query;
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^nm\d+$/i', $search) === 1) {
            return $query->where(function (Builder $personQuery) use ($search): void {
                $personQuery
                    ->where('nconst', $search)
                    ->orWhere('imdb_id', $search);
            });
        }

        return $query->where(function (Builder $personQuery) use ($search): void {
            $personQuery
                ->where('primaryname', 'like', '%'.$search.'%')
                ->orWhere('displayName', 'like', '%'.$search.'%')
                ->orWhere('nconst', 'like', '%'.$search.'%')
                ->orWhere('imdb_id', 'like', '%'.$search.'%')
                ->orWhere('alternativeNames', 'like', '%'.$search.'%')
                ->orWhere('biography', 'like', '%'.$search.'%');
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
        return $query
            ->withCount(['credits', 'awardNominations'])
            ->withExists('meterRanking');
    }

    public function scopeWithDetailRelations(Builder $query): Builder
    {
        return $query->with(self::detailRelations());
    }

    public function scopeInProfession(Builder $query, string $profession): Builder
    {
        return $query->whereHas(
            'professionTerms',
            fn (Builder $builder) => $builder->where('professions.name', $profession),
        );
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class, 'name_basic_id', 'id')->orderBy('position');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(NameRelationship::class, 'name_basic_id', 'id')->orderBy('position');
    }

    public function relationshipsAbout(): HasMany
    {
        return $this->hasMany(NameRelationship::class, 'related_name_basic_id', 'id')->orderBy('position');
    }

    public function professions(): HasMany
    {
        return $this->hasMany(PersonProfession::class, 'name_basic_id', 'id')->orderBy('position');
    }

    public function knownForTitleLinks(): HasMany
    {
        return $this->hasMany(NameBasicKnownForTitle::class, 'name_basic_id', 'id')->orderBy('position');
    }

    public function knownForTitles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'name_basic_known_for_titles', 'name_basic_id', 'title_basic_id', 'id', 'id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function professionTerms(): BelongsToMany
    {
        return $this->belongsToMany(Profession::class, 'name_basic_professions', 'name_basic_id', 'profession_id', 'id', 'id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function personImages(): HasMany
    {
        return $this->hasMany(PersonImage::class, 'name_basic_id', 'id')->orderBy('position');
    }

    public function alternativeNameRecords(): HasMany
    {
        return $this->hasMany(NameBasicAlternativeName::class, 'name_basic_id', 'id')
            ->orderBy('position')
            ->orderBy('alternative_name');
    }

    public function meterRanking(): HasOne
    {
        return $this->hasOne(NameBasicMeterRanking::class, 'name_basic_id', 'id');
    }

    public function awardNominations(): BelongsToMany
    {
        return $this->belongsToMany(AwardNomination::class, 'movie_award_nomination_nominees', 'name_basic_id', 'movie_award_nomination_id', 'id', 'id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function imdbPayloadSection(string $key): ?array
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function resolvedAlternateNames(): array
    {
        $alternateNames = $this->alternativeNames;

        if (! is_string($alternateNames) || trim($alternateNames) === '') {
            return [];
        }

        $trimmed = trim($alternateNames);
        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return collect($decoded)
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->unique()
                ->values()
                ->all();
        }

        return collect(preg_split('/\s*[|,]\s*/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function preferredHeadshot(): ?CatalogMediaAsset
    {
        $assets = collect();

        if ($this->relationLoaded('personImages')) {
            $assets = $assets->concat($this->personImages);
        }

        if (filled($this->primaryImage_url)) {
            $assets->prepend(CatalogMediaAsset::fromCatalog([
                'kind' => MediaKind::Headshot,
                'url' => $this->primaryImage_url,
                'alt_text' => $this->name,
                'width' => $this->primaryImage_width,
                'height' => $this->primaryImage_height,
                'position' => 0,
                'is_primary' => true,
            ]));
        }

        return CatalogMediaAsset::preferredFrom(
            $assets,
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
        if ($this->relationLoaded('professionTerms')) {
            return $this->professionTerms
                ->pluck('name')
                ->filter()
                ->unique()
                ->take($limit)
                ->values()
                ->all();
        }

        $professionLabels = $this->previewProfessions($limit)
            ->pluck('profession')
            ->filter()
            ->values()
            ->all();

        if ($professionLabels !== []) {
            return $professionLabels;
        }

        return collect($this->imdb_primary_professions)
            ->map(fn (string $profession): string => $this->professionMetadata($profession)['label'])
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    public function primaryProfessionLabel(): string
    {
        return $this->professionLabels(1)[0] ?? ($this->resolvedKnownForDepartment() ?: 'Screenbase profile');
    }

    public function secondaryProfessionLabel(): string
    {
        return collect($this->professionLabels())
            ->skip(1)
            ->implode(' · ');
    }

    public function summaryText(): ?string
    {
        $summary = $this->getAttributeFromArray('short_biography') ?: $this->biography;

        return filled($summary) ? (string) $summary : null;
    }

    /**
     * @return SupportCollection<string, SupportCollection<int, CatalogMediaAsset>>
     */
    public function groupedMediaAssetsByKind(): SupportCollection
    {
        $assets = collect();

        if ($this->relationLoaded('personImages')) {
            $assets = $assets->concat($this->personImages);
        }

        if ($headshot = $this->preferredHeadshot()) {
            $assets->prepend($headshot);
        }

        return $assets
            ->filter(fn (mixed $asset): bool => $asset instanceof CatalogMediaAsset)
            ->unique('url')
            ->values()
            ->groupBy(fn (CatalogMediaAsset $mediaAsset): string => $mediaAsset->kind->value);
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name).'-'.($this->nconst ?: $this->imdb_id ?: $this->id);
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->displayName ?: $this->primaryname ?: $this->nconst ?: 'Unknown person');
    }

    public function getAlternateNamesAttribute(): ?string
    {
        $names = $this->resolvedAlternateNames();

        return $names !== [] ? implode(' | ', $names) : null;
    }

    /**
     * @return list<string>
     */
    public function getImdbAlternativeNamesAttribute(): array
    {
        return $this->resolvedAlternateNames();
    }

    /**
     * @return list<string>
     */
    public function getImdbPrimaryProfessionsAttribute(): array
    {
        if (! is_string($this->primaryProfessions) || trim($this->primaryProfessions) === '') {
            return [];
        }

        $decoded = json_decode($this->primaryProfessions, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }

    public function getBiographyAttribute(): ?string
    {
        return filled($this->attributes['biography'] ?? null) ? (string) $this->attributes['biography'] : null;
    }

    public function getShortBiographyAttribute(): ?string
    {
        return $this->biography
            ? Str::of($this->biography)->limit(220)->toString()
            : null;
    }

    public function getKnownForDepartmentAttribute(): ?string
    {
        return $this->resolvedKnownForDepartment();
    }

    public function getBirthDateAttribute(): mixed
    {
        return null;
    }

    public function getDeathDateAttribute(): mixed
    {
        return null;
    }

    public function getBirthPlaceAttribute(): ?string
    {
        return filled($this->birthLocation) ? (string) $this->birthLocation : null;
    }

    public function getDeathPlaceAttribute(): ?string
    {
        return filled($this->deathLocation) ? (string) $this->deathLocation : null;
    }

    public function getNationalityAttribute(): ?string
    {
        $birthPlace = $this->birth_place;

        if (! filled($birthPlace)) {
            return null;
        }

        return Str::of((string) $birthPlace)->afterLast(',')->trim()->toString();
    }

    public function getPopularityRankAttribute(): ?int
    {
        $selectedValue = $this->getAttributeFromArray('popularity_rank');

        if ($selectedValue !== null) {
            return (int) $selectedValue;
        }

        if ($this->relationLoaded('meterRanking')) {
            return $this->meterRanking?->current_rank;
        }

        return null;
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

    public function getMetaTitleAttribute(): string
    {
        return $this->name;
    }

    public function getMetaDescriptionAttribute(): string
    {
        return $this->summaryText() ?: 'Browse biography, credits, and career highlights for '.$this->name.'.';
    }

    public function getSearchKeywordsAttribute(): ?string
    {
        return null;
    }

    public function getIsPublishedAttribute(): bool
    {
        return true;
    }

    public function getUpdatedAtAttribute(): mixed
    {
        return null;
    }

    private function resolvedKnownForDepartment(): ?string
    {
        $storedDepartment = $this->getAttributeFromArray('known_for_department');

        if (filled($storedDepartment)) {
            return (string) $storedDepartment;
        }

        $primaryProfession = $this->primaryProfessionSeed();

        if (! filled($primaryProfession)) {
            return null;
        }

        return $this->professionMetadata((string) $primaryProfession)['department'];
    }

    private function primaryProfessionSeed(): ?string
    {
        if ($this->relationLoaded('professionTerms')) {
            $profession = $this->professionTerms
                ->pluck('name')
                ->filter()
                ->first();

            if (is_string($profession) && $profession !== '') {
                return $profession;
            }
        }

        $profession = $this->previewProfessions(1)
            ->pluck('profession')
            ->filter()
            ->first();

        if (is_string($profession) && $profession !== '') {
            return $profession;
        }

        $profession = $this->imdb_primary_professions[0] ?? null;

        return is_string($profession) && $profession !== '' ? $profession : null;
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
            'actor' => ['label' => 'Actor', 'department' => 'Cast'],
            'actress' => ['label' => 'Actress', 'department' => 'Cast'],
            'self' => ['label' => 'Self', 'department' => 'Cast'],
            'archive footage', 'archive sound' => ['label' => 'Archive', 'department' => 'Cast'],
            'director' => ['label' => 'Director', 'department' => 'Directing'],
            'writer', 'screenplay', 'story', 'creator' => ['label' => 'Writer', 'department' => 'Writing'],
            'producer', 'executive producer', 'associate producer', 'co producer' => ['label' => 'Producer', 'department' => 'Production'],
            'editor', 'editorial department' => ['label' => 'Editor', 'department' => 'Editing'],
            'composer', 'music department', 'soundtrack' => ['label' => 'Composer', 'department' => 'Music'],
            'cinematographer', 'camera department' => ['label' => 'Cinematographer', 'department' => 'Camera'],
            'art department', 'art director', 'production designer' => ['label' => 'Production Designer', 'department' => 'Art'],
            'costume designer', 'costume department' => ['label' => 'Costume Designer', 'department' => 'Costume'],
            'make up department' => ['label' => 'Make-Up', 'department' => 'Make-Up'],
            'visual effects' => ['label' => 'Visual Effects', 'department' => 'Visual Effects'],
            'animation department' => ['label' => 'Animation', 'department' => 'Animation'],
            'stunts' => ['label' => 'Stunts', 'department' => 'Stunts'],
            'script department' => ['label' => 'Script Department', 'department' => 'Writing'],
            'miscellaneous' => ['label' => 'Miscellaneous', 'department' => 'Crew'],
            default => ['label' => Str::title($normalized), 'department' => 'Crew'],
        };
    }
}
