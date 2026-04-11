<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseDateAttribute extends ImdbModel
{
    protected $table = 'release_date_attributes';

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

    public function movieReleaseDateAttributes(): HasMany
    {
        return $this->hasMany(MovieReleaseDateAttribute::class, 'release_date_attribute_id', 'id');
    }
}
