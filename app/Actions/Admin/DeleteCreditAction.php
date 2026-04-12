<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\Credit;

class DeleteCreditAction
{
    use ResolvesLocalCatalogWriteModels;

    public function handle(Credit $credit): void
    {
        $credit = $this->resolveLocalCredit($credit);
        $credit->delete();
    }
}
