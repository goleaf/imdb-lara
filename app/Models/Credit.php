<?php

namespace App\Models;

use Database\Factories\CreditFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Credit extends Model
{
    /** @use HasFactory<CreditFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    public const CAST_CATEGORIES = [
        'actor',
        'actress',
        'archive_footage',
        'self',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
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
        'name_basic_id',
        'movie_id',
        'category',
        'position',
        'episode_count',
    ];

    protected function casts(): array
    {
        return [
            'title_id' => 'integer',
            'person_id' => 'integer',
            'name_basic_id' => 'integer',
            'movie_id' => 'integer',
            'billing_order' => 'integer',
            'position' => 'integer',
            'episode_count' => 'integer',
            'is_principal' => 'boolean',
            'person_profession_id' => 'integer',
            'episode_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $credit): void {
            if (! self::usesCatalogOnlySchema()) {
                return;
            }

            $credit->normalizeCatalogOnlyAttributesForPersistence();
        });
    }

    public static function usesCatalogOnlySchema(): bool
    {
        return Person::usesCatalogOnlySchema();
    }

    /**
     * @return list<string>
     */
    public static function projectedColumns(): array
    {
        if (self::usesCatalogOnlySchema()) {
            return [
                'name_credits.id',
                'name_credits.movie_id',
                'name_credits.name_basic_id',
                'name_credits.category',
                'name_credits.episode_count',
                'name_credits.position',
            ];
        }

        return [
            'credits.id',
            'credits.title_id',
            'credits.person_id',
            'credits.department',
            'credits.job',
            'credits.character_name',
            'credits.billing_order',
            'credits.is_principal',
            'credits.person_profession_id',
            'credits.episode_id',
            'credits.credited_as',
            'credits.imdb_source_group',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function projectedRelations(): array
    {
        if (! self::usesCatalogOnlySchema()) {
            return [];
        }

        return [
            'nameCreditCharacters:name_credit_id,position,character_name',
        ];
    }

    public static function qualifiedColumn(string $localColumn): string
    {
        if (! self::usesCatalogOnlySchema()) {
            return 'credits.'.$localColumn;
        }

        return match ($localColumn) {
            'title_id' => 'name_credits.movie_id',
            'person_id' => 'name_credits.name_basic_id',
            'billing_order' => 'name_credits.position',
            'category' => 'name_credits.category',
            'episode_count' => 'name_credits.episode_count',
            default => 'name_credits.'.$localColumn,
        };
    }

    public function newQuery(): Builder
    {
        $query = parent::newQuery();

        if (self::usesCatalogOnlySchema()) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    public function getTable(): string
    {
        return self::usesCatalogOnlySchema() ? 'name_credits' : parent::getTable();
    }

    public function getConnectionName(): ?string
    {
        return self::usesCatalogOnlySchema() ? 'imdb_mysql' : parent::getConnectionName();
    }

    public function usesTimestamps(): bool
    {
        return self::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, self::usesCatalogOnlySchema() ? 'movie_id' : 'title_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, self::usesCatalogOnlySchema() ? 'name_basic_id' : 'person_id');
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(PersonProfession::class, 'person_profession_id');
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function nameCreditCharacters(): HasMany
    {
        return $this->hasMany(NameCreditCharacter::class, 'name_credit_id', 'id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (self::usesCatalogOnlySchema()) {
            return $query
                ->orderBy('name_credits.position')
                ->orderBy('name_credits.id');
        }

        return $query
            ->orderBy('credits.billing_order')
            ->orderBy('credits.id');
    }

    public function scopeCast(Builder $query): Builder
    {
        if (self::usesCatalogOnlySchema()) {
            return $query->whereIn('name_credits.category', self::CAST_CATEGORIES);
        }

        return $query->where('credits.department', 'Cast');
    }

    public function scopeCrew(Builder $query): Builder
    {
        if (self::usesCatalogOnlySchema()) {
            return $query->whereNotIn('name_credits.category', self::CAST_CATEGORIES);
        }

        return $query->where('credits.department', '!=', 'Cast');
    }

    /**
     * @param  list<string>  $departments
     */
    public function scopeInDepartments(Builder $query, array $departments): Builder
    {
        if (! self::usesCatalogOnlySchema()) {
            return $query->whereIn('credits.department', $departments);
        }

        return $query->whereIn('name_credits.category', collect($departments)
            ->flatMap(fn (string $department): array => self::categoriesForDepartment($department))
            ->unique()
            ->values()
            ->all());
    }

    public function scopeWithPersonPreview(Builder $query): Builder
    {
        return $query->with([
            ...self::projectedRelations(),
            'person' => fn ($personQuery) => $personQuery
                ->selectDirectoryColumns()
                ->withDirectoryRelations()
                ->withDirectoryMetrics(),
        ]);
    }

    public function getTitleIdAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['movie_id']) ? (int) $this->attributes['movie_id'] : null);
    }

    public function getMovieIdAttribute(): int
    {
        return (int) ($this->attributes['movie_id'] ?? $this->attributes['title_id'] ?? 0);
    }

    public function getPersonIdAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['name_basic_id']) ? (int) $this->attributes['name_basic_id'] : null);
    }

    public function getNameBasicIdAttribute(): int
    {
        return (int) ($this->attributes['name_basic_id'] ?? $this->attributes['person_id'] ?? 0);
    }

    public function getDepartmentAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        return self::departmentForCategory($this->category);
    }

    public function getJobAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        return self::labelForCategory($this->category);
    }

    public function getCharacterNameAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $loadedCharacters = $this->getRelationValue('nameCreditCharacters');

        if ($loadedCharacters instanceof Collection) {
            return $loadedCharacters
                ->pluck('character_name')
                ->filter(fn (mixed $characterName): bool => is_string($characterName) && $characterName !== '')
                ->implode(' | ') ?: null;
        }

        return null;
    }

    public function getCategoryAttribute(): string
    {
        if (filled($this->attributes['category'] ?? null)) {
            return (string) $this->attributes['category'];
        }

        if (filled($this->imdb_source_group)) {
            return (string) $this->imdb_source_group;
        }

        return self::categoryForDepartment($this->attributes['department'] ?? null, $this->attributes['job'] ?? null);
    }

    public function getBillingOrderAttribute(mixed $value): ?int
    {
        if ($value !== null) {
            return (int) $value;
        }

        return isset($this->attributes['position']) ? (int) $this->attributes['position'] : null;
    }

    public function getIsPrincipalAttribute(mixed $value): bool
    {
        if ($value !== null) {
            return (bool) $value;
        }

        return in_array($this->category, self::CAST_CATEGORIES, true)
            && (int) ($this->attributes['position'] ?? 0) <= 5
            && (int) ($this->attributes['position'] ?? 0) > 0;
    }

    public function getPositionAttribute(mixed $value): int
    {
        return (int) ($value ?? $this->attributes['billing_order'] ?? 0);
    }

    public function setPersonIdAttribute(mixed $value): void
    {
        $this->attributes[self::usesCatalogOnlySchema() ? 'name_basic_id' : 'person_id'] = $value;
    }

    public function setNameBasicIdAttribute(mixed $value): void
    {
        $this->attributes[self::usesCatalogOnlySchema() ? 'name_basic_id' : 'person_id'] = $value;
    }

    public function setTitleIdAttribute(mixed $value): void
    {
        $this->attributes[self::usesCatalogOnlySchema() ? 'movie_id' : 'title_id'] = $value;
    }

    public function setMovieIdAttribute(mixed $value): void
    {
        $this->attributes[self::usesCatalogOnlySchema() ? 'movie_id' : 'title_id'] = $value;
    }

    public function setCategoryAttribute(?string $value): void
    {
        if (! filled($value)) {
            return;
        }

        $this->attributes['category'] = $value;
        $this->attributes['imdb_source_group'] = $value;
        $this->attributes['job'] = $this->attributes['job'] ?? self::labelForCategory((string) $value);
        $this->attributes['department'] = self::departmentForCategory((string) $value);
    }

    public function setPositionAttribute(mixed $value): void
    {
        $this->attributes[self::usesCatalogOnlySchema() ? 'position' : 'billing_order'] = $value;
    }

    private static function categoryForDepartment(?string $department, ?string $job = null): string
    {
        return match ($department) {
            'Cast' => 'actor',
            'Directing' => 'director',
            'Writing' => 'writer',
            'Production' => 'producer',
            'Music' => 'composer',
            'Camera' => 'cinematographer',
            'Editing' => 'editor',
            default => Str::of((string) $job)->replace('-', ' ')->snake()->toString(),
        };
    }

    private static function departmentForCategory(?string $category): string
    {
        return match (Str::of((string) $category)->replace('_', ' ')->lower()->toString()) {
            'actor', 'actress', 'archive footage', 'self' => 'Cast',
            'director' => 'Directing',
            'writer' => 'Writing',
            'producer', 'executive producer' => 'Production',
            'composer', 'soundtrack' => 'Music',
            'cinematographer' => 'Camera',
            'editor' => 'Editing',
            default => 'Crew',
        };
    }

    private static function labelForCategory(?string $category): string
    {
        return match (Str::of((string) $category)->replace('_', ' ')->lower()->toString()) {
            'actor' => 'Actor',
            'actress' => 'Actress',
            default => Str::headline((string) $category),
        };
    }

    /**
     * @return list<string>
     */
    private static function categoriesForDepartment(string $department): array
    {
        return match ($department) {
            'Cast' => self::CAST_CATEGORIES,
            'Directing' => ['director'],
            'Writing' => ['writer'],
            'Production' => ['producer', 'executive_producer'],
            default => [self::categoryForDepartment($department)],
        };
    }

    private function normalizeCatalogOnlyAttributesForPersistence(): void
    {
        $this->attributes['movie_id'] = $this->attributes['movie_id']
            ?? $this->attributes['title_id']
            ?? null;
        $this->attributes['name_basic_id'] = $this->attributes['name_basic_id']
            ?? $this->attributes['person_id']
            ?? null;
        $this->attributes['category'] = $this->attributes['category']
            ?? self::categoryForDepartment(
                $this->attributes['department'] ?? null,
                $this->attributes['job'] ?? null,
            );
        $this->attributes['position'] = $this->attributes['position']
            ?? $this->attributes['billing_order']
            ?? null;

        unset(
            $this->attributes['title_id'],
            $this->attributes['person_id'],
            $this->attributes['department'],
            $this->attributes['job'],
            $this->attributes['character_name'],
            $this->attributes['billing_order'],
            $this->attributes['is_principal'],
            $this->attributes['person_profession_id'],
            $this->attributes['episode_id'],
            $this->attributes['credited_as'],
            $this->attributes['imdb_source_group'],
            $this->attributes['deleted_at'],
        );
    }
}
