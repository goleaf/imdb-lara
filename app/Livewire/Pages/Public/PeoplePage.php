<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadPersonDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\Person;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PeoplePage extends Component
{
    use RendersLegacyPage;

    public ?Person $person = null;

    public function mount(?Person $person = null): void
    {
        if ($person instanceof Person) {
            abort_unless(
                $person->is_published || (auth()->user()?->can('view', $person) ?? false),
                404,
            );
        }

        $this->person = $person;
    }

    public function render(LoadPersonDetailsAction $loadPersonDetails): View
    {
        if (request()->routeIs('public.people.show')) {
            abort_unless($this->person instanceof Person, 404);

            return $this->renderLegacyPage('people.show', $loadPersonDetails->handle($this->person));
        }

        return $this->renderLegacyPage('people.index', [
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
