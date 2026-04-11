<?php

namespace Tests\Feature\Feature;

use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PublicBrowsePagesTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_public_catalog_pages_render_the_mysql_backed_public_surface(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();
        $person = $this->samplePerson();
        $genre = $this->sampleGenre();
        $interestCategory = $this->sampleInterestCategory();
        $year = $this->sampleReleaseYear();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Catalog Spotlight')
            ->assertSee('Trending titles');

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Advanced Title Discovery')
            ->assertSeeHtml('data-slot="discover-filters-island"')
            ->assertSeeHtml('data-slot="discover-hero"')
            ->assertSeeHtml('data-slot="discover-advanced-filters"')
            ->assertSeeHtml('data-slot="discover-results-shell"');

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSeeHtml('data-slot="awards-archive-shell"');

        $this->get(route('public.trailers.latest'))
            ->assertOk()
            ->assertSee('Trailers')
            ->assertSee('Trailer archive');

        $this->get(route('public.titles.index'))
            ->assertOk()
            ->assertSee('Browse Titles')
            ->assertSeeHtml('data-slot="title-browser-island"');

        $this->get(route('public.genres.show', $genre))
            ->assertOk()
            ->assertSee($genre->name);

        $this->get(route('public.years.show', ['year' => $year]))
            ->assertOk()
            ->assertSee((string) $year);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Overview');

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Browse People')
            ->assertSeeHtml('data-slot="people-browser-island"')
            ->assertSee('Actors')
            ->assertSee('Catalog footprint')
            ->assertSee('Top professions');

        $this->get(route('public.interest-categories.index'))
            ->assertOk()
            ->assertSee('Interest Categories')
            ->assertSeeHtml('data-slot="interest-category-browser-island"')
            ->assertSee('Catalog clusters')
            ->assertSee('Top category lanes');

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee('Known for')
            ->assertSee('Filmography');

        $this->get(route('public.interest-categories.show', $interestCategory))
            ->assertOk()
            ->assertSee($interestCategory->name)
            ->assertSee('Category overview')
            ->assertSee('Related interests');

        $this->get(route('public.search', ['q' => $this->searchTermFor($title)]))
            ->assertOk()
            ->assertSee('Search Results')
            ->assertSeeHtml('data-slot="search-results-island"')
            ->assertSee($title->name);
    }
}
