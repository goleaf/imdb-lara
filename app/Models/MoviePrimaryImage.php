<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoviePrimaryImage extends ImdbModel
{
    protected $table = 'movie_primary_images';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'url',
        'width',
        'height',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
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
