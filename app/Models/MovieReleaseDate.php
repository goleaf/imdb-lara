<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieReleaseDate extends ImdbModel
{
    protected $table = 'movie_release_dates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'country_code',
        'release_year',
        'release_month',
        'release_day',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'release_year' => 'integer',
            'release_month' => 'integer',
            'release_day' => 'integer',
            'position' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function movieReleaseDateAttributes(): HasMany
    {
        return $this->hasMany(MovieReleaseDateAttribute::class, 'movie_release_date_id', 'id');
    }
}
