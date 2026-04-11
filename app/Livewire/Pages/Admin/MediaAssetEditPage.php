<?php

namespace App\Livewire\Pages\Admin;

use Illuminate\Contracts\View\View;

class MediaAssetEditPage extends MediaAssetsPage
{
    public function render(): View
    {
        return $this->renderMediaAssetEditPage();
    }
}
