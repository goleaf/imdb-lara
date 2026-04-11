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
        Livewire::withoutLazyLoading();

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
        Livewire::withoutLazyLoading();

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
            ->call('sortItems', $titleBItemId, 0)
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

    public function test_sortable_reorder_respects_the_current_paginated_page_offset(): void
    {
        Livewire::withoutLazyLoading();

        $user = User::factory()->create();
        $list = UserList::factory()->for($user)->create([
            'name' => 'Long Weekend Queue',
            'slug' => 'long-weekend-queue',
        ]);

        $titleIds = collect(range(1, 14))->map(function (int $position) use ($list): int {
            $title = Title::factory()->create([
                'name' => sprintf('Queue Entry %02d', $position),
            ]);

            ListItem::factory()
                ->for($list, 'userList')
                ->for($title, 'title')
                ->create([
                    'position' => $position,
                ]);

            return $title->id;
        });

        $lastPageItemId = ListItem::query()
            ->where('user_list_id', $list->id)
            ->where('title_id', $titleIds->last())
            ->value('id');

        Livewire::withQueryParams(['items' => 2])
            ->actingAs($user)
            ->test(ManageList::class, ['list' => $list])
            ->call('sortItems', $lastPageItemId, 0)
            ->assertSet('statusMessage', 'List order updated.');

        $this->assertSame([
            $titleIds[0],
            $titleIds[1],
            $titleIds[2],
            $titleIds[3],
            $titleIds[4],
            $titleIds[5],
            $titleIds[6],
            $titleIds[7],
            $titleIds[8],
            $titleIds[9],
            $titleIds[10],
            $titleIds[11],
            $titleIds[13],
            $titleIds[12],
        ], ListItem::query()
            ->where('user_list_id', $list->id)
            ->orderBy('position')
            ->pluck('title_id')
            ->all());
    }

    public function test_non_owner_cannot_mount_the_manage_list_component(): void
    {
        Livewire::withoutLazyLoading();

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $list = UserList::factory()->for($owner)->create([
            'name' => 'Members Only Mix',
            'slug' => 'members-only-mix',
        ]);

        Livewire::actingAs($intruder)
            ->test(ManageList::class, ['list' => $list])
            ->assertForbidden();
    }

    public function test_owner_can_delete_a_custom_list(): void
    {
        Livewire::withoutLazyLoading();

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

    public function test_public_lists_index_only_shows_public_custom_lists_and_supports_search(): void
    {
        $alpha = User::factory()->create([
            'name' => 'Alpha Curator',
            'username' => 'alpha-curator',
        ]);
        $beta = User::factory()->create([
            'name' => 'Beta Curator',
            'username' => 'beta-curator',
        ]);

        $alphaList = UserList::factory()->public()->for($alpha)->create([
            'name' => 'Friday Night Futures',
            'slug' => 'friday-night-futures',
        ]);
        $betaList = UserList::factory()->public()->for($beta)->create([
            'name' => 'Deep Space Essentials',
            'slug' => 'deep-space-essentials',
        ]);
        $privateList = UserList::factory()->for($alpha)->create([
            'name' => 'Secret Sequels',
            'slug' => 'secret-sequels',
            'visibility' => ListVisibility::Private,
        ]);
        $unlistedList = UserList::factory()->for($alpha)->create([
            'name' => 'Hidden Vault',
            'slug' => 'hidden-vault',
            'visibility' => ListVisibility::Unlisted,
        ]);
        $publicWatchlist = UserList::factory()->watchlist()->for($alpha)->create([
            'visibility' => ListVisibility::Public,
        ]);

        foreach ([$alphaList, $betaList, $privateList, $unlistedList, $publicWatchlist] as $index => $list) {
            $title = Title::factory()->create([
                'name' => 'List Title '.($index + 1),
            ]);

            ListItem::factory()
                ->for($list, 'userList')
                ->for($title, 'title')
                ->create([
                    'position' => 1,
                ]);
        }

        $this->get(route('public.lists.index'))
            ->assertOk()
            ->assertSee('Friday Night Futures')
            ->assertSee('Deep Space Essentials')
            ->assertDontSee('Secret Sequels')
            ->assertDontSee('Hidden Vault')
            ->assertDontSee('Watchlist');

        $this->get(route('public.lists.index', ['q' => 'beta-curator']))
            ->assertOk()
            ->assertSee('Deep Space Essentials')
            ->assertDontSee('Friday Night Futures');
    }

    public function test_public_lists_index_can_sort_by_list_size(): void
    {
        $user = User::factory()->create([
            'username' => 'ranked-curator',
        ]);

        $smallerList = UserList::factory()->public()->for($user)->create([
            'name' => 'Single Feature List',
        ]);
        $largerList = UserList::factory()->public()->for($user)->create([
            'name' => 'Triple Feature List',
        ]);

        foreach (range(1, 1) as $position) {
            $title = Title::factory()->create([
                'name' => 'Small Entry '.$position,
            ]);

            ListItem::factory()
                ->for($smallerList, 'userList')
                ->for($title, 'title')
                ->create([
                    'position' => $position,
                ]);
        }

        foreach (range(1, 3) as $position) {
            $title = Title::factory()->create([
                'name' => 'Large Entry '.$position,
            ]);

            ListItem::factory()
                ->for($largerList, 'userList')
                ->for($title, 'title')
                ->create([
                    'position' => $position,
                ]);
        }

        $this->get(route('public.lists.index', ['sort' => 'most_titles']))
            ->assertOk()
            ->assertSeeInOrder([
                'Triple Feature List',
                'Single Feature List',
            ]);
    }

    public function test_public_lists_index_preview_and_counts_ignore_unpublished_titles(): void
    {
        $user = User::factory()->create([
            'username' => 'mixed-curator',
        ]);

        $list = UserList::factory()->public()->for($user)->create([
            'name' => 'Mixed Visibility Queue',
            'slug' => 'mixed-visibility-queue',
        ]);

        foreach (range(1, 3) as $position) {
            $hiddenTitle = Title::factory()->create([
                'name' => 'Hidden Queue Entry '.$position,
                'is_published' => false,
            ]);

            ListItem::factory()
                ->for($list, 'userList')
                ->for($hiddenTitle, 'title')
                ->create([
                    'position' => $position,
                ]);
        }

        $visibleTitle = Title::factory()->create([
            'name' => 'Visible Queue Entry',
            'is_published' => true,
        ]);

        ListItem::factory()
            ->for($list, 'userList')
            ->for($visibleTitle, 'title')
            ->create([
                'position' => 4,
            ]);

        $this->get(route('public.lists.index'))
            ->assertOk()
            ->assertSee('Mixed Visibility Queue')
            ->assertSee('Visible Queue Entry')
            ->assertDontSee('4 titles')
            ->assertSee('1 titles');
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

    public function test_public_list_page_paginates_and_counts_only_published_titles(): void
    {
        $user = User::factory()->create([
            'username' => 'published-only-curator',
        ]);

        $list = UserList::factory()->public()->for($user)->create([
            'name' => 'Published Only Queue',
            'slug' => 'published-only-queue',
        ]);

        foreach (range(1, 18) as $position) {
            $hiddenTitle = Title::factory()->create([
                'name' => sprintf('Hidden Queue Entry %02d', $position),
                'is_published' => false,
            ]);

            ListItem::factory()
                ->for($list, 'userList')
                ->for($hiddenTitle, 'title')
                ->create([
                    'position' => $position,
                ]);
        }

        $visibleTitle = Title::factory()->create([
            'name' => 'Published Queue Entry',
            'is_published' => true,
        ]);

        ListItem::factory()
            ->for($list, 'userList')
            ->for($visibleTitle, 'title')
            ->create([
                'position' => 19,
            ]);

        $this->get(route('public.lists.show', [$user, $list]))
            ->assertOk()
            ->assertSee('Published Queue Entry')
            ->assertDontSee('This list does not have any published titles yet.')
            ->assertDontSee('19 titles')
            ->assertSee('1 titles');
    }
}
