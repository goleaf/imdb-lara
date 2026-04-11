<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\BuildPersonFilmographyQueryAction;
use App\Livewire\People\FilmographyPanel;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PersonFilmographyPanelTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_filmography_panel_renders_live_catalog_rows_for_a_remote_person(): void
    {
        $person = $this->samplePerson();
        $filmography = app(BuildPersonFilmographyQueryAction::class)->handle($person);
        $firstGroup = $filmography['groups']->first();

        if (! is_array($firstGroup) || $firstGroup['rows']->isEmpty()) {
            $this->markTestSkipped('The remote catalog did not return filmography rows for the sample person.');
        }

        $firstRow = $firstGroup['rows']->first();

        Livewire::test(FilmographyPanel::class, ['person' => $person])
            ->assertSee('Filmography')
            ->assertSee('Credit group')
            ->assertSee('Sort')
            ->assertSee($firstGroup['label'])
            ->assertSee($firstRow['title']->name)
            ->assertSee(route('public.titles.show', $firstRow['title']), false)
            ->assertDontSee('No filmography rows match the current filters.');
    }

    public function test_filmography_panel_applies_group_filter_and_rating_sort_for_remote_catalog_rows(): void
    {
        $person = $this->samplePerson();
        $baseFilmography = app(BuildPersonFilmographyQueryAction::class)->handle($person);
        $group = $baseFilmography['groups']->first(
            fn (array $group): bool => $group['rows']->count() >= 2
        );

        if (! is_array($group)) {
            $this->markTestSkipped('The remote catalog did not provide a filmography group with multiple rows for sorting coverage.');
        }

        $filteredFilmography = app(BuildPersonFilmographyQueryAction::class)->handle($person, [
            'profession' => $group['label'],
            'sort' => 'rating',
        ]);
        $filteredGroup = $filteredFilmography['groups']->first();

        if (! is_array($filteredGroup) || $filteredGroup['rows']->count() < 2) {
            $this->markTestSkipped('The filtered remote filmography did not retain enough rows for sort assertions.');
        }

        $expectedTitles = $filteredGroup['rows']
            ->take(2)
            ->pluck('title.name')
            ->values()
            ->all();

        Livewire::test(FilmographyPanel::class, ['person' => $person])
            ->set('profession', $group['label'])
            ->set('sort', 'rating')
            ->assertSee($group['label'])
            ->assertSeeInOrder($expectedTitles);
    }
}
