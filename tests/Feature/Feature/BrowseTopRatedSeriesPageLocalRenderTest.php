<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Mockery;
use Tests\Concerns\BuildsCatalogTitleFixtures;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BrowseTopRatedSeriesPageLocalRenderTest extends TestCase
{
    use BuildsCatalogTitleFixtures;
    use UsesCatalogOnlyApplication;

    public function test_top_rated_series_page_renders_lowercase_imdb_series_types(): void
    {
        $series = $this->makeCatalogTitle(
            attributes: [
                'id' => 1,
                'imdb_id' => 'tt1122334',
                'name' => 'Severance',
                'original_name' => 'Severance',
                'title_type' => 'tvseries',
                'release_year' => 2022,
                'runtime_minutes' => 50,
                'runtime_seconds' => 3000,
                'plot_outline' => 'A fractured workplace thriller where memory is divided between office life and the outside world.',
            ],
            statistic: $this->makeCatalogStatistic(1, 8.7, 845123),
            mediaAssets: [$this->makeCatalogPoster(1, 'https://images.test/severance-poster.jpg')],
        );

        $miniSeries = $this->makeCatalogTitle(
            attributes: [
                'id' => 2,
                'imdb_id' => 'tt2233445',
                'name' => 'Chernobyl',
                'original_name' => 'Chernobyl',
                'title_type' => 'tvminiseries',
                'release_year' => 2019,
                'runtime_minutes' => 60,
                'runtime_seconds' => 3600,
                'plot_outline' => 'A historical disaster drama about the Chernobyl nuclear accident and the people forced to contain it.',
            ],
            statistic: $this->makeCatalogStatistic(2, 9.3, 910234),
            mediaAssets: [$this->makeCatalogPoster(2, 'https://images.test/chernobyl-poster.jpg')],
        );

        $action = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $action
            ->shouldReceive('handleSafely')
            ->once()
            ->with($this->expectedSeriesFilters(), 12, 'top-rated-series')
            ->andReturn([
                'titles' => new Paginator(
                    items: new EloquentCollection([$miniSeries, $series]),
                    perPage: 12,
                    currentPage: 1,
                    options: [
                        'path' => route('public.rankings.series'),
                        'pageName' => 'top-rated-series',
                    ],
                ),
                'usingStaleCache' => false,
                'isUnavailable' => false,
            ]);

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $action);

        $response = $this->get(route('public.rankings.series'))
            ->assertOk()
            ->assertSee('Top Rated Series')
            ->assertSee('Chernobyl')
            ->assertSee('Severance')
            ->assertSee('A historical disaster drama about the Chernobyl nuclear accident')
            ->assertSee('View title')
            ->assertSeeHtml('data-slot="chart-title-card"');

        $this->assertSame(1, substr_count($response->getContent(), '910,234 votes'));
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
    private function expectedSeriesFilters(): array
    {
        return [
            'types' => ['series', 'mini-series'],
            'genre' => null,
            'theme' => null,
            'year' => null,
            'country' => null,
            'sort' => 'rating',
            'excludeEpisodes' => true,
        ];
    }
}
