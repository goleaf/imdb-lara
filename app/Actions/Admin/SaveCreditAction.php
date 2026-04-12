<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\Credit;
use App\Models\LocalCredit;

class SaveCreditAction
{
    use NormalizesAdminAttributes;
    use ResolvesLocalCatalogWriteModels;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Credit $credit, array $attributes): LocalCredit
    {
        $credit = $credit->exists ? $this->resolveLocalCredit($credit) : new LocalCredit;
        $credit->fill($this->normalizeAttributes($attributes));
        $credit->is_principal = (bool) ($attributes['is_principal'] ?? false);
        $credit->save();

        return $credit->refresh();
    }
}
