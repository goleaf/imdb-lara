<?php

namespace App\Models;

use App\Enums\MediaKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class InterestCategory extends ImdbModel
{
    protected $table = 'interest_categories';

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
     * @return list<string>
     */
    public static function directoryColumns(): array
    {
        return [
            'interest_categories.id',
            'interest_categories.name',
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
        if (preg_match('/-ic(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->where('id', (int) $matches['id']);
        }

        return $query->where('id', (int) $value);
    }

    public function scopeWithDirectoryMetrics(Builder $query): Builder
    {
        return $query->withCount([
            'interests',
            'interests as title_linked_interests_count' => fn (Builder $interestQuery) => $interestQuery->linkedToPublishedTitles(),
            'interests as subgenre_interests_count' => fn (Builder $interestQuery) => $interestQuery->where('interests.is_subgenre', true),
        ]);
    }

    public function scopeSelectDirectoryColumns(Builder $query): Builder
    {
        return $query->select(self::directoryColumns());
    }

    public function scopeWithDirectoryPreviewImage(Builder $query): Builder
    {
        return $query
            ->selectSub(self::directoryPreviewColumnSubquery('url'), 'directory_image_url')
            ->selectSub(self::directoryPreviewColumnSubquery('width'), 'directory_image_width')
            ->selectSub(self::directoryPreviewColumnSubquery('height'), 'directory_image_height')
            ->selectSub(self::directoryPreviewColumnSubquery('type'), 'directory_image_type');
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $categoryQuery) use ($search): void {
            $categoryQuery
                ->where('interest_categories.name', 'like', '%'.$search.'%')
                ->orWhereHas('interests', function (Builder $interestQuery) use ($search): void {
                    $interestQuery->where('interests.name', 'like', '%'.$search.'%');
                });
        });
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'interest_category_interests', 'interest_category_id', 'interest_imdb_id', 'id', 'imdb_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function interestCategoryInterests(): HasMany
    {
        return $this->hasMany(InterestCategoryInterest::class, 'interest_category_id', 'id');
    }

    public function interestCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('interests_count');

        if ($selectedValue !== null) {
            return (int) $selectedValue;
        }

        if ($this->relationLoaded('interests')) {
            return $this->interests->count();
        }

        return 0;
    }

    public function titleLinkedInterestCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('title_linked_interests_count');

        return $selectedValue !== null ? (int) $selectedValue : 0;
    }

    public function subgenreInterestCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('subgenre_interests_count');

        if ($selectedValue !== null) {
            return (int) $selectedValue;
        }

        if ($this->relationLoaded('interests')) {
            return $this->interests
                ->filter(fn (Interest $interest): bool => (bool) $interest->is_subgenre)
                ->count();
        }

        return 0;
    }

    public function interestCountBadgeLabel(): string
    {
        return Number::format($this->interestCount()).' '.Str::plural('interest', $this->interestCount());
    }

    public function preferredDirectoryImage(): ?CatalogMediaAsset
    {
        if (! filled($this->directory_image_url)) {
            return null;
        }

        $kind = MediaKind::tryFrom((string) $this->directory_image_type) ?? MediaKind::Gallery;

        return CatalogMediaAsset::fromCatalog([
            'kind' => $kind->value,
            'url' => $this->directory_image_url,
            'alt_text' => $this->name,
            'width' => $this->directory_image_width,
            'height' => $this->directory_image_height,
            'position' => 0,
            'is_primary' => true,
        ]);
    }

    public function titleLinkedInterestCountBadgeLabel(): string
    {
        return Number::format($this->titleLinkedInterestCount()).' title-linked '.Str::plural('interest', $this->titleLinkedInterestCount());
    }

    public function subgenreInterestCountBadgeLabel(): string
    {
        return Number::format($this->subgenreInterestCount()).' '.Str::plural('subgenre', $this->subgenreInterestCount());
    }

    public function getSlugAttribute(): string
    {
        return Str::slug((string) $this->name).'-ic'.$this->id;
    }

    public function getDescriptionAttribute(): string
    {
        return 'Browse interests and linked titles grouped under '.$this->name.'.';
    }

    private static function directoryPreviewColumnSubquery(string $column): Builder
    {
        return InterestPrimaryImage::query()
            ->select($column)
            ->join('interest_category_interests', 'interest_category_interests.interest_imdb_id', '=', 'interest_primary_images.interest_imdb_id')
            ->whereColumn('interest_category_interests.interest_category_id', 'interest_categories.id')
            ->orderBy('interest_category_interests.position')
            ->limit(1);
    }
}
