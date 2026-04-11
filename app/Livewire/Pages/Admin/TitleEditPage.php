<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class TitleEditPage extends TitlesPage
{
    public function render(): View
    {
        return $this->renderTitleEditPage();
    }
}
