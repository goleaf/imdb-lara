<?php

namespace App\Models;

use Database\Factories\PersonProfessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PersonProfession extends Model
{
    /** @use HasFactory<PersonProfessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'person_id',
        'department',
        'profession',
        'is_primary',
        'sort_order',
        'name_basic_id',
        'profession_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'person_id' => 'integer',
            'name_basic_id' => 'integer',
            'profession_id' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $personProfession): void {
            if (! Person::usesCatalogOnlySchema()) {
                return;
            }

            $personProfession->normalizeCatalogOnlyAttributesForPersistence();
        });
    }

    public function getTable(): string
    {
        return Person::usesCatalogOnlySchema() ? 'name_basic_professions' : parent::getTable();
    }

    public function getConnectionName(): ?string
    {
        return Person::usesCatalogOnlySchema() ? 'imdb_mysql' : parent::getConnectionName();
    }

    public function usesTimestamps(): bool
    {
        return Person::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, Person::usesCatalogOnlySchema() ? 'name_basic_id' : 'person_id');
    }

    public function professionTerm(): BelongsTo
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (Person::usesCatalogOnlySchema()) {
            return $query
                ->addSelect([
                    'profession_name_sort' => Profession::query()
                        ->select('name')
                        ->whereColumn('professions.id', 'name_basic_professions.profession_id')
                        ->limit(1),
                ])
                ->orderBy('name_basic_professions.position')
                ->orderBy('profession_name_sort')
                ->orderBy('name_basic_professions.profession_id');
        }

        return $query
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('profession')
            ->orderBy('id');
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->profession ?? '');
    }

    public function getPersonIdAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['name_basic_id']) ? (int) $this->attributes['name_basic_id'] : null);
    }

    public function getSortOrderAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['position']) ? (int) $this->attributes['position'] : null);
    }

    public function getIsPrimaryAttribute(?bool $value): bool
    {
        if ($value !== null) {
            return $value;
        }

        return (int) ($this->attributes['position'] ?? 0) === 1;
    }

    public function getProfessionAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $loadedRelation = $this->getRelationValue('professionTerm');

        if ($loadedRelation instanceof Profession && filled($loadedRelation->name)) {
            return (string) $loadedRelation->name;
        }

        return null;
    }

    public function getDepartmentAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $profession = $this->profession;

        if (! filled($profession)) {
            return null;
        }

        return match (Str::of((string) $profession)->replace(['_', '-'], ' ')->trim()->lower()->toString()) {
            'actor', 'actress', 'self', 'archive footage', 'archive sound' => 'Cast',
            'director' => 'Directing',
            'writer', 'screenplay', 'story', 'creator' => 'Writing',
            'producer', 'executive producer', 'associate producer', 'co producer' => 'Production',
            'editor', 'editorial department' => 'Editing',
            'composer', 'music department', 'soundtrack' => 'Music',
            'cinematographer', 'camera department' => 'Camera',
            default => 'Crew',
        };
    }

    private function normalizeCatalogOnlyAttributesForPersistence(): void
    {
        $professionName = filled($this->attributes['profession'] ?? null)
            ? (string) $this->attributes['profession']
            : null;

        if (($this->attributes['profession_id'] ?? null) === null && $professionName !== null) {
            $profession = Profession::query()->firstOrCreate([
                'name' => $professionName,
            ]);

            $this->attributes['profession_id'] = $profession->getKey();
        }

        $this->attributes['name_basic_id'] = $this->attributes['name_basic_id']
            ?? $this->attributes['person_id']
            ?? null;
        $this->attributes['position'] = $this->attributes['position']
            ?? $this->attributes['sort_order']
            ?? (($this->attributes['is_primary'] ?? false) ? 1 : null);

        unset(
            $this->attributes['person_id'],
            $this->attributes['department'],
            $this->attributes['profession'],
            $this->attributes['is_primary'],
            $this->attributes['sort_order'],
        );
    }
}
