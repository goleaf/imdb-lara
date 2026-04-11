<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestSimilarInterest extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'interest_similar_interests';

    protected $primaryKey = 'interest_imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected array $compositeKey = ['interest_imdb_id', 'similar_interest_imdb_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'interest_imdb_id',
        'similar_interest_imdb_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_imdb_id', 'imdb_id');
    }

    public function similar(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'similar_interest_imdb_id', 'imdb_id');
    }
}
