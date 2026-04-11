<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class AkaTypeCreatePage extends AkaTypesPage
{
    public function render(): View
    {
        return $this->renderAkaTypeCreatePage();
    }
}
