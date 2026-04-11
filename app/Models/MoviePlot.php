<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoviePlot extends ImdbModel
{
    protected $table = 'movie_plots';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'plot',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
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
