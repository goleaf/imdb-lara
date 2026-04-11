<?php

namespace App\Actions\Admin;

use App\Models\AwardCategory;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminAwardCategoriesIndexQueryAction
{
    public function handle(string $search = ''): Builder
    {
        return AwardCategory::query()
            ->selectAdminColumns()
            ->withUsageMetrics()
            ->matchingSearch($search)
            ->ordered();
    }
}
