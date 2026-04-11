<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Content\LoadChangelogPageAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChangesPage extends Component
{
    use RendersPageView;

    public function render(LoadChangelogPageAction $loadChangelogPageAction): View
    {
        return $this->renderPageView('changes.index', [
            ...$loadChangelogPageAction->handle(),
            'seo' => new PageSeoData(
                title: 'Changes',
                description: 'Read the latest Screenbase portal updates, release notes, and catalog improvements published from repository changelog files.',
                canonical: route('public.changes'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Changes'],
                ],
            ),
        ]);
    }
}
