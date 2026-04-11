<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class PersonCreatePage extends PeoplePage
{
    public function render(): View
    {
        return $this->renderPersonCreatePage();
    }
}
