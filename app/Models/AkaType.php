<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

        return $query->where(function (Builder $akaTypeQuery) use ($search): void {
            $akaTypeQuery->where('name', 'like', '%'.$search.'%');

            if (is_numeric($search)) {
                $akaTypeQuery->orWhereKey((int) $search);
            }
        });
    }

    public function scopeSelectAdminColumns(Builder $query): Builder
    {
        return $query->select(['id', 'name']);
    }

    public function scopeWithUsageMetrics(Builder $query): Builder
    {
        return $query->withCount('movieAkaTypes');
    }

    public function movieAkaTypes(): HasMany
    {
        return $this->hasMany(MovieAkaType::class, 'aka_type_id', 'id');
    }

    public function titleAkaTypes(): HasMany
    {
        return $this->hasMany(TitleAkaType::class, 'aka_type_id', 'id');
    }

    public function resolvedLabel(): string
    {
        return Str::of((string) $this->name)
            ->replace(['_', '-'], ' ')
            ->headline()
            ->toString();
    }

    public function shortDescription(): string
    {
        return 'Alternate-title classification attached to imported AKA rows.';
    }

    public function movieAkaUsageCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('movie_aka_types_count');

        return $selectedValue !== null ? (int) $selectedValue : 0;
    }
}
