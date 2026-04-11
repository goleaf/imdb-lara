<?php

namespace App\Models;

use App\Enums\LanguageCode;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeForAkaAttribute(Builder $query, AkaAttribute|int $akaAttribute): Builder
    {
        $akaAttributeId = $akaAttribute instanceof AkaAttribute
            ? (int) $akaAttribute->getKey()
            : (int) $akaAttribute;

        return $query->whereHas(
            'movieAkaAttributes',
            fn (Builder $movieAkaAttributeQuery): Builder => $movieAkaAttributeQuery->forAkaAttribute($akaAttributeId),
        );
    }

    public function scopeForAkaType(Builder $query, AkaType|int $akaType): Builder
    {
        $akaTypeId = $akaType instanceof AkaType
            ? (int) $akaType->getKey()
            : (int) $akaType;

        return $query->whereHas(
            'movieAkaTypes',
            fn (Builder $movieAkaTypeQuery): Builder => $movieAkaTypeQuery->forAkaType($akaTypeId),
        );
    }

    public function scopeSelectArchiveColumns(Builder $query): Builder
    {
        return $query->select(['id', 'movie_id', 'text', 'country_code', 'language_code', 'position']);
    }

    public function scopeWithTitleDetailRelations(Builder $query): Builder
    {
        return $query->with([
            'country:code,name',
            'language:code,name',
            'movieAkaAttributes' => fn ($movieAkaAttributeQuery) => $movieAkaAttributeQuery
                ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
                ->ordered()
                ->with([
                    'akaAttribute:id,name',
                ]),
            'movieAkaTypes' => fn ($movieAkaTypeQuery) => $movieAkaTypeQuery
                ->select(['movie_aka_id', 'aka_type_id', 'position'])
                ->ordered()
                ->with([
                    'akaType:id,name',
                ]),
        ]);
    }

    public function scopeWithArchiveRelations(Builder $query): Builder
    {
        return $query->with([
            'country:code,name',
            'language:code,name',
            'title' => fn ($titleQuery) => $titleQuery
                ->selectCatalogCardColumns()
                ->publishedCatalog()
                ->withCatalogCardRelations(),
            'movieAkaAttributes' => fn ($movieAkaAttributeQuery) => $movieAkaAttributeQuery
                ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
                ->ordered()
                ->with([
                    'akaAttribute:id,name',
                ]),
        ]);
    }

    public function scopeArchiveOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('movie_id')
            ->orderBy('position')
            ->orderBy('id');
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

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function movieAkaAttributes(): HasMany
    {
        return $this->hasMany(MovieAkaAttribute::class, 'movie_aka_id', 'id');
    }

    public function movieAkaTypes(): HasMany
    {
        return $this->hasMany(MovieAkaType::class, 'movie_aka_id', 'id');
    }
}
