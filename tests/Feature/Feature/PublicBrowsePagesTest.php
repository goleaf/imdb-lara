<?php

namespace Tests\Feature\Feature;

use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicBrowsePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_pages_render_seeded_content(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        Livewire::withoutLazyLoading();

        $title = Title::query()->with(['credits.person', 'reviews.author'])->firstOrFail();
        $person = Person::query()->with('credits.title')->firstOrFail();
        $user = User::query()->whereHas('publicLists')->firstOrFail();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Hero Spotlight')
            ->assertSee('Trending Now')
            ->assertSee('Northern Signal')
            ->assertSee('Search The Global Catalog')
            ->assertSeeInOrder(['Home', 'Discovery', 'All Titles', 'Movies', 'TV Shows', 'People', 'Lists', 'Awards', 'Charts', 'Latest Trailers', 'Latest Reviews', 'Advanced Search'])
            ->assertSeeHtml('data-slot="badge-icon"');

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Advanced Title Discovery')
            ->assertSee('Radar Picks')
            ->assertSeeHtml('data-slot="discover-hero"')
            ->assertSeeHtml('data-slot="discover-advanced-filters"')
            ->assertSeeHtml('data-slot="discover-results-shell"')
            ->assertSee($title->name);

        $this->get(route('public.titles.index'))
            ->assertOk()
            ->assertSee('Browse Titles')
            ->assertSeeHtml('data-slot="browse-titles-hero"')
            ->assertSee($title->name);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-hero"')
            ->assertSee($title->name)
            ->assertSee($title->credits->firstOrFail()->person->name)
            ->assertSee($title->reviews->firstOrFail()->headline);

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Browse People')
            ->assertSeeHtml('data-slot="browse-people-hero"')
            ->assertSee('Actors')
            ->assertSee($person->name);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSeeHtml('data-slot="people-detail-hero"')
            ->assertSee($person->name)
            ->assertSee('Known for')
            ->assertSee('Filmography')
            ->assertSee($person->credits->firstOrFail()->title->name);

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSeeHtml('data-slot="awards-archive-hero"')
            ->assertSeeHtml('data-slot="awards-archive-shell"')
            ->assertSee('Celestial Screen Awards');

        $this->get(route('public.lists.index'))
            ->assertOk()
            ->assertSee('Browse Public Lists')
            ->assertSeeHtml('data-slot="public-lists-hero"')
            ->assertSeeHtml('data-slot="public-lists-grid"')
            ->assertSee('Weekend Marathon');

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSeeHtml('data-slot="badge-icon"')
            ->assertSee($user->name);

        $this->get(route('public.search', ['q' => 'Signal']))
            ->assertOk()
            ->assertSee('Search')
            ->assertSeeHtml('data-slot="search-surface"')
            ->assertSee('Northern Signal');
    }
}
