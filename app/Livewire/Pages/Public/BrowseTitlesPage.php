<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BrowseTitlesPage extends Component
{
    use RendersLegacyPage;

    public ?Genre $genre = null;

    public ?int $year = null;

    public function mount(?Genre $genre = null, ?int $year = null): void
    {
        $this->genre = $genre;
        $this->year = $year;

        if (request()->routeIs('public.years.show')) {
            abort_unless(
                is_int($year) && $year >= 1888 && $year <= now()->addYear()->year,
                404,
            );
        }
    }

    public function render(): View
    {
        return match (true) {
            request()->routeIs('public.movies.index') => $this->renderMovies(),
            request()->routeIs('public.series.index') => $this->renderSeries(),
            request()->routeIs('public.genres.show') => $this->renderGenre(),
            request()->routeIs('public.years.show') => $this->renderYear(),
            request()->routeIs('public.rankings.movies') => $this->renderTopRatedMovies(),
            request()->routeIs('public.rankings.series') => $this->renderTopRatedSeries(),
            request()->routeIs('public.trending') => $this->renderTrending(),
            default => $this->renderAllTitles(),
        };
    }

    private function renderAllTitles(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Browse Titles'],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Browse Titles',
            'metaDescription' => 'Browse published Screenbase titles across movies, series, documentaries, shorts, and specials.',
            'heading' => 'Browse Titles',
            'description' => 'The full public title directory, excluding episode records from the main grid so series navigation stays canonical.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Movies', 'Series', 'Documentaries', 'Specials'],
            'actions' => array_values(array_filter([
                route('public.movies.index')
                    ? ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film']
                    : null,
                route('public.series.index')
                    ? ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'ghost', 'icon' => 'tv']
                    : null,
            ])),
            'browserProps' => [
                'sort' => 'name',
                'pageName' => 'titles',
                'emptyHeading' => 'No published titles match the current catalog.',
                'emptyText' => 'Check back soon or browse discovery and search instead.',
            ],
            'seo' => new PageSeoData(
                title: 'Browse Titles',
                description: 'Browse published Screenbase titles across movies, series, documentaries, shorts, and specials.',
                canonical: route('public.titles.index'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'titles',
            ),
        ]);
    }

    private function renderMovies(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Movies'],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Browse Movies',
            'metaDescription' => 'Browse published movies on Screenbase with ratings, reviews, and genre links.',
            'heading' => 'Browse Movies',
            'description' => 'Explore released feature films through the existing Screenbase card system, rating aggregates, and public detail pages.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Feature films', 'Audience ratings', 'Editorial discovery'],
            'actions' => [
                ['label' => 'Top Rated Movies', 'href' => route('public.rankings.movies'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
            ],
            'browserProps' => [
                'types' => [TitleType::Movie->value],
                'sort' => 'popular',
                'pageName' => 'movies',
                'emptyHeading' => 'No published movies are available right now.',
                'emptyText' => 'Check back soon or explore the broader title catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Browse Movies',
                description: 'Browse published movies on Screenbase with ratings, reviews, and genre links.',
                canonical: route('public.movies.index'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'movies',
            ),
        ]);
    }

    private function renderSeries(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'TV Shows'],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Browse TV Shows',
            'metaDescription' => 'Browse published TV series and mini-series on Screenbase.',
            'heading' => 'Browse TV Shows',
            'description' => 'Track ongoing series, mini-series, and season structures with the same catalog, review, and people architecture used across the public site.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Series', 'Mini-series', 'Season-aware pages'],
            'actions' => [
                ['label' => 'Top Rated Series', 'href' => route('public.rankings.series'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'variant' => 'ghost', 'icon' => 'play'],
            ],
            'browserProps' => [
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                'sort' => 'popular',
                'pageName' => 'tv-shows',
                'emptyHeading' => 'No published TV shows are available right now.',
                'emptyText' => 'Check back soon or explore other areas of the catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Browse TV Shows',
                description: 'Browse published TV series and mini-series on Screenbase.',
                canonical: route('public.series.index'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'tv-shows',
            ),
        ]);
    }

    private function renderGenre(): View
    {
        abort_unless($this->genre instanceof Genre, 404);

        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'All Titles', 'href' => route('public.titles.index')],
            ['label' => $this->genre->name],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => $this->genre->name,
            'metaDescription' => $this->genre->description ?: 'Browse '.$this->genre->name.' titles, reviews, and discovery pages on Screenbase.',
            'heading' => $this->genre->name,
            'description' => $this->genre->description ?: 'Published titles tagged with '.$this->genre->name.'.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Genre hub', 'Linked from title pages'],
            'actions' => [
                ['label' => 'Browse All Titles', 'href' => route('public.titles.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Search', 'href' => route('public.search', ['genre' => $this->genre->slug]), 'variant' => 'ghost', 'icon' => 'funnel'],
            ],
            'browserProps' => [
                'genre' => $this->genre->slug,
                'sort' => 'popular',
                'pageName' => 'genre-'.$this->genre->slug,
                'emptyHeading' => 'No published titles are available in this genre yet.',
                'emptyText' => 'Try another genre or return to the broader catalog.',
            ],
            'seo' => new PageSeoData(
                title: $this->genre->name,
                description: $this->genre->description ?: 'Browse '.$this->genre->name.' titles, reviews, and discovery pages on Screenbase.',
                canonical: route('public.genres.show', $this->genre),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'genre-'.$this->genre->slug,
            ),
        ]);
    }

    private function renderYear(): View
    {
        abort_unless(is_int($this->year), 404);

        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'All Titles', 'href' => route('public.titles.index')],
            ['label' => (string) $this->year],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Titles from '.$this->year,
            'metaDescription' => 'Browse public title pages released in '.$this->year.' on Screenbase.',
            'heading' => (string) $this->year,
            'description' => 'Released titles from '.$this->year.', including movies, series, documentaries, specials, and shorts.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Year archive', 'SEO landing page'],
            'actions' => [
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'outline', 'icon' => 'bolt'],
                ['label' => 'Browse All Titles', 'href' => route('public.titles.index'), 'variant' => 'ghost', 'icon' => 'film'],
            ],
            'browserProps' => [
                'year' => $this->year,
                'sort' => 'rating',
                'pageName' => 'year-'.$this->year,
                'emptyHeading' => 'No published titles were found for '.$this->year.'.',
                'emptyText' => 'Try a different year or browse the broader catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Titles from '.$this->year,
                description: 'Browse public title pages released in '.$this->year.' on Screenbase.',
                canonical: route('public.years.show', ['year' => $this->year]),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'year-'.$this->year,
            ),
        ]);
    }

    private function renderTopRatedMovies(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Top Rated Movies'],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Top Rated Movies',
            'metaDescription' => 'Browse Screenbase movies ordered by rating and rating volume.',
            'heading' => 'Top Rated Movies',
            'description' => 'Feature films ranked by aggregate audience rating, then stabilized by vote volume.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Rating-driven', 'Movie only'],
            'actions' => [
                ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Latest Reviews', 'href' => route('public.reviews.latest'), 'variant' => 'ghost', 'icon' => 'chat-bubble-left-right'],
            ],
            'browserProps' => [
                'types' => [TitleType::Movie->value],
                'sort' => 'rating',
                'pageName' => 'top-rated-movies',
                'emptyHeading' => 'No rated movies are available yet.',
                'emptyText' => 'As ratings arrive, this page will surface the strongest films.',
            ],
            'seo' => new PageSeoData(
                title: 'Top Rated Movies',
                description: 'Browse Screenbase movies ordered by rating and rating volume.',
                canonical: route('public.rankings.movies'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'top-rated-movies',
            ),
        ]);
    }

    private function renderTopRatedSeries(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Top Rated Series'],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Top Rated Series',
            'metaDescription' => 'Browse Screenbase series and mini-series ordered by audience rating.',
            'heading' => 'Top Rated Series',
            'description' => 'TV and mini-series ranked by audience score with review and watchlist momentum alongside each record.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['TV and mini-series', 'Rating-driven'],
            'actions' => [
                ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'outline', 'icon' => 'tv'],
                ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'variant' => 'ghost', 'icon' => 'play'],
            ],
            'browserProps' => [
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                'sort' => 'rating',
                'pageName' => 'top-rated-series',
                'emptyHeading' => 'No rated series are available yet.',
                'emptyText' => 'Once ratings accumulate, this page will rank the strongest series.',
            ],
            'seo' => new PageSeoData(
                title: 'Top Rated Series',
                description: 'Browse Screenbase series and mini-series ordered by audience rating.',
                canonical: route('public.rankings.series'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'top-rated-series',
            ),
        ]);
    }

    private function renderTrending(): View
    {
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Trending'],
        ];

        return $this->renderLegacyPage('catalog.browse', [
            'pageTitle' => 'Trending',
            'metaDescription' => 'Browse titles trending on Screenbase by watchlist activity and review momentum.',
            'heading' => 'Trending Now',
            'description' => 'A public feed weighted toward watchlist saves, review volume, and popularity ranking.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Momentum', 'Watchlists', 'Recent discussion'],
            'actions' => [
                ['label' => 'Latest Reviews', 'href' => route('public.reviews.latest'), 'variant' => 'outline', 'icon' => 'chat-bubble-left-right'],
                ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'variant' => 'ghost', 'icon' => 'play'],
            ],
            'browserProps' => [
                'sort' => 'trending',
                'pageName' => 'trending',
                'emptyHeading' => 'No trending titles are available yet.',
                'emptyText' => 'As the community adds watchlists and reviews, this page will update.',
            ],
            'seo' => new PageSeoData(
                title: 'Trending',
                description: 'Browse titles trending on Screenbase by watchlist activity and review momentum.',
                canonical: route('public.trending'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'trending',
            ),
        ]);
    }
}
