<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\CatalogBackendUnavailable;
use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use App\Models\InterestCategory;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Mockery;
use RuntimeException;
use Tests\Concerns\BuildsCatalogTitleFixtures;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BrowseMoviesPageLocalRenderTest extends TestCase
{
    use BuildsCatalogTitleFixtures;
    use UsesCatalogOnlyApplication;

    public function test_movies_page_renders_local_catalog_cards_and_theme_lanes_without_remote_queries(): void
    {
        $title = $this->makeCatalogTitle(
            attributes: [
                'id' => 1,
                'imdb_id' => 'tt0133093',
                'name' => 'The Matrix',
                'original_name' => 'The Matrix',
                'title_type' => 'movie',
                'release_year' => 1999,
                'runtime_minutes' => 136,
                'runtime_seconds' => 8160,
                'plot_outline' => 'A hacker learns the world is a simulation and joins the resistance.',
            ],
            genres: [$this->makeCatalogGenre(1, 'Science Fiction')],
            statistic: $this->makeCatalogStatistic(1, 8.7, 2100000),
            mediaAssets: [$this->makeCatalogPoster(1, 'https://images.test/the-matrix-poster.jpg')],
        );

        $themeLane = new InterestCategory;
        $themeLane->setRawAttributes([
            'id' => 7,
            'name' => 'Cyberpunk',
            'slug' => 'cyberpunk-ic7',
            'description' => 'High-tech dystopian futures and synthetic noir worlds.',
            'interests_count' => 12,
            'title_linked_interests_count' => 8,
            'subgenre_interests_count' => 3,
        ], sync: true);
        $themeLane->exists = true;

        $loadPublicTitleBrowserPage = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $loadPublicTitleBrowserPage
            ->shouldReceive('handleSafely')
            ->once()
            ->with($this->expectedMovieBrowserFilters(), 12, 'movies')
            ->andReturn($this->movieBrowserPageData($title));

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories
            ->shouldReceive('handle')
            ->once()
            ->with(4, null)
            ->andReturn(new EloquentCollection([$themeLane]));

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $loadPublicTitleBrowserPage);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);

        $this->get(route('public.movies.index'))
            ->assertOk()
            ->assertSee('Browse Movies')
            ->assertSee('The Matrix')
            ->assertSee('Science Fiction')
            ->assertSee('Theme lanes')
            ->assertSee('Cyberpunk')
            ->assertSeeHtml('data-slot="catalog-browse-theme-spotlight"');
    }

    public function test_movies_page_shows_a_catalog_outage_notice_when_the_catalog_query_fails(): void
    {
        $loadPublicTitleBrowserPage = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $loadPublicTitleBrowserPage
            ->shouldReceive('handleSafely')
            ->once()
            ->with($this->expectedMovieBrowserFilters(), 12, 'movies')
            ->andReturn($this->emptyMovieBrowserPageData(isUnavailable: true));

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories
            ->shouldReceive('handle')
            ->once()
            ->with(4, null)
            ->andReturn(new EloquentCollection);

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $loadPublicTitleBrowserPage);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);

        $this->get(route('public.movies.index'))
            ->assertOk()
            ->assertSee('Browse Movies')
            ->assertSee('Catalog temporarily unavailable.')
            ->assertSee(CatalogBackendUnavailable::userMessage())
            ->assertSeeHtml('data-slot="title-browser-status"');
    }

    public function test_movies_page_keeps_rendering_when_theme_lanes_are_unavailable(): void
    {
        $title = $this->makeCatalogTitle([
            'id' => 1,
            'imdb_id' => 'tt0133093',
            'name' => 'The Matrix',
            'original_name' => 'The Matrix',
            'title_type' => 'movie',
            'release_year' => 1999,
        ]);

        $loadPublicTitleBrowserPage = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $loadPublicTitleBrowserPage
            ->shouldReceive('handleSafely')
            ->once()
            ->with($this->expectedMovieBrowserFilters(), 12, 'movies')
            ->andReturn($this->movieBrowserPageData($title));

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories
            ->shouldReceive('handle')
            ->once()
            ->with(4, null)
            ->andThrow($this->remoteCatalogFailure());

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $loadPublicTitleBrowserPage);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);

        $this->get(route('public.movies.index'))
            ->assertOk()
            ->assertSee('Browse Movies')
            ->assertSee('The Matrix')
            ->assertSee('Theme lanes')
            ->assertSee('Theme lanes are temporarily unavailable.')
            ->assertSee(CatalogBackendUnavailable::themeLaneMessage())
            ->assertSee('Browse all themes');
    }

    /**
     * @return array{
     *     titles: Paginator,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    private function movieBrowserPageData(Title $title, bool $isUnavailable = false, bool $usingStaleCache = false): array
    {
        return [
            'titles' => new Paginator(
                items: new EloquentCollection([$title]),
                perPage: 12,
                currentPage: 1,
                options: [
                    'path' => route('public.movies.index'),
                    'pageName' => 'movies',
                ],
            ),
            'usingStaleCache' => $usingStaleCache,
            'isUnavailable' => $isUnavailable,
        ];
    }

    /**
     * @return array{
     *     titles: Paginator,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    private function emptyMovieBrowserPageData(bool $isUnavailable, bool $usingStaleCache = false): array
    {
        return [
            'titles' => new Paginator(
                items: new EloquentCollection,
                perPage: 12,
                currentPage: 1,
                options: [
                    'path' => route('public.movies.index'),
                    'pageName' => 'movies',
                ],
            ),
            'usingStaleCache' => $usingStaleCache,
            'isUnavailable' => $isUnavailable,
        ];
    }

    /**
     * @return array{
     *     types: list<string>,
     *     genre: null,
     *     theme: null,
     *     year: null,
     *     country: null,
     *     sort: string,
     *     excludeEpisodes: bool
     * }
     */
    private function expectedMovieBrowserFilters(): array
    {
        return [
            'types' => ['movie'],
            'genre' => null,
            'theme' => null,
            'year' => null,
            'country' => null,
            'sort' => 'popular',
            'excludeEpisodes' => true,
        ];
    }

    private function remoteCatalogFailure(): RuntimeException
    {
        return new RuntimeException(
            "SQLSTATE[HY000] [1226] User 'biayjdev_imdb_db' has exceeded the 'max_connections_per_hour' resource (current value: 1000)",
        );
    }
}
