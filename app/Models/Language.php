<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends ImdbModel
{
    protected $table = 'languages';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_spoken_languages', 'language_code', 'movie_id', 'code', 'id');
    }

    public function movieAkas(): HasMany
    {
        return $this->hasMany(MovieAka::class, 'language_code', 'code');
    }

    public function movieSpokenLanguages(): HasMany
    {
        return $this->hasMany(MovieSpokenLanguage::class, 'language_code', 'code');
    }
}
