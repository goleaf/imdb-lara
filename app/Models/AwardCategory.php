<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends ImdbModel
{
    protected $table = 'award_categories';

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

    public function nominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class, 'award_category_id', 'id');
    }

    public function movieAwardNominations(): HasMany
    {
        return $this->hasMany(MovieAwardNomination::class, 'award_category_id', 'id');
    }
}
