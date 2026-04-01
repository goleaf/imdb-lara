<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\GetFeaturedGenresAction;
use App\Actions\Catalog\GetFeaturedTitlesAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DiscoverPage extends Component
{
    use RendersLegacyPage;

    public function render(
        GetFeaturedGenresAction $getFeaturedGenres,
        GetFeaturedTitlesAction $getFeaturedTitles,
    ): View {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Discovery'],
        ];

        return $this->renderLegacyPage('discover.index', [
            'featuredGenres' => $getFeaturedGenres->handle(),
            'featuredTitles' => $getFeaturedTitles->handle(3),
            'seo' => new PageSeoData(
                title: 'Discovery',
                description: 'Use Screenbase discovery filters to browse titles by genre, type, popularity, and rating.',
                canonical: route('public.discover'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'discover',
                preserveQueryString: true,
                allowedQueryParameters: ['q', 'genre', 'type', 'sort', 'minimumRating'],
            ),
        ]);
    }
}
