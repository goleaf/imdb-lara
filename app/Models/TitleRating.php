<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleRating extends ImdbModel
{
    protected $table = 'title_ratings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tconst',
        'averagerating',
        'numvotes',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'averagerating' => 'float',
            'numvotes' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'tconst', 'tconst');
    }
}
