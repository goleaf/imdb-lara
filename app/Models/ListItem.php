<?php

namespace App\Models;

use App\Enums\WatchState;
use Database\Factories\ListItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListItem extends Model
{
    /** @use HasFactory<ListItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_list_id',
        'title_id',
        'notes',
        'position',
        'watch_state',
        'started_at',
        'watched_at',
        'rewatch_count',
    ];

    protected function casts(): array
    {
        return [
            'watch_state' => WatchState::class,
            'started_at' => 'datetime',
            'watched_at' => 'datetime',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function userList(): BelongsTo
    {
        return $this->belongsTo(UserList::class);
    }
}
