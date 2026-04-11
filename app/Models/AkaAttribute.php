<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AkaAttribute extends ImdbModel
{
    protected $table = 'aka_attributes';

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

    public function movieAkaAttributes(): HasMany
    {
        return $this->hasMany(MovieAkaAttribute::class, 'aka_attribute_id', 'id');
    }

    public function titleAkaAttributes(): HasMany
    {
        return $this->hasMany(TitleAkaAttribute::class, 'aka_attribute_id', 'id');
    }
}
