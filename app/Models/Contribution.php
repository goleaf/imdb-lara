<?php

namespace App\Models;

use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contribution extends Model
{
    /** @use HasFactory<ContributionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $contribution): void {
            $contribution->status_priority = $contribution->status?->priority() ?? ContributionStatus::Submitted->priority();
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'contributable_type',
        'contributable_id',
        'action',
        'status',
        'status_priority',
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
            'status_priority' => 'integer',
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

    public function adminContributable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'contributable_type', 'contributable_id');
    }

    public function moderationActions(): MorphMany
    {
        return $this->morphMany(ModerationAction::class, 'actionable');
    }

    public function getProposedFieldAttribute(): ?string
    {
        $field = $this->payload['field'] ?? null;

        return is_string($field) && filled($field) ? $field : null;
    }

    public function getProposedFieldLabelAttribute(): ?string
    {
        $fieldLabel = $this->payload['field_label'] ?? null;

        if (is_string($fieldLabel) && filled($fieldLabel)) {
            return $fieldLabel;
        }

        return $this->proposed_field
            ? str($this->proposed_field)->replace('_', ' ')->headline()->toString()
            : null;
    }

    public function getProposedValueAttribute(): ?string
    {
        $value = $this->payload['value'] ?? null;

        return is_scalar($value) && filled((string) $value) ? trim((string) $value) : null;
    }

    public function getSubmissionNotesAttribute(): ?string
    {
        $submissionNotes = $this->payload['submission_notes'] ?? null;

        if (is_string($submissionNotes) && filled($submissionNotes)) {
            return trim($submissionNotes);
        }

        return $this->reviewed_by === null && filled($this->notes) ? trim((string) $this->notes) : null;
    }

    public function getReviewNotesAttribute(): ?string
    {
        return $this->reviewed_by !== null && filled($this->notes) ? trim((string) $this->notes) : null;
    }

    public function contributableAdminUrl(): ?string
    {
        $contributable = $this->resolvedAdminContributable();

        return match (true) {
            $contributable instanceof LocalTitle => route('admin.titles.edit', $contributable),
            $contributable instanceof LocalPerson => route('admin.people.edit', $contributable),
            default => null,
        };
    }

    public function contributableLabel(): ?string
    {
        $contributable = $this->resolvedAdminContributable();

        return match (true) {
            $contributable instanceof LocalTitle => $contributable->name,
            $contributable instanceof LocalPerson => $contributable->name,
            default => filled($this->contributable_type) ? Str::headline(class_basename($this->contributable_type)) : null,
        };
    }

    private function resolvedAdminContributable(): ?Model
    {
        if ($this->relationLoaded('adminContributable')) {
            $resolvedContributable = $this->getRelation('adminContributable');

            return $resolvedContributable instanceof Model ? $resolvedContributable : null;
        }

        $resolvedContributable = match ($this->contributable_type) {
            Title::class => LocalTitle::query()
                ->select(['id', 'name', 'slug'])
                ->find($this->contributable_id),
            Person::class => LocalPerson::query()
                ->select(['id', 'name', 'slug'])
                ->find($this->contributable_id),
            default => $this->contributable,
        };

        $this->setRelation('adminContributable', $resolvedContributable);

        return $resolvedContributable instanceof Model ? $resolvedContributable : null;
    }
}
