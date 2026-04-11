<?php

namespace Tests\Feature\Feature;

use App\Actions\Search\BuildDiscoveryQueryAction;
use App\Actions\Search\GetDiscoveryFilterOptionsAction;
use App\Actions\Search\GetDiscoveryTitleSuggestionsAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class DiscoverPageTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
        $this->mockDiscoverPageDependencies();
    }

    public function test_discover_page_ignores_invalid_title_type_query_values(): void
    {
        $this->get(route('public.discover', ['type' => 'not-a-real-type']))
            ->assertOk()
            ->assertSeeHtml('data-slot="discover-filters-island"')
            ->assertSee('All titles')
            ->assertDontSee('1 active');
    }

    public function test_discover_page_preserves_theme_in_the_canonical_url(): void
    {
        $theme = 'mind-bending-ic1';

        $this->get(route('public.discover', ['theme' => $theme]))
            ->assertOk()
            ->assertSeeHtml('href="'.route('public.discover', ['theme' => $theme]).'"');
    }

    private function mockDiscoverPageDependencies(): void
    {
        $buildDiscoveryQuery = Mockery::mock(BuildDiscoveryQueryAction::class);
        $buildDiscoveryQuery
            ->shouldReceive('handle')
            ->andReturnUsing(fn (): Builder => Title::query()
                ->selectCatalogCardColumns()
                ->publishedCatalog()
                ->whereKey(-1));

        $getDiscoveryFilterOptions = Mockery::mock(GetDiscoveryFilterOptionsAction::class);
        $getDiscoveryFilterOptions
            ->shouldReceive('handle')
            ->andReturn([
                'countries' => [],
                'genres' => new EloquentCollection,
                'interestCategories' => new EloquentCollection,
                'languages' => [],
                'titleTypes' => TitleType::cases(),
                'minimumRatings' => range(10, 1),
                'runtimeOptions' => [],
                'sortOptions' => [
                    ['value' => 'popular', 'label' => 'Popularity'],
                ],
                'voteThresholdOptions' => [],
                'years' => [],
                'awardOptions' => [],
            ]);

        $getDiscoveryTitleSuggestions = Mockery::mock(GetDiscoveryTitleSuggestionsAction::class);
        $getDiscoveryTitleSuggestions
            ->shouldReceive('handle')
            ->andReturn(new EloquentCollection);

        $this->app->instance(BuildDiscoveryQueryAction::class, $buildDiscoveryQuery);
        $this->app->instance(GetDiscoveryFilterOptionsAction::class, $getDiscoveryFilterOptions);
        $this->app->instance(GetDiscoveryTitleSuggestionsAction::class, $getDiscoveryTitleSuggestions);
    }
}
