<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SearchPage extends Component
{
    use RendersPageView;

    public function render(): View
    {
        return $this->renderPageView('search.index', [
            'seo' => new PageSeoData(
                title: 'Search',
                description: 'Search titles, people, and public lists with top matches, poster-first results, and focused title discovery filters.',
                canonical: route('public.search'),
                robots: 'noindex,follow',
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Search'],
                ],
                paginationPageName: 'titles',
                preserveQueryString: true,
                allowedQueryParameters: [
                    'q',
                    'type',
                    'genre',
                    'yearFrom',
                    'yearTo',
                    'ratingMin',
                    'ratingMax',
                    'votesMin',
                    'language',
                    'country',
                    'runtime',
                    'status',
                    'sort',
                ],
            ),
        ]);
    }
}
