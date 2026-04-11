<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DiscoverPage extends Component
{
    use RendersPageView;

    public function render(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Discovery'],
        ];

        return $this->renderPageView('discover.index', [
            'seo' => new PageSeoData(
                title: 'Discovery',
                description: 'Use Screenbase advanced discovery filters to explore titles by type, release date, awards, ratings, votes, language, runtime, and country.',
                canonical: route('public.discover'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'discover',
                preserveQueryString: true,
                allowedQueryParameters: ['q', 'genre', 'theme', 'type', 'sort', 'minimumRating', 'yearFrom', 'yearTo', 'votesMin', 'language', 'country', 'runtime', 'awards'],
            ),
        ]);
    }
}
