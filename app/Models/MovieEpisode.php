<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieEpisode extends ImdbModel
{
    protected $table = 'movie_episodes';

    protected $primaryKey = 'episode_movie_id';

    public $incrementing = false;

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
            'episode_number' => 'integer',
            'release_year' => 'integer',
            'release_month' => 'integer',
            'release_day' => 'integer',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'episode_movie_id', 'id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
