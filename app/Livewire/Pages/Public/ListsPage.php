<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Lists\BuildPublicListsIndexQueryAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ListsPage extends Component
{
    use RendersPageView;
    use WithPagination;

    private const SORT_OPTIONS = ['recent', 'most_titles', 'title'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $sort = 'recent';

    public function mount(): void
    {
        if (! in_array($this->sort, self::SORT_OPTIONS, true)) {
            $this->sort = 'recent';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'lists');
    }

    public function updatedSort(): void
    {
        if (! in_array($this->sort, self::SORT_OPTIONS, true)) {
            $this->sort = 'recent';
        }

        $this->resetPage(pageName: 'lists');
    }

    public function render(BuildPublicListsIndexQueryAction $buildPublicListsIndexQuery): View
    {
        $sort = in_array($this->sort, self::SORT_OPTIONS, true)
            ? $this->sort
            : 'recent';

        $lists = $buildPublicListsIndexQuery
            ->handle($this->search, $sort)
            ->paginate(12, pageName: 'lists')
            ->withQueryString();

        return $this->renderPageView('lists.index', [
            'lists' => $lists,
            'search' => $this->search,
            'sort' => $sort,
            'sortOptions' => [
                ['value' => 'recent', 'label' => 'Recently updated', 'icon' => 'sparkles'],
                ['value' => 'most_titles', 'label' => 'Most titles', 'icon' => 'queue-list'],
                ['value' => 'title', 'label' => 'Alphabetical', 'icon' => 'bars-arrow-down'],
            ],
            'seo' => new PageSeoData(
                title: 'Browse Public Lists',
                description: 'Browse public member-curated title lists, discover list makers, and open shareable Screenbase collections.',
                canonical: route('public.lists.index'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Public Lists'],
                ],
                paginationPageName: 'lists',
                preserveQueryString: true,
                allowedQueryParameters: ['q', 'sort'],
            ),
        ]);
    }
}
