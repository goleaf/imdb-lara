<?php

namespace App\Models;

use App\ContributionAction;
use App\ContributionStatus;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contribution extends Model
{
    /** @use HasFactory<ContributionFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'contributable_type',
        'contributable_id',
        'action',
        'status',
        'payload',
        'notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ContributionAction::class,
            'status' => ContributionStatus::class,
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function contributable(): MorphTo
    {
        return $this->morphTo();
    }
}
