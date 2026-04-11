<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieSeason extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_seasons';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_id', 'season'];

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
            'episode_count' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
