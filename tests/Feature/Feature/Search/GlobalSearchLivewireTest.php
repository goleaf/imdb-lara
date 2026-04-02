<?php

namespace Tests\Feature\Feature\Search;

use App\Livewire\Search\GlobalSearch;
use App\Models\ListItem;
use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_shows_grouped_live_suggestions_and_redirects_to_full_results(): void
    {
        Title::factory()->create([
            'name' => 'Northern Signal',
            'search_keywords' => 'signal, sci-fi',
            'is_published' => true,
        ]);
        Person::factory()->create([
            'name' => 'Ava Signal',
            'alternate_names' => 'Signal Maker',
            'search_keywords' => 'signal',
            'is_published' => true,
        ]);
        $curator = User::factory()->create([
            'name' => 'Signal Curator',
            'username' => 'signal-curator',
        ]);
        $publicList = UserList::factory()->public()->for($curator)->create([
            'name' => 'Signal Essentials',
            'slug' => 'signal-essentials',
        ]);
        ListItem::factory()->for($publicList, 'userList')->create();
        $privateList = UserList::factory()->for($curator)->create([
            'name' => 'Signal Draft Vault',
            'slug' => 'signal-draft-vault',
        ]);
        ListItem::factory()->for($privateList, 'userList')->create();

        Livewire::test(GlobalSearch::class)
            ->set('query', 'Signal')
            ->assertSee('Titles')
            ->assertSee('People')
            ->assertSee('Lists')
            ->assertSee('Northern Signal')
            ->assertSee('Ava Signal')
            ->assertSee('Signal Essentials')
            ->assertDontSee('Signal Draft Vault')
            ->call('submitSearch')
            ->assertRedirect(route('public.search', ['q' => 'Signal']));
    }

    public function test_global_search_requires_a_meaningful_query_before_showing_suggestions(): void
    {
        Title::factory()->create([
            'name' => 'Northern Signal',
            'search_keywords' => 'signal',
            'is_published' => true,
        ]);

        Livewire::test(GlobalSearch::class)
            ->set('query', 's')
            ->assertDontSee('Titles')
            ->assertDontSee('Northern Signal');
    }
}
