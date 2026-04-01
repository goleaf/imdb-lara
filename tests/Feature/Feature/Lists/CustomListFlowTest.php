<?php

namespace Tests\Feature\Feature\Lists;

use App\ListVisibility;
use App\Livewire\Lists\CreateListForm;
use App\Livewire\Titles\CustomListPicker;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomListFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_lists_require_authentication_and_render_existing_lists(): void
    {
        $this->get(route('account.lists.index'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create();
        UserList::factory()->for($user)->public()->create([
            'name' => 'Weekend Marathon',
            'slug' => 'weekend-marathon',
        ]);

        $this->actingAs($user)
            ->get(route('account.lists.index'))
            ->assertOk()
            ->assertSee('Your Lists')
            ->assertSee('Weekend Marathon');
    }

    public function test_user_can_create_a_public_list_add_a_title_and_view_its_public_page(): void
    {
        $user = User::factory()->create([
            'username' => 'dana',
        ]);
        $title = Title::factory()->create([
            'name' => 'Northern Signal',
        ]);

        Livewire::actingAs($user)
            ->test(CreateListForm::class)
            ->set('name', 'Friday Night Picks')
            ->set('description', 'The public sci-fi shortlist.')
            ->set('visibility', ListVisibility::Public->value)
            ->call('save')
            ->assertHasNoErrors();

        $list = UserList::query()
            ->where('user_id', $user->id)
            ->where('name', 'Friday Night Picks')
            ->firstOrFail();

        Livewire::actingAs($user)
            ->test(CustomListPicker::class, ['title' => $title])
            ->set("selectedLists.{$list->id}", true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('list_items', [
            'user_list_id' => $list->id,
            'title_id' => $title->id,
        ]);

        $this->get(route('public.lists.show', [$user, $list]))
            ->assertOk()
            ->assertSee('Friday Night Picks')
            ->assertSee('Northern Signal');
    }
}
