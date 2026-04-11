<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Genre extends ImdbModel
{
    protected $table = 'genres';

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
        if (preg_match('/-g(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->where('id', (int) $matches['id']);
        }

        return $query->where('id', (int) $value);
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'movie_genres', 'genre_id', 'movie_id', 'id', 'id')
            ->orderBy('primarytitle');
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_genres', 'genre_id', 'movie_id', 'id', 'id');
    }

    public function movieGenres(): HasMany
    {
        return $this->hasMany(MovieGenre::class, 'genre_id', 'id');
    }

    public function publishedTitleCount(): int
    {
        $selectedValue = $this->getAttributeFromArray('published_titles_count');

        return $selectedValue !== null ? (int) $selectedValue : 0;
    }

    public function publishedTitleCountBadgeLabel(): string
    {
        return Number::format($this->publishedTitleCount()).' '.Str::plural('title', $this->publishedTitleCount());
    }

    public function descriptionText(): string
    {
        return 'Browse '.$this->name.' titles from the imported catalog.';
    }

    public function getSlugAttribute(): string
    {
        return Str::slug((string) $this->name).'-g'.$this->id;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->descriptionText();
    }
}
