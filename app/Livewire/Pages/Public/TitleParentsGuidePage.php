<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadTitleParentsGuideAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TitleParentsGuidePage extends Component
{
    use RendersPageView;

    public ?Title $title = null;

    public function mount(Title $title): void
    {
        abort_unless($title->is_published, 404);

        $this->title = $title;
    }

    public function render(LoadTitleParentsGuideAction $loadTitleParentsGuide): View
    {
        abort_unless($this->title instanceof Title, 404);

        return $this->renderPageView('titles.parents-guide', $loadTitleParentsGuide->handle($this->title));
    }
}
