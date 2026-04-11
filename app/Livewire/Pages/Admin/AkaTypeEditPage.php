<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class AkaTypeEditPage extends AkaTypesPage
{
    public function render(): View
    {
        return $this->renderAkaTypeEditPage();
    }
}
