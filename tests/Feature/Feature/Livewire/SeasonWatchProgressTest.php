<?php

namespace Tests\Feature\Feature\Livewire;

use App\Livewire\Seasons\WatchProgressPanel;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeasonWatchProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_season_watch_progress_renders_descriptive_alerts_for_guest_and_status_states(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();
        $member = User::query()->where('email', 'member@example.com')->firstOrFail();

        Livewire::test(WatchProgressPanel::class, [
            'series' => $series,
            'season' => $season,
        ])->assertSeeHtml('data-slot="alert-description"');

        Livewire::actingAs($member)
            ->test(WatchProgressPanel::class, [
                'series' => $series,
                'season' => $season,
            ])
            ->call('markSeasonWatched')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');
    }

    public function test_season_watch_progress_requires_authentication_for_bulk_updates(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $season = Season::query()->where('slug', 'static-bloom-season-1')->firstOrFail();

        Livewire::test(WatchProgressPanel::class, [
            'series' => $series,
            'season' => $season,
        ])
            ->call('markSeasonWatched')
            ->assertRedirect(route('login'));
    }

    public function test_season_watch_progress_marks_every_episode_in_the_season_as_completed(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $season = Season::query()
            ->where('slug', 'static-bloom-season-1')
            ->with('episodes')
            ->firstOrFail();
        $member = User::query()->where('email', 'member@example.com')->firstOrFail();
        $watchlist = $member->watchlist()->firstOrFail();

        Livewire::actingAs($member)
            ->test(WatchProgressPanel::class, [
                'series' => $series,
                'season' => $season,
            ])
            ->call('markSeasonWatched')
            ->assertHasNoErrors()
            ->assertSet('remainingEpisodes', 0)
            ->assertSet('percentage', 100);

        foreach ($season->episodes as $episodeMeta) {
            $this->assertDatabaseHas('list_items', [
                'user_list_id' => $watchlist->id,
                'title_id' => $episodeMeta->title_id,
                'watch_state' => 'completed',
            ]);
        }
    }
}
