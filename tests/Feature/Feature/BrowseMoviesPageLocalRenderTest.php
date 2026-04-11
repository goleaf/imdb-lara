<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\CatalogBackendUnavailable;
use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use App\Models\Genre;
use App\Models\InterestCategory;
use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BrowseMoviesPageLocalRenderTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_movies_page_renders_local_catalog_cards_and_theme_lanes_without_remote_queries(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
            'runtimeminutes' => 136,
            'runtimeSeconds' => 8160,
        ]);

        $genre = Genre::query()->create([
            'name' => 'Science Fiction',
        ]);

        DB::connection('imdb_mysql')->table('movie_genres')->insert([
            'movie_id' => $title->id,
            'genre_id' => $genre->id,
        ]);

        MoviePrimaryImage::query()->create([
            'movie_id' => $title->id,
            'url' => 'https://images.test/the-matrix-poster.jpg',
            'width' => 1000,
            'height' => 1500,
            'type' => 'poster',
        ]);

        MoviePlot::query()->create([
            'movie_id' => $title->id,
            'plot' => 'A hacker learns the world is a simulation and joins the resistance.',
        ]);

        TitleStatistic::query()->create([
            'movie_id' => $title->id,
            'aggregate_rating' => 8.7,
            'vote_count' => 2100000,
        ]);

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
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
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
        $titles = Title::query()
            ->selectCatalogCardColumns()
            ->with([
                'genres:id,name',
                'statistic:movie_id,aggregate_rating,vote_count',
                'primaryImageRecord:movie_id,url,width,height,type',
                'plotRecord:movie_id,plot',
            ])
            ->whereKey($title->id)
            ->get();

        return [
            'titles' => new Paginator(
                items: $titles,
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
                items: collect(),
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
