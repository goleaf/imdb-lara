<?php

namespace App\Models;

use App\Enums\AkaAttributeValue;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if (preg_match('/-aa(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->whereKey((int) $matches['id']);
        }

        return $query->whereKey((int) $value);
    }

    public function movieAkaAttributes(): HasMany
    {
        return $this->hasMany(MovieAkaAttribute::class, 'aka_attribute_id', 'id');
    }

    public function titleAkaAttributes(): HasMany
    {
        return $this->hasMany(TitleAkaAttribute::class, 'aka_attribute_id', 'id');
    }

    public function resolvedLabel(): string
    {
        return AkaAttributeValue::labelFor($this->name) ?? ($this->name ?: 'AKA attribute');
    }

    public function shortDescription(): string
    {
        return AkaAttributeValue::descriptionFor($this->name) ?? 'Alternate-title marker attached to imported AKA records.';
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->resolvedLabel()).'-aa'.$this->id;
    }
}
