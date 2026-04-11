<?php

namespace App\Models;

use Database\Factories\AwardCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends Model
{
    /** @use HasFactory<AwardCategoryFactory> */
    use HasFactory;

    protected $table = 'award_categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'award_id',
        'name',
        'slug',
        'recipient_scope',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'award_id' => 'integer',
        ];
    }

    protected static function usesCatalogOnlySchema(): bool
    {
        return Title::usesCatalogOnlySchema();
    }

    public function getConnectionName(): ?string
    {
        return static::usesCatalogOnlySchema() ? 'imdb_mysql' : null;
    }

    public function usesTimestamps(): bool
    {
        return static::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
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
        return $query->withCount([
            'movieAwardNominations as movie_award_nominations_count',
        ]);
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominations(): HasMany
    {
        return $this->movieAwardNominations();
    }

    public function movieAwardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class, 'award_category_id', 'id')->ordered();
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
        $selectedValue = $this->getAttributeFromArray('movie_award_nominations_count')
            ?? $this->getAttributeFromArray('award_nominations_count');

        return $selectedValue !== null ? (int) $selectedValue : 0;
    }
}
