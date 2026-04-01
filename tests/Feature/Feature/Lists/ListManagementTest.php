<?php

namespace Tests\Feature\Feature\Lists;

use App\Enums\ListVisibility;
use App\Livewire\Lists\ManageList;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_list_page_requires_authentication_and_hides_other_users_private_lists(): void
    {
        $owner = User::factory()->create();
        $list = UserList::factory()->for($owner)->create([
            'name' => 'Secret Sequels',
            'slug' => 'secret-sequels',
        ]);

        $this->get(route('account.lists.show', $list))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('account.lists.show', $list))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('account.lists.show', $list))
            ->assertOk()
            ->assertSee('Secret Sequels');
    }

    public function test_owner_can_update_list_metadata_add_titles_remove_titles_and_reorder_items(): void
    {
        $user = User::factory()->create();
        $list = UserList::factory()->for($user)->create([
            'name' => 'Late Night Watches',
            'slug' => 'late-night-watches',
        ]);

        $titleA = Title::factory()->create(['name' => 'Alpha Drift']);
        $titleB = Title::factory()->create(['name' => 'Beacon Run']);
        $titleC = Title::factory()->create(['name' => 'Cinder Line']);

        ListItem::factory()->for($list, 'userList')->for($titleA, 'title')->create(['position' => 1]);
        ListItem::factory()->for($list, 'userList')->for($titleB, 'title')->create(['position' => 2]);

        $component = Livewire::actingAs($user)
            ->test(ManageList::class, ['list' => $list])
            ->set('name', 'Road Trip Vault')
            ->set('description', 'A tighter edit of road-trip titles.')
            ->set('visibility', ListVisibility::Unlisted->value)
            ->call('saveList')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"')
            ->call('addTitle', $titleC->id)
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');

        $titleBItemId = ListItem::query()
            ->where('user_list_id', $list->id)
            ->where('title_id', $titleB->id)
            ->value('id');

        $component
            ->call('moveItemUp', $titleBItemId)
            ->call('removeTitle', $titleA->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_lists', [
            'id' => $list->id,
            'name' => 'Road Trip Vault',
            'visibility' => ListVisibility::Unlisted->value,
        ]);

        $this->assertDatabaseMissing('list_items', [
            'user_list_id' => $list->id,
            'title_id' => $titleA->id,
        ]);

        $this->assertSame([
            $titleB->id,
            $titleC->id,
        ], ListItem::query()
            ->where('user_list_id', $list->id)
            ->orderBy('position')
            ->pluck('title_id')
            ->all());
    }

    public function test_owner_can_delete_a_custom_list(): void
    {
        $user = User::factory()->create();
        $list = UserList::factory()->for($user)->create([
            'name' => 'One-Off Experiments',
        ]);

        Livewire::actingAs($user)
            ->test(ManageList::class, ['list' => $list])
            ->call('deleteList')
            ->assertRedirect(route('account.lists.index'));

        $this->assertSoftDeleted('user_lists', [
            'id' => $list->id,
        ]);
    }

    public function test_unlisted_lists_are_directly_viewable_but_not_shown_on_public_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'curator-alpha',
        ]);

        $publicList = UserList::factory()->public()->for($user)->create([
            'name' => 'Public Picks',
            'slug' => 'public-picks',
        ]);

        $unlistedList = UserList::factory()->for($user)->create([
            'name' => 'Hidden Vault',
            'slug' => 'hidden-vault',
            'visibility' => ListVisibility::Unlisted,
        ]);

        $publicTitle = Title::factory()->create(['name' => 'Open Signal']);
        $unlistedTitle = Title::factory()->create(['name' => 'Quiet Harbor']);

        ListItem::factory()->for($publicList, 'userList')->for($publicTitle, 'title')->create();
        ListItem::factory()->for($unlistedList, 'userList')->for($unlistedTitle, 'title')->create();

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Public Picks')
            ->assertDontSee('Hidden Vault');

        $this->get(route('public.lists.show', [$user, $unlistedList]))
            ->assertOk()
            ->assertSee('Hidden Vault')
            ->assertSee('Quiet Harbor');
    }

    public function test_private_lists_are_not_publicly_accessible(): void
    {
        $user = User::factory()->create([
            'username' => 'curator-beta',
        ]);

        $privateList = UserList::factory()->for($user)->create([
            'name' => 'Private Vault',
            'slug' => 'private-vault',
            'visibility' => ListVisibility::Private,
        ]);

        ListItem::factory()->for($privateList, 'userList')->create();

        $this->get(route('public.lists.show', [$user, $privateList]))
            ->assertNotFound();
    }

    public function test_public_list_page_paginates_large_lists(): void
    {
        $user = User::factory()->create([
            'username' => 'curator-gamma',
        ]);

        $list = UserList::factory()->public()->for($user)->create([
            'name' => 'Long Queue',
            'slug' => 'long-queue',
        ]);

        foreach (range(1, 20) as $position) {
            $title = Title::factory()->create([
                'name' => sprintf('Queue Entry %02d', $position),
            ]);

            ListItem::factory()
                ->for($list, 'userList')
                ->for($title, 'title')
                ->create([
                    'position' => $position,
                ]);
        }

        $this->get(route('public.lists.show', [$user, $list]))
            ->assertOk()
            ->assertSee('Queue Entry 01')
            ->assertSee('Queue Entry 18')
            ->assertDontSee('Queue Entry 19');

        $this->get(route('public.lists.show', [$user, $list, 'titles' => 2]))
            ->assertOk()
            ->assertSee('Queue Entry 19')
            ->assertSee('Queue Entry 20')
            ->assertDontSee('Queue Entry 01');
    }
}
