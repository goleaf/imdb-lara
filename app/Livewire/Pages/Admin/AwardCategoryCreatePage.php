<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class AwardCategoryCreatePage extends AwardCategoriesPage
{
    public function render(): View
    {
        return $this->renderAwardCategoryCreatePage();
    }
}
