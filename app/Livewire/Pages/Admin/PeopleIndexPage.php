<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminPeopleIndexQueryAction;
use Illuminate\Contracts\View\View;

class PeopleIndexPage extends PeoplePage
{
    public function render(BuildAdminPeopleIndexQueryAction $buildAdminPeopleIndexQuery): View
    {
        return $this->renderPeopleIndexPage($buildAdminPeopleIndexQuery);
    }
}
