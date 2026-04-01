<?php

namespace App\Actions\Admin;

use App\Models\Report;
use App\Models\Review;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BuildAdminReportsIndexQueryAction
{
    public function handle(): Builder
    {
        return Report::query()
            ->select([
                'id',
                'user_id',
                'reportable_type',
                'reportable_id',
                'reason',
                'details',
                'status',
                'reviewed_by',
                'reviewed_at',
                'resolution_notes',
                'created_at',
            ])
            ->with([
                'reporter:id,name,username',
                'reviewer:id,name,username',
                'reportable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Review::class => ['author:id,name,username,role,status', 'title:id,name,slug'],
                        UserList::class => ['user:id,name,username,role,status'],
                    ])->morphWithCount([
                        UserList::class => ['items'],
                    ]);
                },
            ])
            ->latest('created_at');
    }
}
