<?php

namespace App\Models;

use App\Policies\PersonProfessionPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(PersonProfessionPolicy::class)]
class LocalPersonProfession extends PersonProfession
{
    protected $table = 'person_professions';

    protected static function booted(): void {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getConnectionName(): ?string
    {
        return $this->connection;
    }

    public function usesTimestamps(): bool
    {
        return true;
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(LocalPerson::class, 'person_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('profession')
            ->orderBy('id');
    }
}
