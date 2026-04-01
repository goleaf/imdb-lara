<?php

namespace App\Models;

use App\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title_id',
        'headline',
        'body',
        'contains_spoilers',
        'status',
        'moderated_by',
        'moderated_at',
        'published_at',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'contains_spoilers' => 'boolean',
            'status' => ReviewStatus::class,
            'moderated_at' => 'datetime',
            'published_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
