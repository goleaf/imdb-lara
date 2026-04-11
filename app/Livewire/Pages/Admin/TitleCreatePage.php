<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class TitleCreatePage extends TitlesPage
{
    public function render(): View
    {
        return $this->renderTitleCreatePage();
    }
}
