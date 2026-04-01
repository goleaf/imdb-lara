<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Home\GetLatestTrailerTitlesAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LatestTrailersPage extends Component
{
    use RendersLegacyPage;

    public function render(GetLatestTrailerTitlesAction $getLatestTrailerTitles): View
    {
        $titles = $getLatestTrailerTitles
            ->query()
            ->simplePaginate(12)
            ->withQueryString();

        return $this->renderLegacyPage('trailers.index', [
            'titles' => $titles,
            'seo' => new PageSeoData(
                title: 'Latest Trailers',
                description: 'Watch the freshest public trailers, clips, and featurettes added to Screenbase titles.',
                canonical: route('public.trailers.latest'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Latest Trailers'],
                ],
                paginationPageName: 'page',
            ),
        ]);
    }
}
