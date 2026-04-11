<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class GenreCreatePage extends GenresPage
{
    public function render(): View
    {
        return $this->renderGenreCreatePage();
    }
}
