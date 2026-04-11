<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Title;

trait BlocksCatalogOnlyAdminMutations
{
    protected function abortIfCatalogOnly(): void
    {
        abort_if(
            Title::usesCatalogOnlySchema(),
            501,
            'Catalog mutations are paused while the application is running in catalog-only mode.',
        );
    }
}
