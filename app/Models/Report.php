<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
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

    /**
     * @return array<int|string, mixed>
     */
    public static function adminQueueRelations(): array
    {
        return [
            'reporter:id,name,username',
            'reviewer:id,name,username',
            'reportable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Review::class => Review::adminReportableRelations(),
                    UserList::class => ['user:id,name,username,role,status'],
                ])->morphWithCount([
                    UserList::class => ['items'],
                ]);
            },
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

    public function reportableOwner(): ?User
    {
        return match (true) {
            $this->reportable instanceof Review => $this->reportable->author,
            $this->reportable instanceof UserList => $this->reportable->user,
            default => null,
        };
    }
}
