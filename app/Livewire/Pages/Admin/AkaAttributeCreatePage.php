<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class AkaAttributeCreatePage extends AkaAttributesPage
{
    public function render(): View
    {
        return $this->renderAkaAttributeCreatePage();
    }
}
