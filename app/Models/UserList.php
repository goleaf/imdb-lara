<?php

namespace App\Models;

use App\Enums\ListVisibility;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\UserListFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserList extends Model
{
    /** @use HasFactory<UserListFactory> */
    use GeneratesSlugs;

    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'visibility',
        'is_watchlist',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => ListVisibility::class,
            'is_watchlist' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_watchlist', false);
    }

    public function scopeWatchlist(Builder $query): Builder
    {
        return $query->where('is_watchlist', true);
    }

    public function isPublic(): bool
    {
        return $this->visibility === ListVisibility::Public;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class)->orderBy('position');
    }
}
