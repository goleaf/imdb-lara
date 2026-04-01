<?php

namespace Tests\Feature\Feature\Livewire;

use App\Enums\WatchState;
use App\Livewire\Titles\RatingPanel;
use App\Models\Rating;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RatingPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_title_rating_and_mark_it_watched(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(RatingPanel::class, ['title' => $title])
            ->set('form.score', 8)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('score', 8)
            ->assertSee('Saved as 8/10.');

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'score' => 8,
        ]);

        $this->assertDatabaseHas('list_items', [
            'title_id' => $title->id,
            'watch_state' => WatchState::Completed->value,
        ]);

        $this->assertTitleStatisticSnapshot($title, 1, '8.00', ['8' => 1]);
    }

    public function test_authenticated_user_can_update_a_rating_without_creating_duplicates(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Rating::factory()->for($user)->for($title)->create([
            'score' => 6,
        ]);

        Livewire::actingAs($user)
            ->test(RatingPanel::class, ['title' => $title])
            ->assertSee('Saved as 6/10')
            ->set('form.score', 9)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('score', 9)
            ->assertSee('Saved as 9/10.');

        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'score' => 9,
        ]);

        $this->assertTitleStatisticSnapshot($title, 1, '9.00', ['9' => 1]);
    }

    public function test_authenticated_user_can_remove_an_existing_rating(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Rating::factory()->for($user)->for($title)->create([
            'score' => 7,
        ]);

        Livewire::actingAs($user)
            ->test(RatingPanel::class, ['title' => $title])
            ->call('remove')
            ->assertHasNoErrors()
            ->assertSet('score', null)
            ->assertSee('Your rating was removed.');

        $this->assertDatabaseMissing('ratings', [
            'user_id' => $user->id,
            'title_id' => $title->id,
        ]);

        $this->assertTitleStatisticSnapshot($title, 0, '0.00', []);
    }

    public function test_guest_users_are_redirected_when_attempting_to_mutate_ratings(): void
    {
        $title = Title::factory()->create();

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->set('form.score', 9)
            ->call('save')
            ->assertRedirect(route('login'));

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->call('remove')
            ->assertRedirect(route('login'));
    }

    private function assertTitleStatisticSnapshot(Title $title, int $ratingCount, string $averageRating, array $ratingDistribution): void
    {
        $statistic = TitleStatistic::query()
            ->select(['id', 'title_id', 'rating_count', 'average_rating', 'rating_distribution'])
            ->whereBelongsTo($title)
            ->first();

        $this->assertNotNull($statistic);
        $this->assertSame($ratingCount, $statistic->rating_count);
        $this->assertSame($averageRating, $statistic->average_rating);
        $this->assertSame(
            TitleStatistic::normalizeRatingDistribution($ratingDistribution),
            $statistic->normalizedRatingDistribution(),
        );
    }
}
