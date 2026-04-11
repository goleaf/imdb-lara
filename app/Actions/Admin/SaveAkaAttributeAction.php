<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\AkaAttribute;

class SaveAkaAttributeAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(AkaAttribute $akaAttribute, array $attributes): AkaAttribute
    {
        $akaAttribute->fill($this->normalizeAttributes($attributes));
        $akaAttribute->save();

        return $akaAttribute->refresh();
    }
}
