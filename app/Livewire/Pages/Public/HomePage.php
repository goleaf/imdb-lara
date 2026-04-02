<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Home\GetHeroSpotlightAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
    use RendersPageView;

    public function render(GetHeroSpotlightAction $getHeroSpotlight): View
    {
        $heroSpotlight = $getHeroSpotlight->handle();
        $heroImage = $heroSpotlight?->titleImages?->firstWhere('kind', MediaKind::Backdrop)
            ?? $heroSpotlight?->titleImages?->firstWhere('kind', MediaKind::Poster);
        $heroBackdrop = $heroSpotlight?->preferredBackdrop()
            ?? ($heroSpotlight?->relationLoaded('titleImages')
                ? $heroSpotlight->titleImages->firstWhere('kind', MediaKind::Backdrop)
                : null);
        $heroPoster = $heroSpotlight?->preferredPoster()
            ?? ($heroSpotlight?->relationLoaded('titleImages')
                ? $heroSpotlight->titleImages->firstWhere('kind', MediaKind::Poster)
                : null);
        $heroStatistic = $heroSpotlight?->statistic;
        $heroGenres = $heroSpotlight?->previewGenres(4) ?? collect();
        $heroCast = $heroSpotlight?->credits?->pluck('person')->filter()->unique('id')->take(4)->values() ?? collect();
        $heroTrailer = $heroSpotlight?->titleVideos?->first();

        return $this->renderPageView('home', [
            'heroSpotlight' => $heroSpotlight,
            'heroBackdrop' => $heroBackdrop,
            'heroPoster' => $heroPoster,
            'heroStatistic' => $heroStatistic,
            'heroGenres' => $heroGenres,
            'heroCast' => $heroCast,
            'heroTrailer' => $heroTrailer,
            'seo' => new PageSeoData(
                title: 'Home',
                description: 'Discover trending titles, top rated movies and TV shows, coming soon releases, recently added titles, popular people, latest trailers, latest reviews, featured public lists, genres, and browse by year on Screenbase.',
                canonical: route('public.home'),
                openGraphTitle: $heroSpotlight?->name
                    ? $heroSpotlight->name.' on Screenbase'
                    : 'Screenbase',
                openGraphDescription: $heroSpotlight?->tagline
                    ?: $heroSpotlight?->plot_outline
                    ?: 'Discover trending titles, top rated movies and TV shows, coming soon releases, recently added titles, popular people, latest trailers, latest reviews, featured public lists, genres, and browse by year on Screenbase.',
                openGraphImage: $heroImage?->url,
                openGraphImageAlt: $heroImage?->alt_text ?: $heroSpotlight?->name,
            ),
        ]);
    }
}
