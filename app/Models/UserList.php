<?php

namespace App\Models;

use App\ListVisibility;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\UserListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserList extends Model
{
    /** @use HasFactory<UserListFactory> */
    use GeneratesSlugs;
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class)->orderBy('position');
    }
}
