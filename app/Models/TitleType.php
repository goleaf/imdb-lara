<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TitleType extends ImdbModel
{
    protected $table = 'title_types';

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

    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class, 'title_type_id', 'id');
    }
}
