<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadTitleMediaArchiveAction;
use App\Enums\TitleMediaArchiveKind;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TitleMediaArchivePage extends Component
{
    use RendersPageView;

    public ?Title $title = null;

    public ?TitleMediaArchiveKind $archiveKind = null;

    public function mount(Title $title, string $archive): void
    {
        abort_unless($title->is_published, 404);

        $this->title = $title;
        $this->archiveKind = TitleMediaArchiveKind::tryFrom($archive);

        abort_unless($this->archiveKind instanceof TitleMediaArchiveKind, 404);
    }

    public function render(LoadTitleMediaArchiveAction $loadTitleMediaArchive): View
    {
        abort_unless($this->title instanceof Title, 404);
        abort_unless($this->archiveKind instanceof TitleMediaArchiveKind, 404);

        return $this->renderPageView(
            'titles.media-archive',
            $loadTitleMediaArchive->handle($this->title, $this->archiveKind),
        );
    }
}
