<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use Illuminate\Contracts\View\View;

class TitlesIndexPage extends TitlesPage
{
    public function render(BuildAdminTitlesIndexQueryAction $buildAdminTitlesIndexQuery): View
    {
        return $this->renderTitlesIndexPage($buildAdminTitlesIndexQuery);
    }
}
