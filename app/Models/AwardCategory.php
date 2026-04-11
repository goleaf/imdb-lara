<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends ImdbModel
{
    protected $table = 'award_categories';

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
     * @param  list<string|null>  $names
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

        return $query->where(function (Builder $awardCategoryQuery) use ($search): void {
            $awardCategoryQuery->where('name', 'like', '%'.$search.'%');

            if (is_numeric($search)) {
                $awardCategoryQuery->orWhereKey((int) $search);
            }
        });
    }

    public function scopeSelectAdminColumns(Builder $query): Builder
    {
        return $query->select(['id', 'name']);
    }

    public function scopeWithUsageMetrics(Builder $query): Builder
    {
        return $query->withCount('movieAwardNominations');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class, 'award_category_id', 'id');
    }

    public function movieAwardNominations(): HasMany
    {
        return $this->hasMany(MovieAwardNomination::class, 'award_category_id', 'id');
    }

    public function resolvedLabel(): string
    {
        return filled($this->name) ? (string) $this->name : 'Award category';
    }

    public function shortDescription(): string
    {
        return 'Award classification attached to imported title nomination rows.';
    }

    public function movieAwardNominationUsageCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('movie_award_nominations_count');

        return $selectedValue !== null ? (int) $selectedValue : 0;
    }
}
