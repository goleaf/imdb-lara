<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class GenreEditPage extends GenresPage
{
    public function render(): View
    {
        return $this->renderGenreEditPage();
    }
}
