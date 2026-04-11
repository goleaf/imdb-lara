<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interest extends ImdbModel
{
    protected $table = 'interests';

    protected $primaryKey = 'imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'name',
        'description',
        'is_subgenre',
    ];

    protected function casts(): array
    {
        return [
            'is_subgenre' => 'boolean',
        ];
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_interests', 'interest_imdb_id', 'movie_id', 'imdb_id', 'id');
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'movie_interests', 'interest_imdb_id', 'movie_id', 'imdb_id', 'id');
    }

    public function interestCategories(): BelongsToMany
    {
        return $this->belongsToMany(InterestCategory::class, 'interest_category_interests', 'interest_imdb_id', 'interest_category_id', 'imdb_id', 'id');
    }

    public function categories(): BelongsToMany
    {
        return $this->interestCategories();
    }

    public function similarInterests(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'interest_similar_interests', 'interest_imdb_id', 'similar_interest_imdb_id', 'imdb_id', 'imdb_id');
    }

    public function relatedInterests(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'interest_similar_interests', 'similar_interest_imdb_id', 'interest_imdb_id', 'imdb_id', 'imdb_id');
    }

    public function interestCategoryInterests(): HasMany
    {
        return $this->hasMany(InterestCategoryInterest::class, 'interest_imdb_id', 'imdb_id');
    }

    public function interestPrimaryImages(): HasMany
    {
        return $this->hasMany(InterestPrimaryImage::class, 'interest_imdb_id', 'imdb_id');
    }

    public function interestSimilarInterests(): HasMany
    {
        return $this->hasMany(InterestSimilarInterest::class, 'interest_imdb_id', 'imdb_id');
    }

    public function similarInterestSimilarInterests(): HasMany
    {
        return $this->hasMany(InterestSimilarInterest::class, 'similar_interest_imdb_id', 'imdb_id');
    }

    public function movieInterests(): HasMany
    {
        return $this->hasMany(MovieInterest::class, 'interest_imdb_id', 'imdb_id');
    }

    public function scopeLinkedToPublishedTitles(Builder $query): Builder
    {
        return $query->whereHas('titles', fn (Builder $titleQuery) => $titleQuery->publishedCatalog());
    }
}
