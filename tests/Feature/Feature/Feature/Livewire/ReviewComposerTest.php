<?php

namespace Tests\Feature\Feature\Feature\Livewire;

use App\Enums\ReviewStatus;
use App\Livewire\Titles\ReviewComposer;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_review_draft_for_a_title(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->assertSeeHtml('data-slot="checkbox-wrapper"')
            ->assertSee('This review contains spoilers.')
            ->assertSee('Use this when discussing reveals, endings, or twists that change how someone experiences the title.');

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Draft thoughts')
            ->set('form.body', 'A short draft review body.')
            ->set('form.containsSpoilers', true)
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertSee('Draft saved.');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'headline' => 'Draft thoughts',
            'status' => ReviewStatus::Draft->value,
            'contains_spoilers' => true,
        ]);
    }

    public function test_authenticated_user_keeps_one_primary_review_per_title_and_can_update_it(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'First headline')
            ->set('form.body', 'This is a complete review body with enough detail to validate.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Review sent to moderation.');

        $component
            ->set('form.headline', 'Updated headline')
            ->set('form.body', 'This is an updated review body with even more detail for the second save.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            1,
            Review::query()
                ->where('user_id', $user->id)
                ->where('title_id', $title->id)
                ->count(),
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'title_id' => $title->id,
            'headline' => 'Updated headline',
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_review_author_can_delete_their_existing_review(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();
        $review = Review::factory()->for($user, 'author')->for($title)->pending()->create();

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->call('delete')
            ->assertHasNoErrors()
            ->assertSee('Review deleted.');

        $this->assertSoftDeleted($review);
    }

    public function test_moderator_review_submission_publishes_immediately(): void
    {
        $moderator = User::factory()->moderator()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($moderator)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.headline', 'Moderator review')
            ->set('form.body', 'A fully published review written by a moderator for immediate visibility.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Review published.');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $moderator->id,
            'title_id' => $title->id,
            'status' => ReviewStatus::Published->value,
        ]);
    }

    public function test_review_composer_validates_review_body_during_field_updates(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($user)
            ->test(ReviewComposer::class, ['title' => $title])
            ->set('form.body', 'bad')
            ->assertHasErrors(['form.body' => ['min']]);
    }
}
