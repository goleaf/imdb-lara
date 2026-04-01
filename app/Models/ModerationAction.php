<?php

namespace App\Models;

use Database\Factories\ModerationActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationAction extends Model
{
    /** @use HasFactory<ModerationActionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'moderator_id',
        'report_id',
        'actionable_type',
        'actionable_id',
        'action',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }
}
