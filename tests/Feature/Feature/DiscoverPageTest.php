<?php

namespace Tests\Feature\Feature;

use App\Actions\Search\BuildDiscoveryViewDataAction;
use App\Actions\Search\GetDiscoveryFilterOptionsAction;
use App\Actions\Search\GetDiscoveryTitleSuggestionsAction;
use App\Enums\TitleType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Mockery;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class DiscoverPageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_discover_page_ignores_invalid_title_type_query_values(): void
    {
        $this->mockDiscoverPageDependencies([
            'type' => 'not-a-real-type',
        ]);

        $this->get(route('public.discover', ['type' => 'not-a-real-type']))
            ->assertOk()
            ->assertSeeHtml('data-slot="discover-filters-island"')
            ->assertSee('All titles')
            ->assertDontSee('1 active');
    }

    public function test_discover_page_preserves_theme_in_the_canonical_url(): void
    {
        $theme = 'mind-bending-ic1';

        $this->mockDiscoverPageDependencies(
            [
                'theme' => $theme,
            ],
            [
                'activeFilterCount' => 1,
                'activeFilters' => collect([
                    ['icon' => 'squares-2x2', 'label' => 'Mind Bending'],
                ]),
                'coreFiltersActive' => true,
            ],
        );

        $this->get(route('public.discover', ['theme' => $theme]))
            ->assertOk()
            ->assertSeeHtml('href="'.route('public.discover', ['theme' => $theme]).'"');
    }

    /**
     * @param  array<string, string|null>  $expectedFilters
     * @param  array<string, mixed>  $viewDataOverrides
     */
    private function mockDiscoverPageDependencies(array $expectedFilters, array $viewDataOverrides = []): void
    {
        $buildDiscoveryViewData = Mockery::mock(BuildDiscoveryViewDataAction::class);
        $buildDiscoveryViewData
            ->shouldReceive('handle')
            ->once()
            ->withArgs(function (array $filters, int $perPage, string $pageName) use ($expectedFilters): bool {
                return $filters === array_merge($this->defaultDiscoverFilters(), $expectedFilters)
                    && $perPage === 12
                    && $pageName === 'discover';
            })
            ->andReturn($this->discoverViewData($viewDataOverrides));

        $getDiscoveryFilterOptions = Mockery::mock(GetDiscoveryFilterOptionsAction::class);
        $getDiscoveryFilterOptions
            ->shouldReceive('handle')
            ->never();

        $getDiscoveryTitleSuggestions = Mockery::mock(GetDiscoveryTitleSuggestionsAction::class);
        $getDiscoveryTitleSuggestions
            ->shouldReceive('handle')
            ->never();

        $this->app->instance(BuildDiscoveryViewDataAction::class, $buildDiscoveryViewData);
        $this->app->instance(GetDiscoveryFilterOptionsAction::class, $getDiscoveryFilterOptions);
        $this->app->instance(GetDiscoveryTitleSuggestionsAction::class, $getDiscoveryTitleSuggestions);
    }

    /**
     * @return array<string, string|null>
     */
    private function defaultDiscoverFilters(): array
    {
        return [
            'search' => '',
            'genre' => null,
            'theme' => null,
            'type' => null,
            'sort' => 'popular',
            'minimumRating' => null,
            'yearFrom' => null,
            'yearTo' => null,
            'votesMin' => null,
            'language' => null,
            'country' => null,
            'runtime' => null,
            'awards' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function discoverViewData(array $overrides = []): array
    {
        return array_merge([
            'activeFilterCount' => 0,
            'activeFilters' => collect(),
            'awardOptions' => [],
            'countries' => [],
            'titles' => new Paginator(
                items: new EloquentCollection,
                perPage: 12,
                currentPage: 1,
                options: [
                    'path' => route('public.discover'),
                    'pageName' => 'discover',
                ],
            ),
            'genres' => new EloquentCollection,
            'interestCategories' => new EloquentCollection,
            'keywordActive' => false,
            'languages' => [],
            'coreFiltersActive' => false,
            'loadingTargets' => 'search,genre,theme,type,sort,minimumRating,yearFrom,yearTo,votesMin,language,country,runtime,awards',
            'titleTypes' => TitleType::cases(),
            'minimumRatings' => range(10, 1),
            'orderingActive' => false,
            'originFiltersActive' => false,
            'releaseFiltersActive' => false,
            'runtimeOptions' => [],
            'showSummary' => false,
            'signalFiltersActive' => false,
            'sortLabel' => 'Popularity',
            'sortOptions' => [
                ['value' => 'popular', 'label' => 'Popularity', 'icon' => 'fire'],
            ],
            'searchSuggestions' => new EloquentCollection,
            'titleResultsCount' => 0,
            'voteThresholdOptions' => [],
            'years' => [],
        ], $overrides);
    }
}
