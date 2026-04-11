<?php

namespace App\Models;

use App\Enums\AkaAttributeValue;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @param  list<string>  $names
     * @return array<string, array{name: string}>
     */
    public static function lookupRows(array $names): array
    {
        $rows = [];

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $rows[$name] = [
                'name' => $name,
            ];
        }

        return $rows;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $akaAttributeQuery) use ($search): void {
            $akaAttributeQuery->where('name', 'like', '%'.$search.'%');

            if (is_numeric($search)) {
                $akaAttributeQuery->orWhereKey((int) $search);
            }
        });
    }

    public function scopeSelectAdminColumns(Builder $query): Builder
    {
        return $query->select(['id', 'name']);
    }

    public function scopeWithUsageMetrics(Builder $query): Builder
    {
        return $query->withCount('movieAkaAttributes');
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

    public function movieAkaUsageCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('movie_aka_attributes_count');

        return $selectedValue !== null ? (int) $selectedValue : 0;
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->resolvedLabel()).'-aa'.$this->id;
    }
}
