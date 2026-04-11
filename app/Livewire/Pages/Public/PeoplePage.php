<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\GetPeopleDirectorySnapshotAction;
use App\Actions\Catalog\LoadPersonDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Person;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PeoplePage extends Component
{
    use RendersPageView;

    public ?Person $person = null;

    public function mount(?Person $person = null): void
    {
        if ($person instanceof Person) {
            abort_unless($person->is_published, 404);
        }

        $this->person = $person;
    }

    public function render(
        LoadPersonDetailsAction $loadPersonDetails,
        GetPeopleDirectorySnapshotAction $getPeopleDirectorySnapshot,
    ): View {
        if (request()->routeIs('public.people.show')) {
            abort_unless($this->person instanceof Person, 404);

            return $this->renderPageView('people.show', $loadPersonDetails->handle($this->person));
        }

        return $this->renderPageView('people.index', [
            'directorySnapshot' => $getPeopleDirectorySnapshot->handle(),
            'seo' => new PageSeoData(
                title: 'Browse People',
                description: 'Browse actors, directors, writers, and other creators in the Screenbase catalog.',
                canonical: route('public.people.index'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Browse People'],
                ],
                paginationPageName: 'people',
                preserveQueryString: true,
                allowedQueryParameters: ['q', 'profession', 'sort'],
            ),
        ]);
    }
}
