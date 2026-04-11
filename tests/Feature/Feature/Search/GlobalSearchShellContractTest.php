<?php

namespace Tests\Feature\Feature\Search;

use App\Actions\Search\BuildGlobalSearchViewDataAction;
use App\Livewire\Search\GlobalSearch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class GlobalSearchShellContractTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_global_search_exposes_sheaf_control_slots_for_overlay_actions_and_empty_states(): void
    {
        Livewire::withoutLazyLoading();

        $buildGlobalSearchViewData = Mockery::mock(BuildGlobalSearchViewDataAction::class);
        $buildGlobalSearchViewData
            ->shouldReceive('handle')
            ->times(3)
            ->andReturnUsing(fn (?string $query, int $perGroup = 4, array $titleFilters = []): array => $this->fakeViewData($query));

        $this->app->instance(BuildGlobalSearchViewDataAction::class, $buildGlobalSearchViewData);

        Livewire::test(GlobalSearch::class)
            ->assertSeeHtml('data-slot="global-search-close"')
            ->assertSeeHtml('data-slot="global-search-recent-empty"')
            ->set('query', 'zzzxxyyqqqnomatch')
            ->assertSeeHtml('data-slot="global-search-no-matches"')
            ->set('query', 'matrix')
            ->assertSeeHtml('data-slot="global-search-view-all"');
    }

    /**
     * @return array{
     *     hasSearchTerm: bool,
     *     hasSuggestions: bool,
     *     visibleSections: list<array{
     *         chipClass: string,
     *         copy: string,
     *         icon: string,
     *         items: EloquentCollection<int, mixed>,
     *         key: 'interestCategories'|'people'|'titles',
     *         label: string,
     *         panelClass: string
     *     }>,
     *     suggestions: array{
     *         interestCategories: EloquentCollection<int, mixed>,
     *         people: EloquentCollection<int, mixed>,
     *         titles: EloquentCollection<int, mixed>
     *     },
     *     topSuggestion: array{
     *         record: null,
     *         type: null
     *     },
     *     trimmedQuery: string
     * }
     */
    private function fakeViewData(?string $query): array
    {
        $trimmedQuery = trim((string) $query);
        $hasSearchTerm = mb_strlen($trimmedQuery) >= 2;
        $emptyCollection = new EloquentCollection;

        return [
            'hasSearchTerm' => $hasSearchTerm,
            'hasSuggestions' => false,
            'visibleSections' => [],
            'suggestions' => [
                'interestCategories' => $emptyCollection,
                'people' => $emptyCollection,
                'titles' => $emptyCollection,
            ],
            'topSuggestion' => [
                'record' => null,
                'type' => null,
            ],
            'trimmedQuery' => $trimmedQuery,
        ];
    }
}
