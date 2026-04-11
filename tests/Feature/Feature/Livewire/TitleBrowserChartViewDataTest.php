<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use App\Livewire\Catalog\TitleBrowser;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\BuildsCatalogTitleFixtures;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleBrowserChartViewDataTest extends TestCase
{
    use BuildsCatalogTitleFixtures;
    use UsesCatalogOnlyApplication;

    public function test_chart_mode_prepares_chart_card_view_data_in_livewire(): void
    {
        $title = $this->makeCatalogTitle(
            attributes: [
                'id' => 1,
                'imdb_id' => 'tt0133093',
                'name' => 'The Matrix',
                'original_name' => 'Matrix',
                'title_type' => 'movie',
                'release_year' => 1999,
                'runtime_minutes' => 136,
                'runtime_seconds' => 8160,
                'origin_country' => 'US',
                'plot_outline' => 'A hacker learns the world is a simulation and joins the resistance.',
            ],
            genres: [$this->makeCatalogGenre(1, 'Science Fiction')],
            statistic: $this->makeCatalogStatistic(1, 8.7, 2100000),
            mediaAssets: [$this->makeCatalogPoster(1, 'https://images.test/the-matrix-poster.jpg')],
        );

        $action = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $action
            ->shouldReceive('handleSafely')
            ->andReturn([
                'titles' => new Paginator(
                    items: new EloquentCollection([$title]),
                    perPage: 12,
                    currentPage: 1,
                    options: [
                        'path' => route('public.rankings.movies'),
                        'pageName' => 'titles',
                    ],
                ),
                'usingStaleCache' => false,
                'isUnavailable' => false,
            ]);

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $action);

        $viewData = Livewire::test(TitleBrowser::class, [
            'displayMode' => 'chart',
        ])->instance()->viewData();

        $this->assertSame([
            'comparisonLabel' => '2,100,000 votes',
            'comparisonToken' => null,
            'movementAmount' => 0,
            'movementDirection' => 'steady',
            'movementIcon' => 'minus',
            'movementLabel' => 'Steady',
            'movementNote' => null,
            'originCountryCode' => 'US',
            'originCountryLabel' => 'United States',
            'originalTitle' => 'Matrix',
            'rank' => 1,
            'releaseYear' => 1999,
            'runtimeLabel' => $title->runtimeMinutesLabel(),
            'summaryText' => 'A hacker learns the world is a simulation and joins the resistance.',
            'titleUrl' => route('public.titles.show', $title),
            'voteLabel' => '2,100,000 votes',
        ], collect($viewData['chartRows'][$title->id])
            ->except(['genres', 'poster'])
            ->all());

        $this->assertSame('Science Fiction', $viewData['chartRows'][$title->id]['genres']->first()?->name);
        $this->assertSame('https://images.test/the-matrix-poster.jpg', $viewData['chartRows'][$title->id]['poster']?->url);
    }
}
