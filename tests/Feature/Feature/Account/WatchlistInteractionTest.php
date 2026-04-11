<?php

namespace Tests\Feature\Feature\Account;

use App\Enums\WatchState;
use App\Livewire\Account\WatchlistBrowser;
use App\Livewire\Titles\RatingPanel;
use App\Livewire\Titles\ReviewComposer;
use App\Livewire\Titles\WatchlistToggle;
use App\Livewire\Titles\WatchStatePanel;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WatchlistInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_watchlist_page_requires_authentication_and_renders_saved_titles(): void
    {
        $this->get(route('account.watchlist'))
            ->assertRedirect(route('login'));

        $this->seed(DemoCatalogSeeder::class);

        $member = User::query()->where('email', 'member@example.com')->firstOrFail();
        $savedTitle = $member->watchlist->items()->with('title')->firstOrFail()->title;

        $this->actingAs($member)
            ->get(route('account.watchlist'))
            ->assertOk()
            ->assertSee('Your Watchlist')
            ->assertSee('Loading your private tracking queue and filters.');

        Livewire::actingAs($member)
            ->test(WatchlistBrowser::class)
            ->assertSee($savedTitle->name);
    }

    public function test_authenticated_user_can_toggle_watchlist_rate_and_submit_review(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        $this->actingAs($user);

        Livewire::test(WatchlistToggle::class, ['title' => $title])
            ->call('toggle')
            ->assertDispatched('title-personal-tracking-updated')
            ->assertSet('inWatchlist', true);

        $this->assertDatabaseHas('list_items', [
            'title_id' => $title->id,
        ]);

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->set('form.score', 8)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'score' => 8,
        ]);

        Livewire::test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Sharp, grounded sci-fi')
            ->set('form.body', 'The character work keeps the whole thing moving.')
            ->set('form.containsSpoilers', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'headline' => 'Sharp, grounded sci-fi',
        ]);
    }

    public function test_authenticated_user_can_toggle_watched_state_from_the_title_panel(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(WatchStatePanel::class, ['title' => $title])
            ->call('toggleWatched')
            ->assertDispatched('title-personal-tracking-updated')
            ->assertSet('watchState', WatchState::Completed)
            ->assertSee('Marked as watched.');

        $this->assertDatabaseHas('list_items', [
            'title_id' => $title->id,
            'watch_state' => WatchState::Completed->value,
        ]);

        Livewire::actingAs($user)
            ->test(WatchStatePanel::class, ['title' => $title])
            ->call('toggleWatched')
            ->assertDispatched('title-personal-tracking-updated')
            ->assertSet('watchState', WatchState::Planned)
            ->assertSee('Marked as unwatched.');

        $this->assertDatabaseHas('list_items', [
            'title_id' => $title->id,
            'watch_state' => WatchState::Planned->value,
            'watched_at' => null,
        ]);
    }

    public function test_authenticated_user_can_toggle_episode_watched_state_without_marking_the_series(): void
    {
        $user = User::factory()->create();
        $series = Title::factory()->series()->create();
        $season = Season::factory()->for($series, 'series')->create([
            'season_number' => 1,
        ]);
        $episode = Title::factory()->episode()->create();

        Episode::factory()
            ->for($episode, 'title')
            ->for($series, 'series')
            ->for($season, 'season')
            ->create([
                'season_number' => 1,
                'episode_number' => 1,
            ]);

        Livewire::actingAs($user)
            ->test(WatchStatePanel::class, ['title' => $episode])
            ->call('toggleWatched')
            ->assertHasNoErrors()
            ->assertSet('watchState', WatchState::Completed)
            ->assertSee('Marked as watched.');

        $this->assertDatabaseHas('list_items', [
            'title_id' => $episode->id,
            'watch_state' => WatchState::Completed->value,
        ]);

        $this->assertDatabaseMissing('list_items', [
            'title_id' => $series->id,
        ]);
    }
}
