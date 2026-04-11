<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'movie_episodes';

    protected $primaryKey = 'episode_movie_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'episode_movie_id',
        'movie_id',
        'season',
        'episode_number',
        'release_year',
        'release_month',
        'release_day',
    ];

    protected function casts(): array
    {
        return [
            'episode_movie_id' => 'integer',
            'movie_id' => 'integer',
            'season' => 'integer',
            'episode_number' => 'integer',
            'release_year' => 'integer',
            'release_month' => 'integer',
            'release_day' => 'integer',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'episode_movie_id', 'id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function getTitleIdAttribute(): int
    {
        return (int) $this->episode_movie_id;
    }

    public function getSeriesIdAttribute(): int
    {
        return (int) $this->movie_id;
    }

    public function getSeasonIdAttribute(): string
    {
        return $this->movie_id.':'.$this->season;
    }

    public function getSeasonNumberAttribute(): int
    {
        return (int) ($this->season ?? 0);
    }

    public function getAbsoluteNumberAttribute(): ?int
    {
        return null;
    }

    public function getProductionCodeAttribute(): ?string
    {
        return null;
    }

    public function getAiredAtAttribute(): ?CarbonImmutable
    {
        if (! $this->release_year) {
            return null;
        }

        $month = $this->release_month ?: 1;
        $day = $this->release_day ?: 1;

        return CarbonImmutable::create((int) $this->release_year, (int) $month, (int) $day);
    }
}
