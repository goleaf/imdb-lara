<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Home\GetHeroSpotlightAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
    use RendersLegacyPage;

    public function render(GetHeroSpotlightAction $getHeroSpotlight): View
    {
        $heroSpotlight = $getHeroSpotlight->handle();
        $heroImage = $heroSpotlight?->titleImages?->firstWhere('kind', MediaKind::Backdrop)
            ?? $heroSpotlight?->titleImages?->firstWhere('kind', MediaKind::Poster);

        return $this->renderLegacyPage('home', [
            'heroSpotlight' => $heroSpotlight,
            'seo' => new PageSeoData(
                title: 'Home',
                description: 'Discover trending titles, top rated movies and TV shows, popular people, fresh trailers, and community lists on Screenbase.',
                canonical: route('public.home'),
                openGraphTitle: $heroSpotlight?->name
                    ? $heroSpotlight->name.' on Screenbase'
                    : 'Screenbase',
                openGraphDescription: $heroSpotlight?->tagline
                    ?: $heroSpotlight?->plot_outline
                    ?: 'Discover trending titles, top rated movies and TV shows, popular people, fresh trailers, and community lists on Screenbase.',
                openGraphImage: $heroImage?->url,
                openGraphImageAlt: $heroImage?->alt_text ?: $heroSpotlight?->name,
            ),
        ]);
    }
}
