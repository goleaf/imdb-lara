<?php

namespace Tests\Feature\Feature\Lists;

use App\Enums\ListVisibility;
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

    public function test_create_list_form_renders_a_combobox_for_visibility(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateListForm::class)
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

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

        $createList = Livewire::actingAs($user)
            ->test(CreateListForm::class)
            ->set('form.name', 'Friday Night Picks')
            ->set('form.description', 'The public sci-fi shortlist.')
            ->set('form.visibility', ListVisibility::Public->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');

        $list = UserList::query()
            ->where('user_id', $user->id)
            ->where('name', 'Friday Night Picks')
            ->firstOrFail();

        Livewire::actingAs($user)
            ->test(CustomListPicker::class, ['title' => $title])
            ->set('selectedListIds', [(string) $list->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');

        $this->assertDatabaseHas('list_items', [
            'user_list_id' => $list->id,
            'title_id' => $title->id,
        ]);

        $this->get(route('public.lists.show', [$user, $list]))
            ->assertOk()
            ->assertSee('Friday Night Picks')
            ->assertSee('Northern Signal');
    }

    public function test_title_picker_can_create_a_list_inline_and_preselect_it(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create([
            'name' => 'Fresh Signal',
        ]);

        $component = Livewire::actingAs($user)
            ->test(CustomListPicker::class, ['title' => $title])
            ->set('listQuery', 'Fresh Finds')
            ->set('createListForm.name', 'Fresh Finds')
            ->set('createListForm.description', 'A brand new list from the title picker.')
            ->set('createListForm.visibility', ListVisibility::Public->value)
            ->call('createList')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');

        $createdList = UserList::query()
            ->whereBelongsTo($user)
            ->where('name', 'Fresh Finds')
            ->firstOrFail();

        $component
            ->assertSet('selectedListIds', [(string) $createdList->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('list_items', [
            'user_list_id' => $createdList->id,
            'title_id' => $title->id,
        ]);
    }

    public function test_custom_list_picker_guest_notice_uses_alert_description(): void
    {
        $title = Title::factory()->create();

        Livewire::test(CustomListPicker::class, ['title' => $title])
            ->assertSeeHtml('data-slot="alert-description"');
    }
}
