<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieReleaseDateAttribute extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_release_date_attributes';

    protected $primaryKey = 'movie_release_date_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_release_date_id', 'release_date_attribute_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_release_date_id',
        'release_date_attribute_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_release_date_id' => 'integer',
            'release_date_attribute_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function releaseDateAttribute(): BelongsTo
    {
        return $this->belongsTo(ReleaseDateAttribute::class, 'release_date_attribute_id', 'id');
    }

    public function movieReleaseDate(): BelongsTo
    {
        return $this->belongsTo(MovieReleaseDate::class, 'movie_release_date_id', 'id');
    }
}
