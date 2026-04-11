<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class PersonEditPage extends PeoplePage
{
    public function render(): View
    {
        return $this->renderPersonEditPage();
    }
}
