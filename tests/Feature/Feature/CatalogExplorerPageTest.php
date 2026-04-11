<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\BuildPublicInterestCategoryIndexQueryAction;
use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use Illuminate\Pagination\Paginator;
use Mockery;
use RuntimeException;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CatalogExplorerPageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_catalog_titles_section_stays_available_when_the_remote_catalog_is_unavailable(): void
    {
        $action = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $action
            ->shouldReceive('handleSafely')
            ->once()
            ->andReturn([
                'titles' => new Paginator(collect(), 12, 1, [
                    'path' => route('public.catalog.explorer'),
                    'pageName' => 'catalog-titles',
                ]),
                'usingStaleCache' => false,
                'isUnavailable' => true,
            ]);

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $action);

        $this->get(route('public.catalog.explorer'))
            ->assertOk()
            ->assertSee('Catalog Explorer')
            ->assertSee('Live catalog temporarily unavailable')
            ->assertSee('The live title catalog is unavailable right now. Try again shortly.');
    }

    public function test_catalog_people_section_stays_available_when_the_remote_catalog_is_unavailable(): void
    {
        $this->mockCatalogFailure(BuildPublicPeopleIndexQueryAction::class);

        $this->get(route('public.catalog.explorer', ['section' => 'people']))
            ->assertOk()
            ->assertSee('Catalog Explorer')
            ->assertSee('Catalog temporarily unavailable.')
            ->assertSee('The imported IMDb catalog could not be reached. Try again in a few minutes.');
    }

    public function test_catalog_themes_section_stays_available_when_the_remote_catalog_is_unavailable(): void
    {
        $this->mockCatalogFailure(BuildPublicInterestCategoryIndexQueryAction::class);

        $this->get(route('public.catalog.explorer', ['section' => 'themes']))
            ->assertOk()
            ->assertSee('Catalog Explorer')
            ->assertSee('Catalog temporarily unavailable.')
            ->assertSee('The imported IMDb catalog could not be reached. Try again in a few minutes.');
    }

    private function mockCatalogFailure(string $actionClass): void
    {
        $action = Mockery::mock($actionClass);
        $action
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::type('array'))
            ->andThrow($this->remoteCatalogFailure());

        $this->app->instance($actionClass, $action);
    }

    private function remoteCatalogFailure(): RuntimeException
    {
        return new RuntimeException(
            "SQLSTATE[HY000] [1226] User 'biayjdev_imdb_db' has exceeded the 'max_connections_per_hour' resource (current value: 1000)",
        );
    }
}
