<?php

namespace App\Models;

use App\Policies\GenrePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[UsePolicy(GenrePolicy::class)]
class LocalGenre extends Genre
{
    protected $table = 'genres';

    public function getConnectionName(): ?string
    {
        return $this->connection;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field !== null) {
            return $query->where($field, $value);
        }

        return $query->where(function (Builder $genreQuery) use ($value): void {
            $genreQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $genreQuery->orWhere($this->qualifyColumn($this->getKeyName()), (int) $value);
            }

            if (is_string($value) && $value !== '') {
                $genreQuery->orWhere('slug', Str::slug($value));
            }
        });
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(LocalTitle::class, 'genre_title', 'genre_id', 'title_id', 'id', 'id')
            ->withTimestamps()
            ->orderBy('titles.name');
    }
}
