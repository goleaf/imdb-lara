<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieAwardNomination extends ImdbModel
{
    protected $table = 'movie_award_nominations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'event_imdb_id',
        'award_category_id',
        'award_year',
        'text',
        'is_winner',
        'winner_rank',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'award_category_id' => 'integer',
            'award_year' => 'integer',
            'is_winner' => 'boolean',
            'winner_rank' => 'integer',
            'position' => 'integer',
        ];
    }

    public function awardCategory(): BelongsTo
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id', 'id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class, 'event_imdb_id', 'imdb_id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function movieAwardNominationNominees(): HasMany
    {
        return $this->hasMany(MovieAwardNominationNominee::class, 'movie_award_nomination_id', 'id');
    }

    public function movieAwardNominationTitles(): HasMany
    {
        return $this->hasMany(MovieAwardNominationTitle::class, 'movie_award_nomination_id', 'id');
    }
}
