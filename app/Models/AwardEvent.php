<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AwardEvent extends ImdbModel
{
    protected $table = 'award_events';

    protected $primaryKey = 'imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'name',
    ];

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
        if (preg_match('/-(?P<id>[a-z0-9]+)$/', (string) $value, $matches) === 1) {
            return $query->where('imdb_id', $matches['id']);
        }

        return $query->where('imdb_id', (string) $value);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class, 'event_imdb_id', 'imdb_id');
    }

    public function movieAwardNominations(): HasMany
    {
        return $this->hasMany(MovieAwardNomination::class, 'event_imdb_id', 'imdb_id');
    }

    public function getSlugAttribute(): string
    {
        return Str::slug((string) $this->name).'-'.$this->imdb_id;
    }
}
