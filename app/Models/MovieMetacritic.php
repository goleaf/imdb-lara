<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieMetacritic extends ImdbModel
{
    protected $table = 'movie_metacritic';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'url',
        'score',
        'review_count',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'score' => 'integer',
            'review_count' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
