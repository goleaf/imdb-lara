<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminMediaAssetsIndexQueryAction;
use Illuminate\Contracts\View\View;

class MediaAssetsIndexPage extends MediaAssetsPage
{
    public function render(BuildAdminMediaAssetsIndexQueryAction $buildAdminMediaAssetsIndexQuery): View
    {
        return $this->renderMediaAssetsIndexPage($buildAdminMediaAssetsIndexQuery);
    }
}
