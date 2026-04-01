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
            ->assertSee('Northern Signal');

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Discovery')
            ->assertSee($title->name);

        $this->get(route('public.titles.index'))
            ->assertOk()
            ->assertSee('Browse Titles')
            ->assertSee($title->name);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee($title->credits->firstOrFail()->person->name)
            ->assertSee($title->reviews->firstOrFail()->headline);

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Browse People')
            ->assertSee('Actors')
            ->assertSee($person->name);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($person->name)
            ->assertSee('Known for')
            ->assertSee('Filmography')
            ->assertSee($person->credits->firstOrFail()->title->name);

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSee($user->name);

        $this->get(route('public.search', ['q' => 'Signal']))
            ->assertOk()
            ->assertSee('Search')
            ->assertSee('Northern Signal');
    }
}
