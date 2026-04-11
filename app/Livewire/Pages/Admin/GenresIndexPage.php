<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminGenresIndexQueryAction;
use Illuminate\Contracts\View\View;

class GenresIndexPage extends GenresPage
{
    public function render(BuildAdminGenresIndexQueryAction $buildAdminGenresIndexQuery): View
    {
        return $this->renderGenresIndexPage($buildAdminGenresIndexQuery);
    }
}
