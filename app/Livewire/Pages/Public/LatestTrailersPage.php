<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Home\GetLatestTrailerTitlesAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LatestTrailersPage extends Component
{
    use RendersPageView;

    public function render(GetLatestTrailerTitlesAction $getLatestTrailerTitles): View
    {
        $titles = $getLatestTrailerTitles
            ->query()
            ->simplePaginate(12)
            ->withQueryString();

        return $this->renderPageView('trailers.index', [
            'titles' => $titles,
            'seo' => new PageSeoData(
                title: 'Trailers',
                description: 'Browse trailer-linked titles, clips, and featurettes from the imported Screenbase catalog.',
                canonical: route('public.trailers.latest'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Trailers'],
                ],
                paginationPageName: 'page',
            ),
        ]);
    }
}
