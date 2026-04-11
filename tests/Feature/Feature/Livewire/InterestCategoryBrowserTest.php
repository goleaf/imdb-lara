<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\BuildPublicInterestCategoryIndexQueryAction;
use App\Livewire\Catalog\InterestCategoryBrowser;
use App\Models\InterestCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class InterestCategoryBrowserTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_interest_category_browser_uses_combobox_filters(): void
    {
        Livewire::withoutLazyLoading();
        $this->mockInterestCategoryQueryBuilder();

        Livewire::test(InterestCategoryBrowser::class)
            ->assertSeeHtml('data-slot="interest-category-browser-island"')
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

    public function test_interest_category_browser_renders_a_catalog_unavailable_state_when_the_remote_query_fails(): void
    {
        Livewire::withoutLazyLoading();

        $action = Mockery::mock(BuildPublicInterestCategoryIndexQueryAction::class);
        $action
            ->shouldReceive('handle')
            ->once()
            ->with([
                'search' => '',
                'showImages' => false,
                'sort' => 'popular',
            ])
            ->andThrow($this->remoteCatalogFailure());

        $this->app->instance(BuildPublicInterestCategoryIndexQueryAction::class, $action);

        Livewire::test(InterestCategoryBrowser::class)
            ->assertSee('Catalog temporarily unavailable.')
            ->assertSee('The imported IMDb catalog could not be reached. Try again in a few minutes.');
    }

    public function test_interest_category_browser_can_render_all_categories_with_images_and_without_pagination(): void
    {
        Livewire::withoutLazyLoading();

        $builder = Mockery::mock(Builder::class);
        $builder
            ->shouldReceive('get')
            ->once()
            ->andReturn(new EloquentCollection([
                tap(new InterestCategory, function (InterestCategory $interestCategory): void {
                    $interestCategory->forceFill([
                        'id' => 1,
                        'name' => 'Animation',
                        'directory_image_url' => 'https://images.example.test/categories/animation.jpg',
                        'directory_image_width' => 1600,
                        'directory_image_height' => 900,
                        'directory_image_type' => 'gallery',
                    ]);
                }),
            ]));

        $action = Mockery::mock(BuildPublicInterestCategoryIndexQueryAction::class);
        $action
            ->shouldReceive('handle')
            ->once()
            ->with([
                'search' => '',
                'showImages' => true,
                'sort' => 'popular',
            ])
            ->andReturn($builder);

        $this->app->instance(BuildPublicInterestCategoryIndexQueryAction::class, $action);

        Livewire::test(InterestCategoryBrowser::class, ['showAll' => true, 'showImages' => true])
            ->assertSee('Animation')
            ->assertSeeHtml('src="https://images.example.test/categories/animation.jpg"')
            ->assertDontSeeHtml('rel="next"');
    }

    private function mockInterestCategoryQueryBuilder(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder
            ->shouldReceive('simplePaginate')
            ->once()
            ->withAnyArgs()
            ->andReturn(new Paginator(
                items: collect(),
                perPage: 18,
                currentPage: 1,
                options: [
                    'path' => route('public.catalog.explorer', ['section' => 'themes']),
                    'pageName' => 'interest-categories',
                ],
            ));

        $action = Mockery::mock(BuildPublicInterestCategoryIndexQueryAction::class);
        $action
            ->shouldReceive('handle')
            ->once()
            ->with([
                'search' => '',
                'showImages' => false,
                'sort' => 'popular',
            ])
            ->andReturn($builder);

        $this->app->instance(BuildPublicInterestCategoryIndexQueryAction::class, $action);
    }

    private function remoteCatalogFailure(): RuntimeException
    {
        return new RuntimeException(
            "SQLSTATE[HY000] [1226] User 'biayjdev_imdb_db' has exceeded the 'max_connections_per_hour' resource (current value: 1000)",
        );
    }
}
