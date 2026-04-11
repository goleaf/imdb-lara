<?php

namespace App\Actions\Admin;

use App\Models\AkaType;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminAkaTypesIndexQueryAction
{
    public function handle(string $search = ''): Builder
    {
        return AkaType::query()
            ->selectAdminColumns()
            ->withUsageMetrics()
            ->matchingSearch($search)
            ->ordered();
    }
}
