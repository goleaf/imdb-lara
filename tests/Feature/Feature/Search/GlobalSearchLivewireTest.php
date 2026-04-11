<?php

namespace Tests\Feature\Feature\Search;

use App\Livewire\Search\GlobalSearch;
use App\Models\NameBasicMeterRanking;
use App\Models\Person;
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
        $personSearch = $this->personSearchTermFor($person);

        Livewire::test(GlobalSearch::class)
            ->set('query', $this->searchTermFor($title))
            ->assertSee('Find titles and people fast')
            ->assertSee('Top suggestion')
            ->assertSee($title->name)
            ->assertDontSee('Lists')
            ->set('query', $personSearch)
            ->assertSee('Top suggestion')
            ->assertSee($person->name)
            ->call('submitSearch')
            ->assertRedirect(route('public.search', ['q' => $personSearch]));
    }

    public function test_global_search_requires_a_meaningful_query_before_showing_suggestions(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();

        Livewire::test(GlobalSearch::class)
            ->set('query', 's')
            ->assertDontSee('Titles')
            ->assertDontSee($title->name);
    }

    public function test_global_search_surfaces_a_ranked_person_top_suggestion_and_people_metrics(): void
    {
        Livewire::withoutLazyLoading();

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
}
