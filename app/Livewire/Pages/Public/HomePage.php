<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Actions\Catalog\GetFeaturedGenresAction;
use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Catalog\GetFeaturedTitlesAction;
use App\Actions\Home\GetAwardsSpotlightNominationsAction;
use App\Actions\Home\GetHeroSpotlightAction;
use App\Actions\Home\GetLatestTrailerTitlesAction;
use App\Actions\Home\GetPopularPeopleAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomePage extends Component
{
    use RendersPageView;

    public function render(
        GetHeroSpotlightAction $getHeroSpotlight,
        GetLatestTrailerTitlesAction $getLatestTrailerTitles,
        GetAwardsSpotlightNominationsAction $getAwardsSpotlightNominations,
        BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
        GetFeaturedTitlesAction $getFeaturedTitles,
        GetFeaturedGenresAction $getFeaturedGenres,
        GetFeaturedInterestCategoriesAction $getFeaturedInterestCategories,
        GetPopularPeopleAction $getPopularPeople,
    ): View {
        $heroSpotlight = $getHeroSpotlight->handle();
        $heroBackdrop = $heroSpotlight?->preferredBackdrop();
        $heroPoster = $heroSpotlight?->preferredPoster();
        $heroStatistic = $heroSpotlight?->statistic;
        $heroGenres = $heroSpotlight?->previewGenres(4) ?? collect();
        $heroCast = $heroSpotlight?->credits?->pluck('person')->filter()->unique('id')->take(4)->values() ?? collect();
        $heroTrailer = $heroSpotlight?->preferredVideo();
        $awardsSpotlightEntries = $getAwardsSpotlightNominations->handle(4);
        $featuredAwardEntry = $awardsSpotlightEntries->first();
        $supportingAwardEntries = $awardsSpotlightEntries->slice(1)->values();
        $latestTrailerTitles = $getLatestTrailerTitles->handle(4);
        $featuredTrailerTitle = $latestTrailerTitles->first();
        $supportingTrailerTitles = $latestTrailerTitles->slice(1)->values();
        $featuredTrailerAsset = $featuredTrailerTitle?->preferredBackdrop() ?: $featuredTrailerTitle?->preferredPoster();
        $trendingTitles = $buildPublicTitleIndexQuery
            ->handle(['sort' => 'trending'])
            ->limit(6)
            ->get();
        $topMovieTitles = $buildPublicTitleIndexQuery
            ->handle([
                'sort' => 'rating',
                'types' => [TitleType::Movie->value],
            ])
            ->limit(6)
            ->get();
        $topSeriesTitles = $buildPublicTitleIndexQuery
            ->handle([
                'sort' => 'rating',
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
            ])
            ->limit(6)
            ->get();

        return $this->renderPageView('home', [
            'featuredGenres' => $getFeaturedGenres->handle(8),
            'featuredInterestCategories' => $getFeaturedInterestCategories->handle(4),
            'featuredTitles' => $getFeaturedTitles->handle(6),
            'awardsSpotlightEntries' => $awardsSpotlightEntries,
            'featuredAwardEntry' => $featuredAwardEntry,
            'supportingAwardEntries' => $supportingAwardEntries,
            'heroSpotlight' => $heroSpotlight,
            'heroBackdrop' => $heroBackdrop,
            'heroPoster' => $heroPoster,
            'heroStatistic' => $heroStatistic,
            'heroGenres' => $heroGenres,
            'heroCast' => $heroCast,
            'heroTrailer' => $heroTrailer,
            'latestTrailerTitles' => $latestTrailerTitles,
            'featuredTrailerTitle' => $featuredTrailerTitle,
            'featuredTrailerAsset' => $featuredTrailerAsset,
            'supportingTrailerTitles' => $supportingTrailerTitles,
            'popularPeople' => $getPopularPeople->handle(6),
            'topMovieTitles' => $topMovieTitles,
            'topSeriesTitles' => $topSeriesTitles,
            'trendingTitles' => $trendingTitles,
            'seo' => new PageSeoData(
                title: 'Home',
                description: 'Browse the imported IMDb catalog through trending titles, top rated movies and series, featured genres, people pages, and interest-category lanes.',
                canonical: route('public.home'),
                openGraphTitle: $heroSpotlight?->name
                    ? $heroSpotlight->name.' on Screenbase'
                    : 'Screenbase',
                openGraphDescription: $heroSpotlight?->summaryText()
                    ?: 'Browse the imported IMDb catalog through trending titles, top rated movies and series, featured genres, people pages, and interest-category lanes.',
                openGraphImage: ($heroBackdrop ?? $heroPoster)?->url,
                openGraphImageAlt: ($heroBackdrop ?? $heroPoster)?->alt_text ?: $heroSpotlight?->name,
            ),
        ]);
    }
}
