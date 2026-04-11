<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profession extends ImdbModel
{
    protected $table = 'professions';

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

    public function nameBasicProfessions(): HasMany
    {
        return $this->hasMany(NameBasicProfession::class, 'profession_id', 'id');
    }

    public function nameBasics(): BelongsToMany
    {
        return $this->belongsToMany(NameBasic::class, 'name_basic_professions', 'profession_id', 'name_basic_id', 'id', 'id');
    }
}
