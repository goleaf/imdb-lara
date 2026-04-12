<?php

namespace Tests\Feature\Feature\Search;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Search\BuildSearchTitleResultsQueryAction;
use App\Livewire\Search\SearchResults;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class SearchExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_search_page_highlights_top_matches_with_titles_and_people_lanes(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();
        $interestCategory = $this->sampleInterestCategory();

        $this->get(route('public.search', ['q' => $this->searchTermFor($title)]))
            ->assertOk()
            ->assertSee('Search The Global Catalog')
            ->assertSee('Browse by Theme')
            ->assertSee('Search Results')
            ->assertSee('Loading')
            ->assertSeeHtml('wire:intersect.once="__lazyLoadIsland"');

        $titleComponent = Livewire::test(SearchResults::class)
            ->set('query', $this->searchTermFor($title));

        /** @var array{
         *     hasAnyResults: bool,
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null},
         *     titleResultsCount: int
         * } $titleView
         */
        $titleView = $titleComponent->instance()->viewData();

        $this->assertTrue($titleView['hasAnyResults']);
        $this->assertSame('title', $titleView['topMatch']['type']);
        $this->assertInstanceOf(Title::class, $titleView['topMatch']['record']);
        $this->assertSame($title->id, $titleView['topMatch']['record']->id);

        $themeComponent = Livewire::test(SearchResults::class)
            ->set('query', $interestCategory->name);

        /** @var array{
         *     interestCategories: Collection<int, mixed>
         * } $themeView
         */
        $themeView = $themeComponent->instance()->viewData();

        $this->assertTrue(
            $themeView['interestCategories']->contains(
                fn ($match): bool => $match->getKey() === $interestCategory->getKey()
            ),
            'The matching interest category should be present in the themes lane.',
        );
    }

    public function test_search_supports_live_genre_and_year_filters_against_remote_titles(): void
    {
        $title = $this->sampleTitle()->loadMissing('genres');
        $genre = $title->genres->firstOrFail();

        $matchingComponent = Livewire::test(SearchResults::class)
            ->set('query', $this->searchTermFor($title))
            ->set('genre', $genre->slug)
            ->set('yearFrom', (string) $title->release_year)
            ->set('yearTo', (string) $title->release_year);

        /** @var array{
         *     titles: Paginator,
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null}
         * } $matchingView
         */
        $matchingView = $matchingComponent->instance()->viewData();
        $matchingTitles = collect($matchingView['titles']->items());

        $this->assertTrue(
            $matchingTitles->contains(fn (Title $visibleTitle): bool => $visibleTitle->is($title))
                || (
                    $matchingView['topMatch']['type'] === 'title'
                    && $matchingView['topMatch']['record'] instanceof Title
                    && $matchingView['topMatch']['record']->is($title)
                ),
            'The filtered search results should retain the matching title either as the top match or in the visible title lane.',
        );

        $emptyComponent = Livewire::test(SearchResults::class)
            ->set('query', $this->searchTermFor($title))
            ->set('yearFrom', (string) ($title->release_year + 1));

        /** @var array{hasAnyResults: bool, titles: Paginator} $emptyView */
        $emptyView = $emptyComponent->instance()->viewData();

        $this->assertFalse($emptyView['hasAnyResults']);
        $this->assertCount(0, $emptyView['titles']->items());
    }

    public function test_search_page_shows_a_no_results_state_when_nothing_matches(): void
    {
        $component = Livewire::test(SearchResults::class)
            ->set('query', 'zzzzzz-not-a-real-imdb-record')
            ->set('country', 'JP');

        /** @var array{
         *     hasAnyResults: bool,
         *     interestCategoryCount: int,
         *     peopleCount: int,
         *     titleResultsCount: int
         * } $view
         */
        $view = $component->instance()->viewData();

        $this->assertFalse($view['hasAnyResults']);
        $this->assertSame(0, $view['titleResultsCount']);
        $this->assertSame(0, $view['peopleCount']);
        $this->assertSame(0, $view['interestCategoryCount']);
    }

    public function test_search_page_supports_theme_filters_against_remote_titles(): void
    {
        $interestCategory = $this->sampleInterestCategory();
        $themeResultTitle = app(BuildSearchTitleResultsQueryAction::class)
            ->handle([
                'search' => '',
                'theme' => $interestCategory->slug,
                'sort' => 'popular',
            ])
            ->limit(12)
            ->first();

        if (! $themeResultTitle instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a visible title for the sampled interest category.');
        }

        $component = Livewire::test(SearchResults::class)
            ->set('theme', $interestCategory->slug);

        /** @var array{
         *     titles: Paginator,
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null}
         * } $view
         */
        $view = $component->instance()->viewData();
        $titles = collect($view['titles']->items());

        $this->assertTrue(
            $titles->contains(fn (Title $visibleTitle): bool => $visibleTitle->is($themeResultTitle))
                || (
                    $view['topMatch']['type'] === 'title'
                    && $view['topMatch']['record'] instanceof Title
                    && $view['topMatch']['record']->is($themeResultTitle)
                ),
            'Theme-filtered search results should include the sampled title either as the top match or in the visible title lane.',
        );
    }

    public function test_search_page_reuses_ranked_person_cards_for_people_matches(): void
    {
        if (! Person::catalogPeopleAvailable() || ! Title::catalogTablesAvailable('name_basic_meter_rankings')) {
            $this->markTestSkipped('The remote catalog does not currently expose a ranked person.');
        }

        $person = app(BuildPublicPeopleIndexQueryAction::class)
            ->handle(['sort' => 'popular'])
            ->first();

        if (! $person instanceof Person || ! is_int($person->popularity_rank)) {
            $this->markTestSkipped('The remote catalog does not currently expose a ranked person.');
        }

        $component = Livewire::test(SearchResults::class)
            ->set('query', $this->personSearchTermFor($person));

        /** @var array{
         *     topMatch: array{
         *         record: Title|Person|null,
         *         type: 'title'|'person'|null,
         *         popularityRankLabel?: string|null
         *     }
         * } $view
         */
        $view = $component->instance()->viewData();

        $this->assertSame('person', $view['topMatch']['type']);
        $this->assertInstanceOf(Person::class, $view['topMatch']['record']);
        $this->assertSame($person->id, $view['topMatch']['record']->id);
        $this->assertSame(
            'Rank #'.number_format($person->popularity_rank),
            $view['topMatch']['popularityRankLabel'] ?? null,
        );
    }

    public function test_search_page_deduplicates_a_title_top_match_from_the_title_grid(): void
    {
        $title = $this->sampleTitle();
        $component = Livewire::test(SearchResults::class)->set('query', $this->searchTermFor($title));

        /** @var array{
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null},
         *     titles: Paginator
         * } $view
         */
        $view = $component->instance()->viewData();
        $visibleTitles = collect($view['titles']->items());

        $this->assertTrue($view['topMatch']['record'] instanceof Title);
        $this->assertFalse(
            $visibleTitles->contains(fn (Title $visibleTitle): bool => $visibleTitle->is($view['topMatch']['record'])),
            'The top title match should not be repeated in the title results grid.',
        );
    }

    public function test_search_page_deduplicates_a_person_top_match_from_the_people_grid(): void
    {
        $person = $this->samplePerson();
        $component = Livewire::test(SearchResults::class)->set('query', $this->personSearchTermFor($person));

        /** @var array{
         *     people: Collection<int, Person>,
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null}
         * } $view
         */
        $view = $component->instance()->viewData();

        $this->assertTrue($view['topMatch']['record'] instanceof Person);
        $this->assertFalse(
            $view['people']->contains(fn (Person $visiblePerson): bool => $visiblePerson->is($view['topMatch']['record'])),
            'The top person match should not be repeated in the people results grid.',
        );
    }

    public function test_search_page_keeps_the_same_top_match_when_paging_titles(): void
    {
        $component = Livewire::test(SearchResults::class);

        /** @var array{
         *     titles: Paginator,
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null}
         * } $pageOne
         */
        $pageOne = $component->instance()->viewData();

        if (! $pageOne['titles']->hasMorePages()) {
            $this->markTestSkipped('The remote catalog did not produce a second title page for the search results contract.');
        }

        $this->assertTrue($pageOne['topMatch']['record'] instanceof Title);

        $component->call('setPage', 2, 'titles');

        /** @var array{
         *     titles: Paginator,
         *     topMatch: array{record: Title|Person|null, type: 'title'|'person'|null}
         * } $pageTwo
         */
        $pageTwo = $component->instance()->viewData();

        $this->assertTrue($pageTwo['topMatch']['record'] instanceof Title);
        $this->assertTrue(
            $pageTwo['topMatch']['record']->is($pageOne['topMatch']['record']),
            'The top title match should stay stable across paginated title results.',
        );
        $this->assertFalse(
            collect($pageTwo['titles']->items())->contains(
                fn (Title $visibleTitle): bool => $visibleTitle->is($pageTwo['topMatch']['record']),
            ),
            'The global top title match should not be repeated inside paginated title results.',
        );
    }
}
