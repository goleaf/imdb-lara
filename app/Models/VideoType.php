<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoType extends ImdbModel
{
    protected $table = 'video_types';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function movieVideos(): HasMany
    {
        return $this->hasMany(MovieVideo::class, 'video_type_id', 'id');
    }
}
