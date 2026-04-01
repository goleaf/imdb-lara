<?php

namespace Tests\Feature\Feature\Livewire;

use App\Livewire\Titles\RatingPanel;
use App\Livewire\Titles\ReviewComposer;
use App\Livewire\Titles\WatchlistToggle;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TitleInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_components_require_authentication_for_mutations(): void
    {
        $title = Title::factory()->create();

        Livewire::test(WatchlistToggle::class, ['title' => $title])
            ->call('toggle')
            ->assertRedirect(route('login'));

        Livewire::test(RatingPanel::class, ['title' => $title])
            ->set('score', 9)
            ->call('save')
            ->assertRedirect(route('login'));

        Livewire::test(ReviewComposer::class, ['title' => $title])
            ->set('headline', 'Strong')
            ->set('body', 'A compelling review body long enough to validate.')
            ->call('save')
            ->assertRedirect(route('login'));
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
            ->set('score', 9)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('headline', 'Strong')
            ->set('body', 'A compelling review body long enough to validate.')
            ->call('save')
            ->assertHasNoErrors();

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
    }
}
