<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class AkaAttributeEditPage extends AkaAttributesPage
{
    public function render(): View
    {
        return $this->renderAkaAttributeEditPage();
    }
}
