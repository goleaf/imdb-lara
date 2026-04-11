<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieGenre extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_genres';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_id', 'genre_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'genre_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'genre_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id', 'id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
