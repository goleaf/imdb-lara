<?php

namespace App\Models;

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
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function userList(): BelongsTo
    {
        return $this->belongsTo(UserList::class);
    }
}
