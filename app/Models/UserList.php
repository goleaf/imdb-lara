<?php

namespace App\Models;

use App\Enums\ListVisibility;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\UserListFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    public function isUnlisted(): bool
    {
        return $this->visibility === ListVisibility::Unlisted;
    }

    public function isShareable(): bool
    {
        return $this->isPublic() || $this->isUnlisted();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class)->orderBy('position');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function moderationActions(): MorphMany
    {
        return $this->morphMany(ModerationAction::class, 'actionable');
    }

    public function previewTitle(): ?Title
    {
        if (! $this->relationLoaded('items')) {
            return null;
        }

        /** @var ListItem|null $item */
        $item = $this->items->first();

        return $item?->title;
    }

    /**
     * @return EloquentCollection<int, ListItem>
     */
    public function previewItems(int $limit = 3): EloquentCollection
    {
        if (! $this->relationLoaded('items')) {
            return new EloquentCollection;
        }

        /** @var EloquentCollection<int, ListItem> $items */
        $items = $this->items
            ->filter(fn (ListItem $item): bool => $item->title !== null)
            ->take($limit)
            ->values();

        return $items;
    }

    public function previewPoster(): ?MediaAsset
    {
        return $this->previewTitle()?->preferredPoster();
    }

    protected function slugConflictQuery(string $slug): Builder
    {
        return static::query()
            ->where('slug', $slug)
            ->when(
                $this->exists,
                fn (Builder $query) => $query->whereKeyNot($this->getKey()),
            )
            ->when(
                filled($this->user_id),
                fn (Builder $query) => $query->where('user_id', $this->user_id),
            );
    }
}
