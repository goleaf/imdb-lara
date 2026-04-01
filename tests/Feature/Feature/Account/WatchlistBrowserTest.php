<?php

namespace Tests\Feature\Feature\Account;

use App\Actions\Lists\BuildAccountWatchlistQueryAction;
use App\Enums\ListVisibility;
use App\Enums\TitleType;
use App\Enums\WatchState;
use App\Livewire\Account\WatchlistBrowser;
use App\Models\Genre;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WatchlistBrowserTest extends TestCase
{
    use RefreshDatabase;

    public function test_watchlist_browser_filters_and_sorts_private_tracking_items(): void
    {
        $user = User::factory()->create();
        $watchlist = UserList::factory()->watchlist()->for($user)->create();

        $drama = Genre::factory()->create(['name' => 'Drama', 'slug' => 'drama']);
        $thriller = Genre::factory()->create(['name' => 'Thriller', 'slug' => 'thriller']);

        $afterlight = Title::factory()->movie()->create([
            'name' => 'Afterlight',
            'slug' => 'afterlight',
            'release_year' => 2024,
        ]);
        $afterlight->genres()->attach($drama);
        TitleStatistic::factory()->for($afterlight, 'title')->create([
            'average_rating' => 9.2,
            'rating_count' => 420,
        ]);
        ListItem::factory()->for($watchlist, 'userList')->for($afterlight, 'title')->create([
            'position' => 1,
            'watch_state' => WatchState::Planned,
        ]);

        $beaconPoint = Title::factory()->series()->create([
            'name' => 'Beacon Point',
            'slug' => 'beacon-point',
            'release_year' => 2023,
        ]);
        $beaconPoint->genres()->attach($drama);
        TitleStatistic::factory()->for($beaconPoint, 'title')->create([
            'average_rating' => 8.7,
            'rating_count' => 360,
        ]);
        ListItem::factory()->completed()->for($watchlist, 'userList')->for($beaconPoint, 'title')->create([
            'position' => 2,
        ]);

        $coldHarbor = Title::factory()->movie()->create([
            'name' => 'Cold Harbor',
            'slug' => 'cold-harbor',
            'release_year' => 2022,
        ]);
        $coldHarbor->genres()->attach($thriller);
        TitleStatistic::factory()->for($coldHarbor, 'title')->create([
            'average_rating' => 7.4,
            'rating_count' => 220,
        ]);
        ListItem::factory()->for($watchlist, 'userList')->for($coldHarbor, 'title')->create([
            'position' => 3,
            'watch_state' => WatchState::Watching,
        ]);

        Livewire::actingAs($user)
            ->test(WatchlistBrowser::class)
            ->set('type', TitleType::Movie->value)
            ->set('genre', $drama->slug)
            ->set('year', '2024')
            ->set('state', 'unwatched')
            ->assertSee($afterlight->name)
            ->assertDontSee($beaconPoint->name)
            ->assertDontSee($coldHarbor->name);

        $sortedItems = app(BuildAccountWatchlistQueryAction::class)
            ->handle($watchlist, ['sort' => 'title'])
            ->get();

        $this->assertSame([
            $afterlight->id,
            $beaconPoint->id,
            $coldHarbor->id,
        ], $sortedItems->pluck('title_id')->all());
    }

    public function test_member_can_toggle_watched_state_and_watchlist_visibility(): void
    {
        $user = User::factory()->create();
        $watchlist = UserList::factory()->watchlist()->for($user)->create();
        $title = Title::factory()->movie()->create();

        ListItem::factory()->for($watchlist, 'userList')->for($title, 'title')->create([
            'watch_state' => WatchState::Planned,
        ]);

        Livewire::actingAs($user)
            ->test(WatchlistBrowser::class)
            ->call('toggleWatched', $title->id)
            ->assertSet('statusMessage', 'Tracking updated.')
            ->set('visibility', ListVisibility::Public->value)
            ->call('saveVisibility')
            ->assertSet('visibilityMessage', 'Watchlist visibility updated.');

        $this->assertDatabaseHas('list_items', [
            'user_list_id' => $watchlist->id,
            'title_id' => $title->id,
            'watch_state' => WatchState::Completed->value,
        ]);

        $this->assertDatabaseHas('user_lists', [
            'id' => $watchlist->id,
            'visibility' => ListVisibility::Public->value,
        ]);

        Livewire::actingAs($user)
            ->test(WatchlistBrowser::class)
            ->call('toggleWatched', $title->id);

        $this->assertDatabaseHas('list_items', [
            'user_list_id' => $watchlist->id,
            'title_id' => $title->id,
            'watch_state' => WatchState::Planned->value,
            'watched_at' => null,
        ]);
    }
}
