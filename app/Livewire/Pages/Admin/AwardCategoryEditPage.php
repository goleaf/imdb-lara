<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class AwardCategoryEditPage extends AwardCategoriesPage
{
    public function render(): View
    {
        return $this->renderAwardCategoryEditPage();
    }
}
