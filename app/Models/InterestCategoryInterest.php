<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestCategoryInterest extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'interest_category_interests';

    protected $primaryKey = 'interest_category_id';

    public $incrementing = false;

    protected array $compositeKey = ['interest_category_id', 'interest_imdb_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'interest_category_id',
        'interest_imdb_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'interest_category_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function interestCategory(): BelongsTo
    {
        return $this->belongsTo(InterestCategory::class, 'interest_category_id', 'id');
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_imdb_id', 'imdb_id');
    }
}
