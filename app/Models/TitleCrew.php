<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TitleCrew extends ImdbModel
{
    protected $table = 'title_crew';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tconst',
        'directors',
        'writers',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'tconst', 'tconst');
    }

    public function titleCrewDirectors(): HasMany
    {
        return $this->hasMany(TitleCrewDirector::class, 'title_crew_id', 'id');
    }

    public function titleCrewWriters(): HasMany
    {
        return $this->hasMany(TitleCrewWriter::class, 'title_crew_id', 'id');
    }
}
