<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieInterest extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_interests';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_id', 'interest_imdb_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'interest_imdb_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_imdb_id', 'imdb_id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
