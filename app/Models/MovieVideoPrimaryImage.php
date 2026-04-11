<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieVideoPrimaryImage extends ImdbModel
{
    protected $table = 'movie_video_primary_images';

    protected $primaryKey = 'video_imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'video_imdb_id',
        'url',
        'width',
        'height',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(MovieVideo::class, 'video_imdb_id', 'imdb_id');
    }
}
