<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BrowseTitlesPage extends Component
{
    use RendersPageView;

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

        return $this->renderBrowseView([
            'pageTitle' => 'Browse Titles',
            'metaDescription' => 'Browse the full imported title catalog across movies, series, documentaries, shorts, and specials.',
            'heading' => 'Browse Titles',
            'description' => 'The public title directory, mapped onto the imported MySQL schema and filtered to canonical title pages.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Movies', 'Series', 'Documentaries', 'Specials'],
            'actions' => [
                ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'ghost', 'icon' => 'tv'],
            ],
            'browserProps' => [
                'sort' => 'name',
                'pageName' => 'titles',
                'emptyHeading' => 'No titles match the current browse state.',
                'emptyText' => 'Try another route into the catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Browse Titles',
                description: 'Browse the full imported title catalog across movies, series, documentaries, shorts, and specials.',
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

        return $this->renderBrowseView([
            'pageTitle' => 'Browse Movies',
            'metaDescription' => 'Browse imported feature films from the remote IMDb catalog.',
            'heading' => 'Browse Movies',
            'description' => 'Feature films from the imported catalog, ranked by catalog popularity, rating, or release date.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Feature films', 'Ratings', 'Genre hubs'],
            'actions' => [
                ['label' => 'Top Rated Movies', 'href' => route('public.rankings.movies'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
            ],
            'browserProps' => [
                'types' => [TitleType::Movie->value],
                'sort' => 'popular',
                'pageName' => 'movies',
                'emptyHeading' => 'No movies are available right now.',
                'emptyText' => 'Check back soon or explore the broader title catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Browse Movies',
                description: 'Browse imported feature films from the remote IMDb catalog.',
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

        return $this->renderBrowseView([
            'pageTitle' => 'Browse TV Shows',
            'metaDescription' => 'Browse imported TV series and mini-series from the remote catalog, with season and episode routes when source data exists.',
            'heading' => 'Browse TV Shows',
            'description' => 'Series and mini-series from the imported catalog, with canonical season and episode routes when the source database provides that hierarchy.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Series', 'Mini-series', 'Catalog-backed'],
            'actions' => [
                ['label' => 'Top Rated Series', 'href' => route('public.rankings.series'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
            ],
            'browserProps' => [
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                'sort' => 'popular',
                'pageName' => 'tv-shows',
                'emptyHeading' => 'No TV shows are available right now.',
                'emptyText' => 'Check back soon or explore other areas of the catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Browse TV Shows',
                description: 'Browse imported TV series and mini-series from the remote catalog, with season and episode routes when source data exists.',
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

        return $this->renderBrowseView([
            'pageTitle' => $this->genre->name,
            'metaDescription' => 'Browse '.$this->genre->name.' titles from the imported catalog.',
            'heading' => $this->genre->name,
            'description' => 'Titles tagged with '.$this->genre->name.' in the imported IMDb data.',
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
                'emptyHeading' => 'No titles are available in this genre yet.',
                'emptyText' => 'Try another genre or return to the broader catalog.',
            ],
            'seo' => new PageSeoData(
                title: $this->genre->name,
                description: 'Browse '.$this->genre->name.' titles from the imported catalog.',
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

        return $this->renderBrowseView([
            'pageTitle' => 'Titles from '.$this->year,
            'metaDescription' => 'Browse imported titles released in '.$this->year.'.',
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
                'emptyHeading' => 'No titles were found for '.$this->year.'.',
                'emptyText' => 'Try a different year or browse the broader catalog.',
            ],
            'seo' => new PageSeoData(
                title: 'Titles from '.$this->year,
                description: 'Browse imported titles released in '.$this->year.'.',
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

        return $this->renderBrowseView([
            'pageTitle' => 'Top Rated Movies',
            'metaDescription' => 'Browse movies ordered by rating and vote volume from the imported catalog.',
            'heading' => 'Top Rated Movies',
            'description' => 'Feature films ranked by audience score and stabilized by vote volume from the imported data.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Rating-driven', 'Movie only'],
            'actions' => [
                ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
            ],
            'browserProps' => [
                'types' => [TitleType::Movie->value],
                'sort' => 'rating',
                'pageName' => 'top-rated-movies',
                'displayMode' => 'chart',
                'emptyHeading' => 'No rated movies are available yet.',
                'emptyText' => 'As vote counts accumulate, this page will surface the strongest films.',
            ],
            'seo' => new PageSeoData(
                title: 'Top Rated Movies',
                description: 'Browse movies ordered by rating and vote volume from the imported catalog.',
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

        return $this->renderBrowseView([
            'pageTitle' => 'Top Rated Series',
            'metaDescription' => 'Browse series and mini-series ordered by audience rating.',
            'heading' => 'Top Rated Series',
            'description' => 'TV and mini-series ranked by audience score and vote count from the imported catalog.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['TV and mini-series', 'Rating-driven'],
            'actions' => [
                ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'outline', 'icon' => 'tv'],
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
            ],
            'browserProps' => [
                'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                'sort' => 'rating',
                'pageName' => 'top-rated-series',
                'displayMode' => 'chart',
                'emptyHeading' => 'No rated series are available yet.',
                'emptyText' => 'As vote counts accumulate, this page will rank the strongest series.',
            ],
            'seo' => new PageSeoData(
                title: 'Top Rated Series',
                description: 'Browse series and mini-series ordered by audience rating.',
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

        return $this->renderBrowseView([
            'pageTitle' => 'Trending',
            'metaDescription' => 'Browse titles currently trending in the imported catalog.',
            'heading' => 'Trending Now',
            'description' => 'A live catalog feed weighted toward vote activity, recency, and catalog visibility.',
            'breadcrumbs' => $breadcrumbs,
            'badges' => ['Momentum', 'Catalog visibility', 'Fresh interest'],
            'actions' => [
                ['label' => 'Top Rated Movies', 'href' => route('public.rankings.movies'), 'variant' => 'outline', 'icon' => 'star'],
                ['label' => 'Top Rated Series', 'href' => route('public.rankings.series'), 'variant' => 'ghost', 'icon' => 'tv'],
            ],
            'browserProps' => [
                'sort' => 'trending',
                'pageName' => 'trending',
                'displayMode' => 'chart',
                'emptyHeading' => 'No trending titles are available yet.',
                'emptyText' => 'The chart will update as the imported catalog evolves.',
            ],
            'seo' => new PageSeoData(
                title: 'Trending',
                description: 'Browse titles currently trending in the imported catalog.',
                canonical: route('public.trending'),
                breadcrumbs: $breadcrumbs,
                paginationPageName: 'trending',
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderBrowseView(array $payload): View
    {
        $browserProps = is_array($payload['browserProps'] ?? null) ? $payload['browserProps'] : [];
        $displayMode = (string) ($browserProps['displayMode'] ?? 'catalog');
        $pageName = (string) ($browserProps['pageName'] ?? 'titles');
        $countryCode = str((string) request()->query('country', ''))
            ->trim()
            ->upper()
            ->toString();
        $countryLabel = null;

        if ($countryCode !== '' && class_exists(\Locale::class)) {
            $resolvedCountryLabel = \Locale::getDisplayRegion('-'.$countryCode, app()->getLocale());

            if (filled($resolvedCountryLabel)) {
                $countryLabel = $resolvedCountryLabel;
            }
        }

        $countryLabel ??= $countryCode !== '' ? $countryCode : null;

        return $this->renderPageView('catalog.browse', [
            ...$payload,
            'browserProps' => $browserProps,
            'displayMode' => $displayMode,
            'heroSlot' => $pageName === 'titles' ? 'browse-titles-hero' : 'catalog-browse-hero',
            'isChartPage' => $displayMode === 'chart',
            'countryCode' => $countryCode,
            'countryLabel' => $countryLabel,
            'badgeItems' => collect($payload['badges'] ?? [])
                ->map(function (string $badge): array {
                    $normalizedBadge = str($badge)->lower();
                    $badgeIcon = collect([
                        'film' => 'film',
                        'movie' => 'film',
                        'tv' => 'tv',
                        'series' => 'tv',
                        'rating' => 'star',
                        'discover' => 'sparkles',
                        'genre' => 'tag',
                        'year' => 'calendar-days',
                        'people' => 'users',
                        'search' => 'magnifying-glass',
                    ])->first(fn (string $icon, string $keyword): bool => $normalizedBadge->contains($keyword));

                    return [
                        'label' => $badge,
                        'icon' => $badgeIcon,
                    ];
                })
                ->values(),
        ]);
    }
}
