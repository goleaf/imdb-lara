<?php

namespace App\Models;

use Database\Factories\AwardEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AwardEvent extends Model
{
    /** @use HasFactory<AwardEventFactory> */
    use HasFactory;

    protected $table = 'award_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'award_id',
        'imdb_id',
        'name',
        'slug',
        'year',
        'edition',
        'event_date',
        'location',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'award_id' => 'integer',
            'year' => 'integer',
            'event_date' => 'date',
        ];
    }

    protected static function usesCatalogOnlySchema(): bool
    {
        return Title::usesCatalogOnlySchema();
    }

    public function getConnectionName(): ?string
    {
        return static::usesCatalogOnlySchema() ? 'imdb_mysql' : null;
    }

    public function usesTimestamps(): bool
    {
        return static::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
    }

    public function getKeyName(): string
    {
        return static::usesCatalogOnlySchema() ? 'imdb_id' : parent::getKeyName();
    }

    public function getIncrementing(): bool
    {
        return ! static::usesCatalogOnlySchema();
    }

    public function getKeyType(): string
    {
        return static::usesCatalogOnlySchema() ? 'string' : parent::getKeyType();
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
        if (static::usesCatalogOnlySchema()) {
            if (preg_match('/-(?P<id>[a-z0-9]+)$/', (string) $value, $matches) === 1) {
                return $query->where('imdb_id', $matches['id']);
            }

            return $query->where('imdb_id', (string) $value);
        }

        return $query->where(function ($awardEventQuery) use ($value): void {
            $awardEventQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $awardEventQuery->orWhereKey((int) $value);
            }
        });
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(
            AwardNomination::class,
            static::usesCatalogOnlySchema() ? 'event_imdb_id' : 'award_event_id',
            static::usesCatalogOnlySchema() ? 'imdb_id' : 'id',
        )->ordered();
    }

    public function movieAwardNominations(): HasMany
    {
        return $this->nominations();
    }

    public function getSlugAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        $suffix = static::usesCatalogOnlySchema()
            ? (string) ($this->imdb_id ?? '')
            : 'e'.$this->id;

        return Str::slug((string) $this->name).'-'.$suffix;
    }
}
