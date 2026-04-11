<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NameCredit extends ImdbModel
{
    protected $table = 'name_credits';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'movie_id',
        'category',
        'episode_count',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name_basic_id' => 'integer',
            'movie_id' => 'integer',
            'episode_count' => 'integer',
            'position' => 'integer',
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

    public function nameCreditCharacters(): HasMany
    {
        return $this->hasMany(NameCreditCharacter::class, 'name_credit_id', 'id');
    }
}
