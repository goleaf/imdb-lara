<?php

namespace App\Actions\Moderation;

use App\Models\Report;
use App\Models\User;
use App\Models\UserList;

class ReportUserListAction
{
    public function __construct(private UpsertReportAction $upsertReport) {}

    /**
     * @param  array{reason: string, details?: string|null}  $attributes
     */
    public function handle(User $reporter, UserList $userList, array $attributes): Report
    {
        return $this->upsertReport->handle($reporter, $userList, $attributes);
    }
}
