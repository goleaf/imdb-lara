<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\CatalogBackendUnavailable;
use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Country;
use App\Models\Genre;
use App\Models\InterestCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class BrowseTitlesPage extends Component
{
    use RendersPageView;

    protected GetFeaturedInterestCategoriesAction $getFeaturedInterestCategories;

    public ?Genre $genre = null;

    public ?int $year = null;

    #[Url]
    public string $country = '';

    #[Url]
    public string $theme = '';

    public function boot(GetFeaturedInterestCategoriesAction $getFeaturedInterestCategories): void
    {
        $this->getFeaturedInterestCategories = $getFeaturedInterestCategories;
    }

    public function mount(?Genre $genre = null, ?int $year = null): void
    {
        $this->genre = $genre;
        $this->year = $year;
        $this->country = str($this->country)->trim()->upper()->toString();
        $this->theme = str($this->theme)->trim()->toString();

        if ($this->isYearBrowsePage()) {
            abort_unless(
                is_int($year) && $year >= 1888 && $year <= now()->addYear()->year,
                404,
            );
        }
    }

    public function render(): View
    {
        return $this->renderBrowseView($this->browsePageConfiguration());
    }

    /**
     * @return array<string, mixed>
     */
    private function browsePageConfiguration(): array
    {
        return match (true) {
            $this->isGenreBrowsePage() => $this->genreBrowsePageConfiguration(),
            $this->isYearBrowsePage() => $this->yearBrowsePageConfiguration(),
            default => $this->staticBrowsePageConfiguration($this->browsePageKey()),
        };
    }

    protected function browsePageKey(): string
    {
        return 'titles';
    }

    protected function isGenreBrowsePage(): bool
    {
        return false;
    }

    protected function isYearBrowsePage(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function staticBrowsePageConfiguration(string $pageKey): array
    {
        return match ($pageKey) {
            'movies' => $this->makeBrowsePageConfiguration(
                pageTitle: 'Browse Movies',
                metaDescription: 'Browse imported feature films from the remote IMDb catalog.',
                heading: 'Browse Movies',
                description: 'Feature films from the imported catalog, ranked by catalog popularity, rating, or release date.',
                canonical: route('public.movies.index'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Movies'],
                ],
                badges: ['Feature films', 'Ratings', 'Genre hubs'],
                actions: [
                    ['label' => 'Top Rated Movies', 'href' => route('public.rankings.movies'), 'variant' => 'outline', 'icon' => 'star'],
                    ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
                ],
                browserProps: [
                    'types' => [TitleType::Movie->value],
                    'sort' => 'popular',
                    'pageName' => 'movies',
                    'emptyHeading' => 'No movies are available right now.',
                    'emptyText' => 'Check back soon or explore the broader title catalog.',
                ],
            ),
            'series' => $this->makeBrowsePageConfiguration(
                pageTitle: 'Browse TV Shows',
                metaDescription: 'Browse imported TV series and mini-series from the remote catalog, with season and episode routes when source data exists.',
                heading: 'Browse TV Shows',
                description: 'Series and mini-series from the imported catalog, with canonical season and episode routes when the source database provides that hierarchy.',
                canonical: route('public.series.index'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'TV Shows'],
                ],
                badges: ['Series', 'Mini-series', 'Catalog-backed'],
                actions: [
                    ['label' => 'Top Rated Series', 'href' => route('public.rankings.series'), 'variant' => 'outline', 'icon' => 'star'],
                    ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
                ],
                browserProps: [
                    'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                    'sort' => 'popular',
                    'pageName' => 'tv-shows',
                    'emptyHeading' => 'No TV shows are available right now.',
                    'emptyText' => 'Check back soon or explore other areas of the catalog.',
                ],
            ),
            'top-rated-movies' => $this->makeBrowsePageConfiguration(
                pageTitle: 'Top Rated Movies',
                metaDescription: 'Browse movies ordered by rating and vote volume from the imported catalog.',
                heading: 'Top Rated Movies',
                description: 'Feature films ranked by audience score and stabilized by vote volume from the imported data.',
                canonical: route('public.rankings.movies'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Top Rated Movies'],
                ],
                badges: ['Rating-driven', 'Movie only'],
                actions: [
                    ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                    ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
                ],
                browserProps: [
                    'types' => [TitleType::Movie->value],
                    'sort' => 'rating',
                    'pageName' => 'top-rated-movies',
                    'displayMode' => 'chart',
                    'emptyHeading' => 'No rated movies are available yet.',
                    'emptyText' => 'As vote counts accumulate, this page will surface the strongest films.',
                ],
            ),
            'top-rated-series' => $this->makeBrowsePageConfiguration(
                pageTitle: 'Top Rated Series',
                metaDescription: 'Browse series and mini-series ordered by audience rating.',
                heading: 'Top Rated Series',
                description: 'TV and mini-series ranked by audience score and vote count from the imported catalog.',
                canonical: route('public.rankings.series'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Top Rated Series'],
                ],
                badges: ['TV and mini-series', 'Rating-driven'],
                actions: [
                    ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'outline', 'icon' => 'tv'],
                    ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'ghost', 'icon' => 'bolt'],
                ],
                browserProps: [
                    'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                    'sort' => 'rating',
                    'pageName' => 'top-rated-series',
                    'displayMode' => 'chart',
                    'emptyHeading' => 'No rated series are available yet.',
                    'emptyText' => 'As vote counts accumulate, this page will rank the strongest series.',
                ],
            ),
            'trending' => $this->makeBrowsePageConfiguration(
                pageTitle: 'Trending',
                metaDescription: 'Browse titles currently trending in the imported catalog.',
                heading: 'Trending Now',
                description: 'A live catalog feed weighted toward vote activity, recency, and catalog visibility.',
                canonical: route('public.trending'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Trending'],
                ],
                badges: ['Momentum', 'Catalog visibility', 'Fresh interest'],
                actions: [
                    ['label' => 'Top Rated Movies', 'href' => route('public.rankings.movies'), 'variant' => 'outline', 'icon' => 'star'],
                    ['label' => 'Top Rated Series', 'href' => route('public.rankings.series'), 'variant' => 'ghost', 'icon' => 'tv'],
                ],
                browserProps: [
                    'sort' => 'trending',
                    'pageName' => 'trending',
                    'showAll' => true,
                    'displayMode' => 'chart',
                    'emptyHeading' => 'No trending titles are available yet.',
                    'emptyText' => 'The chart will update as the imported catalog evolves.',
                ],
            ),
            default => $this->makeBrowsePageConfiguration(
                pageTitle: 'Browse Titles',
                metaDescription: 'Browse the full imported title catalog across movies, series, documentaries, shorts, and specials.',
                heading: 'Browse Titles',
                description: 'The public title directory, mapped onto the imported MySQL schema and filtered to canonical title pages.',
                canonical: route('public.titles.index'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Browse Titles'],
                ],
                badges: ['Movies', 'Series', 'Documentaries', 'Specials'],
                actions: [
                    ['label' => 'Browse Movies', 'href' => route('public.movies.index'), 'variant' => 'outline', 'icon' => 'film'],
                    ['label' => 'Browse TV Shows', 'href' => route('public.series.index'), 'variant' => 'ghost', 'icon' => 'tv'],
                ],
                browserProps: [
                    'sort' => 'name',
                    'pageName' => 'titles',
                    'emptyHeading' => 'No titles match the current browse state.',
                    'emptyText' => 'Try another route into the catalog.',
                ],
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function genreBrowsePageConfiguration(): array
    {
        abort_unless($this->genre instanceof Genre, 404);

        return $this->makeBrowsePageConfiguration(
            pageTitle: $this->genre->name,
            metaDescription: 'Browse '.$this->genre->name.' titles from the imported catalog.',
            heading: $this->genre->name,
            description: 'Titles tagged with '.$this->genre->name.' in the imported IMDb data.',
            canonical: route('public.genres.show', $this->genre),
            breadcrumbs: [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'All Titles', 'href' => route('public.titles.index')],
                ['label' => $this->genre->name],
            ],
            badges: ['Genre hub', 'Linked from title pages'],
            actions: [
                ['label' => 'Browse All Titles', 'href' => route('public.titles.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Search', 'href' => route('public.search', ['genre' => $this->genre->slug]), 'variant' => 'ghost', 'icon' => 'funnel'],
            ],
            browserProps: [
                'genre' => $this->genre->slug,
                'sort' => 'popular',
                'pageName' => 'genre-'.$this->genre->slug,
                'emptyHeading' => 'No titles are available in this genre yet.',
                'emptyText' => 'Try another genre or return to the broader catalog.',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function yearBrowsePageConfiguration(): array
    {
        abort_unless(is_int($this->year), 404);

        return $this->makeBrowsePageConfiguration(
            pageTitle: 'Titles from '.$this->year,
            metaDescription: 'Browse imported titles released in '.$this->year.'.',
            heading: (string) $this->year,
            description: 'Released titles from '.$this->year.', including movies, series, documentaries, specials, and shorts.',
            canonical: route('public.years.show', ['year' => $this->year]),
            breadcrumbs: [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'All Titles', 'href' => route('public.titles.index')],
                ['label' => (string) $this->year],
            ],
            badges: ['Year archive', 'SEO landing page'],
            actions: [
                ['label' => 'Trending', 'href' => route('public.trending'), 'variant' => 'outline', 'icon' => 'bolt'],
                ['label' => 'Browse All Titles', 'href' => route('public.titles.index'), 'variant' => 'ghost', 'icon' => 'film'],
            ],
            browserProps: [
                'year' => $this->year,
                'sort' => 'rating',
                'pageName' => 'year-'.$this->year,
                'emptyHeading' => 'No titles were found for '.$this->year.'.',
                'emptyText' => 'Try a different year or browse the broader catalog.',
            ],
        );
    }

    /**
     * @param  list<array{label: string, href: string, variant: string, icon: string}>  $actions
     * @param  list<string>  $badges
     * @param  array<string, mixed>  $browserProps
     * @return array<string, mixed>
     */
    private function makeBrowsePageConfiguration(
        string $pageTitle,
        string $metaDescription,
        string $heading,
        string $description,
        string $canonical,
        array $breadcrumbs,
        array $badges,
        array $actions,
        array $browserProps,
    ): array {
        return [
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription,
            'heading' => $heading,
            'description' => $description,
            'breadcrumbs' => $breadcrumbs,
            'badges' => $badges,
            'actions' => $actions,
            'browserProps' => $browserProps,
            'seo' => new PageSeoData(
                title: $pageTitle,
                description: $metaDescription,
                canonical: $canonical,
                breadcrumbs: $breadcrumbs,
                paginationPageName: (string) $browserProps['pageName'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderBrowseView(array $payload): View
    {
        $browserProps = is_array($payload['browserProps'] ?? null) ? $payload['browserProps'] : [];
        $displayMode = (string) ($browserProps['displayMode'] ?? 'catalog');
        $pageName = (string) ($browserProps['pageName'] ?? 'titles');
        $countryCode = $this->country;
        $countryLabel = $countryCode !== '' ? Country::labelForCode($countryCode) : null;
        $themeSlug = $this->theme;
        $selectedTheme = $this->resolveSelectedTheme($themeSlug);
        $themeSpotlightItems = collect();
        $themeSpotlightUnavailable = false;
        $clearThemeHref = null;

        $browserProps['theme'] = $themeSlug !== '' ? $themeSlug : null;
        $browserProps['country'] = $countryCode !== '' ? $countryCode : null;

        if ($displayMode !== 'chart') {
            ['items' => $themeSpotlightItems, 'isUnavailable' => $themeSpotlightUnavailable] = $this->themeSpotlightState($selectedTheme);
        }

        if ($themeSlug !== '') {
            $clearThemeHref = $this->browseRouteUrl(['theme' => '']);
        }

        $actions = collect($payload['actions'] ?? []);

        if ($clearThemeHref !== null) {
            $actions->prepend([
                'label' => 'Clear theme lane',
                'href' => $clearThemeHref,
                'icon' => 'x-mark',
                'variant' => 'outline',
            ]);
        }

        return $this->renderPageView('catalog.browse', [
            ...$payload,
            'browserProps' => $browserProps,
            'displayMode' => $displayMode,
            'heroSlot' => $pageName === 'titles' ? 'browse-titles-hero' : 'catalog-browse-hero',
            'isChartPage' => $displayMode === 'chart',
            'countryCode' => $countryCode,
            'countryLabel' => $countryLabel,
            'clearThemeHref' => $clearThemeHref,
            'selectedTheme' => $selectedTheme,
            'themeSpotlightItems' => $themeSpotlightItems,
            'themeSpotlightUnavailable' => $themeSpotlightUnavailable,
            'themeSpotlightStatusText' => CatalogBackendUnavailable::themeLaneMessage(),
            'themeDirectoryHref' => route('public.interest-categories.index'),
            'actions' => $actions->values()->all(),
            'badgeItems' => $this->badgeItems($payload['badges'] ?? []),
        ]);
    }

    private function resolveSelectedTheme(string $themeSlug): ?InterestCategory
    {
        if ($themeSlug === '' || preg_match('/-ic(?P<id>\d+)$/', $themeSlug, $matches) !== 1) {
            return null;
        }

        try {
            return InterestCategory::query()
                ->select(['interest_categories.id', 'interest_categories.name'])
                ->withDirectoryMetrics()
                ->where('interest_categories.id', (int) $matches['id'])
                ->first();
        } catch (Throwable $exception) {
            if (! CatalogBackendUnavailable::matches($exception)) {
                throw $exception;
            }

            report($exception);

            return null;
        }
    }

    /**
     * @return array{items: Collection<int, array<string, mixed>>, isUnavailable: bool}
     */
    private function themeSpotlightState(?InterestCategory $selectedTheme): array
    {
        try {
            $items = $this->getFeaturedInterestCategories
                ->handle(4, $selectedTheme?->id)
                ->map(fn (InterestCategory $interestCategory): array => [
                    'href' => $this->browseRouteUrl(['theme' => $interestCategory->slug]),
                    'description' => $interestCategory->description,
                    'interestCountBadgeLabel' => $interestCategory->interestCountBadgeLabel(),
                    'name' => $interestCategory->name,
                    'titleLinkedInterestCount' => $interestCategory->titleLinkedInterestCount(),
                    'titleLinkedInterestCountBadgeLabel' => $interestCategory->titleLinkedInterestCountBadgeLabel(),
                ])
                ->values();

            return [
                'items' => $items,
                'isUnavailable' => false,
            ];
        } catch (Throwable $exception) {
            if (! CatalogBackendUnavailable::matches($exception)) {
                throw $exception;
            }

            report($exception);

            return [
                'items' => collect(),
                'isUnavailable' => true,
            ];
        }
    }

    /**
     * @param  array{country?: string, theme?: string}  $queryOverrides
     */
    private function browseRouteUrl(array $queryOverrides = []): string
    {
        return route($this->browseRouteName(), [
            ...$this->browseRouteParameters(),
            ...$this->browseQueryParameters($queryOverrides),
        ]);
    }

    private function browseRouteName(): string
    {
        return match (true) {
            $this->genre instanceof Genre => 'public.genres.show',
            is_int($this->year) => 'public.years.show',
            default => match ($this->browsePageKey()) {
                'movies' => 'public.movies.index',
                'series' => 'public.series.index',
                'top-rated-movies' => 'public.rankings.movies',
                'top-rated-series' => 'public.rankings.series',
                'trending' => 'public.trending',
                default => 'public.titles.index',
            },
        };
    }

    /**
     * @return array{genre?: Genre, year?: int}
     */
    private function browseRouteParameters(): array
    {
        return match (true) {
            $this->genre instanceof Genre => ['genre' => $this->genre],
            is_int($this->year) => ['year' => $this->year],
            default => [],
        };
    }

    /**
     * @param  array{country?: string, theme?: string}  $queryOverrides
     * @return array{country?: string, theme?: string}
     */
    private function browseQueryParameters(array $queryOverrides = []): array
    {
        return collect([
            'country' => $this->country,
            'theme' => $this->theme,
            ...$queryOverrides,
        ])
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    /**
     * @param  list<string>  $badges
     * @return Collection<int, array{label: string, icon: string|null}>
     */
    private function badgeItems(array $badges): Collection
    {
        return collect($badges)
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
            ->values();
    }
}
