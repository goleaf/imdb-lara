<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieStar extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_stars';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_id', 'name_basic_id', 'ordering'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'name_basic_id',
        'ordering',
        'category',
        'job',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'name_basic_id' => 'integer',
            'ordering' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
