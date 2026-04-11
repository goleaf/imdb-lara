<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieAkaAttribute extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_aka_attributes';

    protected $primaryKey = 'movie_aka_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_aka_id', 'aka_attribute_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_aka_id',
        'aka_attribute_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_aka_id' => 'integer',
            'aka_attribute_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function akaAttribute(): BelongsTo
    {
        return $this->belongsTo(AkaAttribute::class, 'aka_attribute_id', 'id');
    }

    public function movieAka(): BelongsTo
    {
        return $this->belongsTo(MovieAka::class, 'movie_aka_id', 'id');
    }
}
