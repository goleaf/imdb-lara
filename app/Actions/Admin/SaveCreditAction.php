<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\Credit;

class SaveCreditAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Credit $credit, array $attributes): Credit
    {
        $credit->fill($this->normalizeAttributes($attributes));
        $credit->is_principal = (bool) ($attributes['is_principal'] ?? false);
        $credit->save();

        return $credit->refresh();
    }
}
