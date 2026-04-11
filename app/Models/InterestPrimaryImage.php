<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestPrimaryImage extends ImdbModel
{
    protected $table = 'interest_primary_images';

    protected $primaryKey = 'interest_imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'interest_imdb_id',
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

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_imdb_id', 'imdb_id');
    }
}
