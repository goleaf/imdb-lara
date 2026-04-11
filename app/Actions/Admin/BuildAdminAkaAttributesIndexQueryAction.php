<?php

namespace App\Actions\Admin;

use App\Models\AkaAttribute;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminAkaAttributesIndexQueryAction
{
    public function handle(string $search = ''): Builder
    {
        return AkaAttribute::query()
            ->selectAdminColumns()
            ->withUsageMetrics()
            ->matchingSearch($search)
            ->ordered();
    }
}
