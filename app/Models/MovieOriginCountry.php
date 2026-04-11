<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieOriginCountry extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_origin_countries';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_id', 'country_code'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'country_code',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
