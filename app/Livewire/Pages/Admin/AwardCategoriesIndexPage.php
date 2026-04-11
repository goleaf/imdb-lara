<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminAwardCategoriesIndexQueryAction;
use Illuminate\Contracts\View\View;

class AwardCategoriesIndexPage extends AwardCategoriesPage
{
    public function render(BuildAdminAwardCategoriesIndexQueryAction $buildAdminAwardCategoriesIndexQuery): View
    {
        return $this->renderAwardCategoriesIndexPage($buildAdminAwardCategoriesIndexQuery);
    }
}
