<?php

namespace App\Models;

use App\Enums\LanguageCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieAka extends ImdbModel
{
    protected $table = 'movie_akas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'text',
        'country_code',
        'language_code',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    public function resolvedLanguageLabel(): ?string
    {
        if ($this->relationLoaded('language') && filled($this->language?->name)) {
            return (string) $this->language?->name;
        }

        return LanguageCode::labelFor($this->language_code) ?? $this->language_code;
    }

    public function resolvedCountryLabel(): ?string
    {
        $fallbackName = $this->relationLoaded('country') ? $this->country?->name : null;

        return Country::labelForCode($this->country_code, $fallbackName);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function movieAkaAttributes(): HasMany
    {
        return $this->hasMany(MovieAkaAttribute::class, 'movie_aka_id', 'id');
    }
}
