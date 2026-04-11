<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credit extends Model
{
    /**
     * @var list<string>
     */
    public const CAST_CATEGORIES = [
        'actor',
        'actress',
        'archive_footage',
        'self',
    ];

    protected $connection = 'imdb_mysql';

    protected $table = 'name_credits';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'movie_id',
        'category',
        'episode_count',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'movie_id' => 'integer',
            'episode_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'name_basic_id', 'id');
    }

    public function nameCreditCharacters(): HasMany
    {
        return $this->hasMany(NameCreditCharacter::class, 'name_credit_id', 'id')->orderBy('position');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name_credits.position');
    }

    public function scopeCast(Builder $query): Builder
    {
        return $query->whereIn('name_credits.category', self::CAST_CATEGORIES);
    }

    public function scopeCrew(Builder $query): Builder
    {
        return $query->where(function (Builder $crewQuery): void {
            $crewQuery
                ->whereNull('name_credits.category')
                ->orWhereNotIn('name_credits.category', self::CAST_CATEGORIES);
        });
    }

    public function scopeWithPersonPreview(Builder $query): Builder
    {
        return $query->with([
            'person' => fn ($personQuery) => $personQuery
                ->selectDirectoryColumns(),
        ]);
    }

    public function getTitleIdAttribute(): int
    {
        return (int) $this->movie_id;
    }

    public function getPersonIdAttribute(): int
    {
        return (int) $this->name_basic_id;
    }

    public function getDepartmentAttribute(): string
    {
        return match ($this->category) {
            'actor', 'actress', 'archive_footage', 'self' => 'Cast',
            'director' => 'Directing',
            'writer' => 'Writing',
            'producer', 'executive' => 'Production',
            'composer', 'music_department', 'soundtrack' => 'Music',
            'cinematographer' => 'Camera',
            'editor' => 'Editing',
            'thanks' => 'Thanks',
            default => str((string) $this->category)->headline()->toString(),
        };
    }

    public function getJobAttribute(): string
    {
        return str((string) $this->category)->headline()->toString();
    }

    public function getCharacterNameAttribute(): ?string
    {
        $rawCharacterName = $this->getAttributeFromArray('character_name');

        if (is_string($rawCharacterName) && trim($rawCharacterName) !== '') {
            return trim($rawCharacterName);
        }

        if (! $this->relationLoaded('nameCreditCharacters')) {
            return null;
        }

        $characterNames = $this->nameCreditCharacters
            ->pluck('character_name')
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values();

        return $characterNames->isEmpty() ? null : $characterNames->implode(' / ');
    }

    public function getBillingOrderAttribute(): int
    {
        return (int) ($this->position ?? 0);
    }

    public function getIsPrincipalAttribute(): bool
    {
        return (int) ($this->position ?? 0) <= 5;
    }

    public function getCreditedAsAttribute(): ?string
    {
        return null;
    }

    public function getImdbSourceGroupAttribute(): ?string
    {
        return $this->category ? (string) $this->category : null;
    }
}
