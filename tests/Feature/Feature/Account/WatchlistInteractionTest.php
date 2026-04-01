<?php

namespace Tests\Feature\Feature\Account;

use App\Livewire\Titles\RatingPanel;
use App\Livewire\Titles\ReviewComposer;
use App\Livewire\Titles\WatchlistToggle;
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
            ->assertSee($savedTitle->name);
    }

    public function test_authenticated_user_can_toggle_watchlist_rate_and_submit_review(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        $this->actingAs($user);

        Livewire::test(WatchlistToggle::class, ['title' => $title])
            ->call('toggle')
            ->assertSet('inWatchlist', true);

        $this->assertDatabaseHas('list_items', [
            'title_id' => $title->id,
        ]);

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->set('score', 8)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'score' => 8,
        ]);

        Livewire::test(ReviewComposer::class, ['title' => $title])
            ->set('headline', 'Sharp, grounded sci-fi')
            ->set('body', 'The character work keeps the whole thing moving.')
            ->set('containsSpoilers', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'headline' => 'Sharp, grounded sci-fi',
        ]);
    }
}
