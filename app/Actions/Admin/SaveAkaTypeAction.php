<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\AkaType;

class SaveAkaTypeAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(AkaType $akaType, array $attributes): AkaType
    {
        $akaType->fill($this->normalizeAttributes($attributes));
        $akaType->save();

        return $akaType->refresh();
    }
}
