<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieAka extends ImdbModel
{
    protected $table = 'movie_akas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'text',
        'country_code',
        'language_code',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function movieAkaAttributes(): HasMany
    {
        return $this->hasMany(MovieAkaAttribute::class, 'movie_aka_id', 'id');
    }
}
