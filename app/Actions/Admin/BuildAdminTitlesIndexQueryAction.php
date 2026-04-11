<?php

namespace App\Actions\Admin;

use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminTitlesIndexQueryAction
{
    public function handle(): Builder
    {
        return Title::query()
            ->selectCatalogCardColumns()
            ->orderByCatalogName();
    }
}
