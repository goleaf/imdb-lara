<?php

namespace App\Models;

use App\Enums\MediaKind;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'primaryProfessions',
        'birthLocation',
        'deathLocation',
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
            $person->slug = $person->slug ?: Str::slug($person->name ?: 'unknown-person');
        });
    }

    /**
     * @return list<string>
     */
    public static function directoryColumns(): array
    {
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
            'awardNominations:id,title_id,person_id,award_event_id,award_category_id,details,credited_name,is_winner,sort_order',
            'awardNominations.title:id,name,slug,title_type,release_year',
            'awardNominations.awardCategory:id,name',
            'awardNominations.awardEvent:id,name,year',
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

        return $query->where(function (Builder $personQuery) use ($value): void {
            $personQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $personQuery->orWhereKey((int) $value);
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
        return $query->where('people.is_published', true);
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        if (preg_match('/^nm\d+$/i', $search) === 1) {
            return $query->where('people.imdb_id', $search);
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
        return $query->whereHas(
            'professions',
            fn (Builder $builder) => $builder->where('profession', $profession),
        );
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class)->ordered();
    }

    public function professions(): HasMany
    {
        return $this->hasMany(PersonProfession::class)->ordered();
    }

    public function professionTerms(): HasMany
    {
        return $this->professions();
    }

    public function knownForTitles(): BelongsToMany
    {
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

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }

    /**
     * @return list<string>
     */
    public function resolvedAlternateNames(): array
    {
        $alternateNames = $this->alternate_names;

        if (! is_string($alternateNames) || trim($alternateNames) === '') {
            return [];
        }

        return collect(preg_split('/\s*[|,]\s*/', trim($alternateNames), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    /**
     * @return list<string>
     */
    public function getImdbAlternativeNamesAttribute(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return $this->resolvedAlternateNames();
    }

    /**
     * @return list<string>
     */
    public function getImdbPrimaryProfessionsAttribute(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return collect($this->attributes['primaryProfessions'] ?? null)
            ->filter(fn (mixed $item): bool => is_string($item) && $item !== '')
            ->values()
            ->all();
    }

    public function getKnownForDepartmentAttribute(?string $value): ?string
    {
        return filled($value) ? (string) $value : $this->resolvedKnownForDepartment();
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
