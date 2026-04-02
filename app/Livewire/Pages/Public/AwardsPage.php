<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadAwardsArchiveAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AwardsPage extends Component
{
    use RendersPageView;

    public function render(LoadAwardsArchiveAction $loadAwardsArchive): View
    {
        return $this->renderPageView('awards.index', $loadAwardsArchive->handle());
    }
}
