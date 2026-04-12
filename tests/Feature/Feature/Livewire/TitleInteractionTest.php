<?php

namespace Tests\Feature\Feature\Livewire;

use App\Livewire\Titles\RatingPanel;
use App\Livewire\Titles\ReviewComposer;
use App\Livewire\Titles\WatchlistToggle;
use App\Livewire\Titles\WatchStatePanel;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TitleInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_title_panels_render_descriptive_alerts(): void
    {
        $title = Title::factory()->create();

        Livewire::test(WatchlistToggle::class, ['title' => $title])
            ->assertSeeHtml('data-slot="alert-description"');

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->assertSeeHtml('data-slot="alert-description"');

        Livewire::test(ReviewComposer::class, ['title' => $title])
            ->assertSeeHtml('data-slot="alert-description"');

        Livewire::test(WatchStatePanel::class, ['title' => $title])
            ->assertSeeHtml('data-slot="alert-description"');
    }

    public function test_authenticated_title_panels_render_alert_notices_for_current_state(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(WatchlistToggle::class, ['title' => $title])
            ->assertSeeHtml('data-slot="alert-description"')
            ->call('toggle')
            ->assertSeeHtml('data-slot="alert-description"');

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Strong')
            ->set('form.body', 'A compelling review body long enough to validate.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');

        Livewire::actingAs($user)
            ->test(WatchStatePanel::class, ['title' => $title])
            ->call('markWatched')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-slot="alert-description"');
    }

    public function test_title_components_require_authentication_for_mutations(): void
    {
        $title = Title::factory()->create();

        Livewire::test(WatchlistToggle::class, ['title' => $title])
            ->call('toggle')
            ->assertRedirect(route('login'));

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->set('form.score', 9)
            ->call('save')
            ->assertRedirect(route('login'));

        Livewire::test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Strong')
            ->set('form.body', 'A compelling review body long enough to validate.')
            ->call('save')
            ->assertRedirect(route('login'));

        Livewire::test(WatchStatePanel::class, ['title' => $title])
            ->call('markWatched')
            ->assertRedirect(route('login'));
    }

    public function test_suspended_users_cannot_mutate_title_tracking_or_review_components(): void
    {
        $user = User::factory()->suspended()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(WatchlistToggle::class, ['title' => $title])
            ->call('toggle')
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(RatingPanel::class, ['title' => $title])
            ->set('form.score', 9)
            ->call('save')
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Strong')
            ->set('form.body', 'A compelling review body long enough to validate.')
            ->call('save')
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(WatchStatePanel::class, ['title' => $title])
            ->call('markWatched')
            ->assertForbidden();
    }

    public function test_title_components_persist_authenticated_user_interactions(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(WatchlistToggle::class, ['title' => $title])
            ->call('toggle')
            ->assertSet('inWatchlist', true);

        Livewire::actingAs($user)
            ->test(RatingPanel::class, ['title' => $title])
            ->set('form.score', 9)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Strong')
            ->set('form.body', 'A compelling review body long enough to validate.')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(WatchStatePanel::class, ['title' => $title])
            ->call('markWatched')
            ->assertHasNoErrors()
            ->assertSet('watchState.value', 'completed');

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'score' => 9,
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'headline' => 'Strong',
        ]);

        $this->assertDatabaseHas('list_items', [
            'title_id' => $title->id,
            'watch_state' => 'completed',
        ]);
    }
}
