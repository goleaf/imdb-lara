<?php

namespace Tests\Feature\Feature\Search;

use App\Enums\ListVisibility;
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
        $title = Title::factory()->create([
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
        $owner = User::factory()->create([
            'username' => 'signal-owner',
        ]);
        $list = UserList::factory()->public()->for($owner)->create([
            'name' => 'Signal Essentials',
            'visibility' => ListVisibility::Public,
        ]);
        ListItem::factory()->for($list, 'userList')->for($title, 'title')->create();

        Livewire::test(GlobalSearch::class)
            ->set('query', 'Signal')
            ->assertSee('Titles')
            ->assertSee('People')
            ->assertSee('Lists')
            ->assertSee('Northern Signal')
            ->assertSee('Ava Signal')
            ->assertSee('Signal Essentials')
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
