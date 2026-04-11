<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Livewire\Catalog\PeopleBrowser;
use App\Models\NameBasicMeterRanking;
use App\Models\Person;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PeopleBrowserTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_people_browser_uses_combobox_filters(): void
    {
        Livewire::withoutLazyLoading();

        Livewire::test(PeopleBrowser::class)
            ->assertSeeHtml('data-slot="people-browser-island"')
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

    public function test_people_browser_filters_by_search_and_profession(): void
    {
        Livewire::withoutLazyLoading();

        $person = Person::query()
            ->select($this->remotePersonColumns())
            ->published()
            ->whereHas('professionTerms')
            ->with([
                'professionTerms:id,name',
            ])
            ->orderBy('name_basics.id')
            ->first();

        $profession = $person instanceof Person
            ? $person->professionTerms->first()?->name
            : null;

        if (! $person instanceof Person || ! is_string($profession) || $profession === '') {
            $this->markTestSkipped('The remote catalog does not currently expose a person with a loaded profession term.');
        }

        $matchingPeople = app(BuildPublicPeopleIndexQueryAction::class)
            ->handle([
                'profession' => $profession,
                'sort' => 'popular',
            ])
            ->limit(18)
            ->get();

        $person = $matchingPeople->first();

        if (! $person instanceof Person) {
            $this->markTestSkipped('The remote catalog does not currently expose a filtered person result for the selected profession.');
        }

        $this->get(route('public.people.index', ['q' => $this->personSearchTermFor($person)]))
            ->assertOk()
            ->assertSee($person->name);

        $this->get(route('public.people.index', ['profession' => $profession]))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee(route('public.people.show', $person), false);
    }

    public function test_popular_sort_prioritizes_meter_ranking_before_credit_volume(): void
    {
        $expectedTopRankedPerson = Person::query()
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
            ->withCount(['credits', 'awardNominations'])
            ->orderBy('popularity_rank')
            ->orderByDesc('award_nominations_count')
            ->orderByDesc('credits_count')
            ->orderBy('displayName')
            ->first();

        if (! $expectedTopRankedPerson instanceof Person || ! is_int($expectedTopRankedPerson->popularity_rank)) {
            $this->markTestSkipped('The remote catalog does not currently expose a ranked person.');
        }

        $topPopularPerson = app(BuildPublicPeopleIndexQueryAction::class)
            ->handle(['sort' => 'popular'])
            ->first();

        $this->assertInstanceOf(Person::class, $topPopularPerson);
        $this->assertSame($expectedTopRankedPerson->id, $topPopularPerson->id);
    }

    public function test_people_browser_cards_surface_award_metadata(): void
    {
        Livewire::withoutLazyLoading();

        $person = app(BuildPublicPeopleIndexQueryAction::class)
            ->handle(['sort' => 'awards'])
            ->whereHas('awardNominations')
            ->first();

        if (! $person instanceof Person) {
            $this->markTestSkipped('The remote catalog does not currently expose a person with award nominations.');
        }

        $this->get(route('public.people.index', [
            'q' => $this->personSearchTermFor($person),
            'sort' => 'awards',
        ]))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSeeHtml('data-slot="person-card-awards"')
            ->assertSee(number_format((int) ($person->award_nominations_count ?? 0)).' award');
    }
}
