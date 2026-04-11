<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AkaType extends ImdbModel
{
    protected $table = 'aka_types';

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

    public function titleAkaTypes(): HasMany
    {
        return $this->hasMany(TitleAkaType::class, 'aka_type_id', 'id');
    }
}
