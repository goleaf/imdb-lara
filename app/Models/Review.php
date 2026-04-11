<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Published);
    }

    public function scopeForTitle(Builder $query, Title $title): Builder
    {
        return $query->whereBelongsTo($title);
    }

    public function scopeAuthoredBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user, 'author');
    }

    public function scopeWithHelpfulMetrics(Builder $query, ?User $viewer = null): Builder
    {
        $query->withCount([
            'votes as helpful_votes_count' => fn (Builder $voteQuery) => $voteQuery->where('is_helpful', true),
        ]);

        if ($viewer) {
            $query->withCount([
                'votes as current_user_helpful_votes_count' => fn (Builder $voteQuery) => $voteQuery
                    ->where('is_helpful', true)
                    ->whereBelongsTo($viewer),
            ]);
        }

        return $query;
    }

    public function scopeWithModerationMetrics(Builder $query): Builder
    {
        return $query->withCount([
            'votes as helpful_votes_count' => fn (Builder $voteQuery) => $voteQuery->where('is_helpful', true),
            'reports as open_reports_count' => fn (Builder $reportQuery) => $reportQuery->where('status', ReportStatus::Open),
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function adminQueueRelations(): array
    {
        return [
            'author:id,name,username',
            'adminTitle' => fn ($titleQuery) => $titleQuery->select(LocalTitle::catalogCardColumns()),
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function adminReportableRelations(): array
    {
        return [
            'author:id,name,username,role,status',
            'adminTitle' => fn ($titleQuery) => $titleQuery->select(LocalTitle::catalogCardColumns()),
        ];
    }

    public function scopeOrderForPublic(Builder $query, string $sort = 'newest'): Builder
    {
        return match ($sort) {
            'helpful' => $query
                ->orderByDesc('helpful_votes_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            default => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };
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

    public function adminTitle(): BelongsTo
    {
        return $this->belongsTo(LocalTitle::class, 'title_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewVote::class)->where('is_helpful', true);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function moderationActions(): MorphMany
    {
        return $this->morphMany(ModerationAction::class, 'actionable');
    }
}
