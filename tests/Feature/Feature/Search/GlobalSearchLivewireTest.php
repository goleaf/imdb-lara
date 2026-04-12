<?php

namespace Tests\Feature\Feature\Search;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Livewire\Search\GlobalSearch;
use App\Models\Person;
use App\Models\Title;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class GlobalSearchLivewireTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_global_search_shows_live_title_and_people_suggestions_and_redirects_to_full_results(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();
        $person = $this->samplePerson();
        $interestCategory = $this->sampleInterestCategory();
        $personSearch = $this->personSearchTermFor($person);

        Livewire::test(GlobalSearch::class)
            ->set('query', $this->searchTermFor($title))
            ->assertSee('Find titles, people, and themes fast')
            ->assertSeeHtml('data-slot="global-search-loading"')
            ->assertSeeHtml('data-slot="global-search-results"')
            ->assertSee('Top suggestion')
            ->assertSee($title->name)
            ->assertDontSee('Lists')
            ->set('query', $personSearch)
            ->assertSee('Top suggestion')
            ->assertSee($person->name)
            ->set('query', $interestCategory->name)
            ->assertSee('Themes')
            ->assertSee($interestCategory->name)
            ->call('submitSearch')
            ->assertRedirect(route('public.search', ['q' => $interestCategory->name]));
    }

    public function test_global_search_requires_a_meaningful_query_before_showing_suggestions(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();

        Livewire::test(GlobalSearch::class)
            ->set('query', 's')
            ->assertDontSeeHtml('sb-search-panel--titles')
            ->assertDontSeeHtml('sb-search-panel--people')
            ->assertDontSee($title->name);
    }

    public function test_global_search_shows_a_single_no_matches_state_without_empty_result_lanes(): void
    {
        Livewire::withoutLazyLoading();

        Livewire::test(GlobalSearch::class)
            ->set('query', 'zzzxxyyqqqnomatch')
            ->assertSee('No quick matches')
            ->assertDontSee('Poster-led matches with year and type.')
            ->assertDontSee('Portrait-first profiles with profession cues.')
            ->assertDontSee('Interest-category lanes from the imported discovery graph.')
            ->assertDontSeeHtml('sb-search-panel--titles')
            ->assertDontSeeHtml('sb-search-panel--people');
    }

    public function test_global_search_surfaces_a_ranked_person_top_suggestion_and_people_metrics(): void
    {
        Livewire::withoutLazyLoading();

        if (! Person::catalogPeopleAvailable() || ! Title::catalogTablesAvailable('name_basic_meter_rankings')) {
            $this->markTestSkipped('The remote catalog does not currently expose a ranked person.');
        }

        $person = app(BuildPublicPeopleIndexQueryAction::class)
            ->handle(['sort' => 'popular'])
            ->first();

        if (! $person instanceof Person || ! is_int($person->popularity_rank)) {
            $this->markTestSkipped('The remote catalog does not currently expose a ranked person.');
        }

        Livewire::test(GlobalSearch::class)
            ->set('query', $this->personSearchTermFor($person))
            ->assertSee('Top suggestion')
            ->assertSee($person->name)
            ->assertSee('Rank #'.number_format($person->popularity_rank))
            ->assertSeeHtml('data-slot="global-search-top-suggestion"')
            ->assertSeeHtml('data-slot="global-search-person-suggestion-metrics"');
    }

    public function test_global_search_deduplicates_a_person_top_suggestion_from_people_results(): void
    {
        Livewire::withoutLazyLoading();

        $person = $this->samplePerson();
        $component = Livewire::test(GlobalSearch::class)
            ->set('query', $this->personSearchTermFor($person))
            ->assertSee('Top suggestion');

        $this->assertSame(
            1,
            substr_count($component->html(), route('public.people.show', $person)),
            'The top person suggestion should not be repeated in the people list.',
        );
    }

    public function test_global_search_deduplicates_a_title_top_suggestion_from_title_results(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();
        $component = Livewire::test(GlobalSearch::class)
            ->set('query', $this->searchTermFor($title))
            ->assertSee('Top suggestion');

        $this->assertSame(
            1,
            substr_count($component->html(), route('public.titles.show', $title)),
            'The top title suggestion should not be repeated in the titles list.',
        );
    }

    public function test_global_search_surfaces_interest_category_matches_in_a_themes_lane(): void
    {
        Livewire::withoutLazyLoading();

        $interestCategory = $this->sampleInterestCategory();
        $component = Livewire::test(GlobalSearch::class)
            ->set('query', $interestCategory->name)
            ->assertSee('Themes')
            ->assertSee($interestCategory->name)
            ->assertSee(route('public.interest-categories.show', $interestCategory), false);

        $this->assertGreaterThanOrEqual(
            1,
            substr_count($component->html(), route('public.interest-categories.show', $interestCategory)),
            'The matching interest category should be present in the themes lane.',
        );
    }
}
