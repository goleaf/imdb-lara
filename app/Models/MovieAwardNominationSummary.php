<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieAwardNominationSummary extends ImdbModel
{
    protected $table = 'movie_award_nomination_summaries';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'nomination_count',
        'win_count',
        'next_page_token',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'nomination_count' => 'integer',
            'win_count' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
