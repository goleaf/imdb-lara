<?php

namespace App\Models;

use App\Policies\CreditPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(CreditPolicy::class)]
class LocalCredit extends Credit
{
    protected $table = 'credits';

    protected static function booted(): void {}

    public function newQuery(): Builder
    {
        return $this->registerGlobalScopes($this->newQueryWithoutScopes());
    }

    public function newQueryWithoutScopes(): Builder
    {
        return $this->newModelQuery()
            ->with($this->with)
            ->withCount($this->withCount);
    }

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

    public function title(): BelongsTo
    {
        return $this->belongsTo(LocalTitle::class, 'title_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(LocalPerson::class, 'person_id');
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(LocalPersonProfession::class, 'person_profession_id');
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class, 'episode_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('credits.billing_order')
            ->orderBy('credits.id');
    }

    public function scopeCast(Builder $query): Builder
    {
        return $query->where('credits.department', 'Cast');
    }

    public function scopeCrew(Builder $query): Builder
    {
        return $query->where('credits.department', '!=', 'Cast');
    }

    /**
     * @param  list<string>  $departments
     */
    public function scopeInDepartments(Builder $query, array $departments): Builder
    {
        return $query->whereIn('credits.department', $departments);
    }
}
