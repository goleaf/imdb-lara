<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieAkaType extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_aka_types';

    protected $primaryKey = 'movie_aka_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_aka_id', 'aka_type_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_aka_id',
        'aka_type_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_aka_id' => 'integer',
            'aka_type_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function scopeForAkaType(Builder $query, AkaType|int $akaType): Builder
    {
        $akaTypeId = $akaType instanceof AkaType
            ? (int) $akaType->getKey()
            : (int) $akaType;

        return $query->where('aka_type_id', $akaTypeId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('position')
            ->orderBy('movie_aka_id')
            ->orderBy('aka_type_id');
    }

    public function akaType(): BelongsTo
    {
        return $this->belongsTo(AkaType::class, 'aka_type_id', 'id');
    }

    public function movieAka(): BelongsTo
    {
        return $this->belongsTo(MovieAka::class, 'movie_aka_id', 'id');
    }
}
