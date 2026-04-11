<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'movie_seasons';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'season',
        'episode_count',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'season' => 'integer',
            'episode_count' => 'integer',
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
        $seasonNumber = preg_match('/season-(?P<number>\d+)$/', (string) $value, $matches) === 1
            ? (int) $matches['number']
            : (int) $value;
        $series = request()->route('series');

        if ($series instanceof Title) {
            $query->where('movie_id', $series->getKey());
        }

        return $query->where('season', $seasonNumber);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'movie_id', 'movie_id')
            ->where('season', $this->season)
            ->orderBy('episode_number');
    }

    public function getIdAttribute(): string
    {
        return $this->movie_id.':'.$this->season;
    }

    public function getKey(): mixed
    {
        return $this->id;
    }

    public function getSeriesIdAttribute(): int
    {
        return (int) $this->movie_id;
    }

    public function getNameAttribute(): string
    {
        return 'Season '.$this->season_number;
    }

    public function getSlugAttribute(): string
    {
        return 'season-'.$this->season_number;
    }

    public function getSeasonNumberAttribute(): int
    {
        return (int) ($this->season ?? 0);
    }

    public function getEpisodesCountAttribute(): int
    {
        return (int) ($this->episode_count ?? 0);
    }

    public function getSummaryAttribute(): ?string
    {
        return null;
    }

    public function getReleaseYearAttribute(): ?int
    {
        return null;
    }

    public function getMetaTitleAttribute(): string
    {
        return $this->name;
    }

    public function getMetaDescriptionAttribute(): string
    {
        return 'Browse episode records for '.$this->name.'.';
    }

    public function getUpdatedAtAttribute(): mixed
    {
        return null;
    }
}
