<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieRating extends ImdbModel
{
    protected $table = 'movie_ratings';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'aggregate_rating',
        'vote_count',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'aggregate_rating' => 'decimal:2',
            'vote_count' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }
}
