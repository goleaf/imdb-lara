<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\AwardCategory;

class SaveAwardCategoryAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(AwardCategory $awardCategory, array $attributes): AwardCategory
    {
        $awardCategory->fill($this->normalizeAttributes($attributes));
        $awardCategory->save();

        return $awardCategory->refresh();
    }
}
