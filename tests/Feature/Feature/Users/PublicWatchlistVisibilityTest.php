<?php

namespace Tests\Feature\Feature\Users;

use App\Enums\ListVisibility;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWatchlistVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_watchlist_is_visible_from_profile_and_public_list_routes(): void
    {
        $user = User::factory()->create([
            'name' => 'Curator One',
            'username' => 'curator-one',
        ]);

        $watchlist = UserList::factory()->watchlist()->for($user)->create([
            'visibility' => ListVisibility::Public,
        ]);

        $title = Title::factory()->movie()->create([
            'name' => 'Silent Meridian',
            'slug' => 'silent-meridian',
        ]);

        ListItem::factory()->for($watchlist, 'userList')->for($title, 'title')->create();

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Public watchlist')
            ->assertSee($title->name);

        $this->get(route('public.lists.show', [$user, $watchlist]))
            ->assertOk()
            ->assertSee('Watchlist')
            ->assertSee($title->name);
    }

    public function test_private_watchlist_does_not_expose_a_public_profile_or_list_route(): void
    {
        $user = User::factory()->create([
            'username' => 'private-curator',
        ]);

        $watchlist = UserList::factory()->watchlist()->for($user)->create([
            'visibility' => ListVisibility::Private,
        ]);

        ListItem::factory()->for($watchlist, 'userList')->create();

        $this->get(route('public.users.show', $user))->assertNotFound();
        $this->get(route('public.lists.show', [$user, $watchlist]))->assertNotFound();
    }
}
