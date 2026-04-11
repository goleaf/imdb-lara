<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleEpisode extends ImdbModel
{
    protected $table = 'title_episode';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tconst',
        'parenttconst',
        'seasonnumber',
        'episodenumber',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'seasonnumber' => 'integer',
            'episodenumber' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'parenttconst', 'tconst');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'tconst', 'tconst');
    }
}
