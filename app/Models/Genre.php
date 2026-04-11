<?php

namespace App\Models;

use Database\Factories\GenreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Genre extends Model
{
    /** @use HasFactory<GenreFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function getConnectionName(): ?string
    {
        return Title::usesCatalogOnlySchema() ? 'imdb_mysql' : parent::getConnectionName();
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
        return $query->where(function ($genreQuery) use ($value): void {
            $genreQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $genreQuery->orWhere($this->qualifyColumn($this->getKeyName()), (int) $value);
            }
        });
    }

    public function getSlugAttribute(?string $value): string
    {
        if (filled($value)) {
            return (string) $value;
        }

        return Str::slug((string) $this->name).'-g'.$this->id;
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class)
            ->withTimestamps()
            ->orderBy('titles.name');
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
        return $this->description ?: 'Browse '.$this->name.' titles from the curated catalog.';
    }
}
