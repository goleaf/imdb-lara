<?php

namespace App\Models;

use App\ReportReason;
use App\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
