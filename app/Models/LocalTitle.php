<?php

namespace App\Models;

use App\Policies\TitlePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(TitlePolicy::class)]
class LocalTitle extends Title
{
    protected $table = 'titles';

    public static function usesCatalogOnlySchema(): bool
    {
        return false;
    }

    public function getMorphClass(): string
    {
        return Title::class;
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(LocalGenre::class, 'genre_title', 'title_id', 'genre_id', 'id', 'id')
            ->withTimestamps()
            ->orderBy('genres.name');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(LocalCredit::class, 'title_id')->ordered();
    }
}
