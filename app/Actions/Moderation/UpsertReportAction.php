<?php

namespace App\Actions\Moderation;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UpsertReportAction
{
    /**
     * @param  array{reason: string, details?: string|null}  $attributes
     */
    public function handle(User $reporter, Model $reportable, array $attributes): Report
    {
        return Report::query()->updateOrCreate(
            [
                'user_id' => $reporter->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->getKey(),
            ],
            [
                'reason' => $attributes['reason'],
                'details' => filled($attributes['details'] ?? null) ? trim((string) $attributes['details']) : null,
                'status' => ReportStatus::Open,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'resolution_notes' => null,
            ],
        );
    }
}
