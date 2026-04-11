<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieVideo extends ImdbModel
{
    protected $table = 'movie_videos';

    protected $primaryKey = 'imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'movie_id',
        'video_type_id',
        'name',
        'description',
        'width',
        'height',
        'runtime_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'video_type_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'runtime_seconds' => 'integer',
            'position' => 'integer',
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

    public function videoType(): BelongsTo
    {
        return $this->belongsTo(VideoType::class, 'video_type_id', 'id');
    }

    public function movieVideoPrimaryImages(): HasMany
    {
        return $this->hasMany(MovieVideoPrimaryImage::class, 'video_imdb_id', 'imdb_id');
    }
}
