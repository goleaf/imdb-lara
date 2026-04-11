<?php

namespace Tests\Feature\Feature\Search;

use App\Livewire\Search\SearchResults;
use App\Models\NameBasicMeterRanking;
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
        $title = $this->sampleTitle();

        $this->get(route('public.search', ['q' => $this->searchTermFor($title)]))
            ->assertOk()
            ->assertSee('Search Results')
            ->assertSee('Top Match')
            ->assertSee('Best title match')
            ->assertDontSee('No matches yet.')
            ->assertSee($title->name);
    }

    public function test_search_supports_live_genre_and_year_filters_against_remote_titles(): void
    {
        $title = $this->sampleTitle()->loadMissing('genres');
        $genre = $title->genres->firstOrFail();

        $this->get(route('public.search', [
            'q' => $this->searchTermFor($title),
            'genre' => $genre->slug,
            'yearFrom' => $title->release_year,
            'yearTo' => $title->release_year,
        ]))
            ->assertOk()
            ->assertSee($title->name);

        $this->get(route('public.search', [
            'q' => $this->searchTermFor($title),
            'yearFrom' => $title->release_year + 1,
        ]))
            ->assertOk()
            ->assertSee('No matches yet.')
            ->assertDontSee($title->name);
    }

    public function test_search_page_shows_a_no_results_state_when_nothing_matches(): void
    {
        $this->get(route('public.search', [
            'q' => 'zzzzzz-not-a-real-imdb-record',
            'country' => 'JP',
        ]))
            ->assertOk()
            ->assertSee('No matches yet.');
    }

    public function test_search_page_reuses_ranked_person_cards_for_people_matches(): void
    {
        $person = Person::query()
            ->select($this->remotePersonColumns())
            ->addSelect([
                'popularity_rank' => NameBasicMeterRanking::query()
                    ->select('current_rank')
                    ->whereColumn('name_basic_meter_rankings.name_basic_id', 'name_basics.id')
                    ->limit(1),
            ])
            ->published()
            ->whereHas('meterRanking')
            ->whereNotNull('name_basics.primaryname')
            ->orderBy('popularity_rank')
            ->first();

        if (! $person instanceof Person || ! is_int($person->popularity_rank)) {
            $this->markTestSkipped('The remote catalog does not currently expose a ranked person.');
        }

        $this->get(route('public.search', ['q' => $this->personSearchTermFor($person)]))
            ->assertOk()
            ->assertSee('Best people match')
            ->assertSee($person->name)
            ->assertSee('Rank #'.number_format($person->popularity_rank))
            ->assertSeeHtml('data-slot="search-top-match-person-metrics"');
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
