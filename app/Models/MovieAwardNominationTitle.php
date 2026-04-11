<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieAwardNominationTitle extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_award_nomination_titles';

    protected $primaryKey = 'movie_award_nomination_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_award_nomination_id', 'nominated_movie_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_award_nomination_id',
        'nominated_movie_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_award_nomination_id' => 'integer',
            'nominated_movie_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nominated(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'nominated_movie_id', 'id');
    }

    public function movieAwardNomination(): BelongsTo
    {
        return $this->belongsTo(MovieAwardNomination::class, 'movie_award_nomination_id', 'id');
    }
}
