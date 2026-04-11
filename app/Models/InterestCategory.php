<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestCategory extends ImdbModel
{
    protected $table = 'interest_categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'interest_category_interests', 'interest_category_id', 'interest_imdb_id', 'id', 'imdb_id');
    }

    public function interestCategoryInterests(): HasMany
    {
        return $this->hasMany(InterestCategoryInterest::class, 'interest_category_id', 'id');
    }
}
