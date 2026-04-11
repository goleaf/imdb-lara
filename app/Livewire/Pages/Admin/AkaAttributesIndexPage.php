<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminAkaAttributesIndexQueryAction;
use Illuminate\Contracts\View\View;

class AkaAttributesIndexPage extends AkaAttributesPage
{
    public function render(BuildAdminAkaAttributesIndexQueryAction $buildAdminAkaAttributesIndexQuery): View
    {
        return $this->renderAkaAttributesIndexPage($buildAdminAkaAttributesIndexQuery);
    }
}
