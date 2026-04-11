<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminAkaTypesIndexQueryAction;
use Illuminate\Contracts\View\View;

class AkaTypesIndexPage extends AkaTypesPage
{
    public function render(BuildAdminAkaTypesIndexQueryAction $buildAdminAkaTypesIndexQuery): View
    {
        return $this->renderAkaTypesIndexPage($buildAdminAkaTypesIndexQuery);
    }
}
