<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TitleAka extends ImdbModel
{
    protected $table = 'title_akas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'titleid',
        'ordering',
        'title',
        'region',
        'language',
        'types',
        'attributes',
        'isoriginaltitle',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'ordering' => 'integer',
            'isoriginaltitle' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'titleid', 'tconst');
    }

    public function titleAkaAttributes(): HasMany
    {
        return $this->hasMany(TitleAkaAttribute::class, 'title_aka_id', 'id');
    }

    public function titleAkaTypes(): HasMany
    {
        return $this->hasMany(TitleAkaType::class, 'title_aka_id', 'id');
    }
}
