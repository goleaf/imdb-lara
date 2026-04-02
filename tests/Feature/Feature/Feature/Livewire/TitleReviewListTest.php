<?php

namespace Tests\Feature\Feature\Feature\Livewire;

use App\Livewire\Reviews\TitleReviewList;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TitleReviewListTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_review_list_shows_only_published_reviews_and_supports_public_sorting(): void
    {
        $title = Title::factory()->create();
        $viewer = User::factory()->create();

        $newestReview = Review::factory()->for($title)->published()->create([
            'headline' => 'Newest review',
            'published_at' => now(),
        ]);
        $mostHelpfulReview = Review::factory()->for($title)->published()->create([
            'headline' => 'Most helpful review',
            'published_at' => now()->subDay(),
        ]);
        Review::factory()->for($title)->draft()->create([
            'headline' => 'Draft review',
        ]);

        ReviewVote::factory()->count(3)->helpful()->for($mostHelpfulReview)->create();
        ReviewVote::factory()->count(1)->helpful()->for($newestReview)->create();

        Livewire::actingAs($viewer)
            ->test(TitleReviewList::class, ['title' => $title])
            ->assertSeeInOrder(['Newest review', 'Most helpful review'])
            ->assertDontSee('Draft review')
            ->call('setSort', 'helpful')
            ->assertSeeInOrder(['Most helpful review', 'Newest review']);
    }

    public function test_authenticated_user_can_toggle_helpful_vote_for_a_review(): void
    {
        $title = Title::factory()->create();
        $reviewAuthor = User::factory()->create();
        $voter = User::factory()->create();
        $review = Review::factory()->for($title)->for($reviewAuthor, 'author')->published()->create();

        Livewire::actingAs($voter)
            ->test(TitleReviewList::class, ['title' => $title])
            ->call('toggleHelpful', $review->id)
            ->assertHasNoErrors()
            ->assertSee('Marked as helpful.');

        $this->assertDatabaseHas('review_votes', [
            'review_id' => $review->id,
            'user_id' => $voter->id,
            'is_helpful' => true,
        ]);

        Livewire::actingAs($voter)
            ->test(TitleReviewList::class, ['title' => $title])
            ->call('toggleHelpful', $review->id)
            ->assertHasNoErrors()
            ->assertSee('Helpful vote removed.');

        $this->assertDatabaseMissing('review_votes', [
            'review_id' => $review->id,
            'user_id' => $voter->id,
        ]);
    }

    public function test_guest_is_redirected_when_trying_to_vote_helpful(): void
    {
        $title = Title::factory()->create();
        $review = Review::factory()->for($title)->published()->create();

        Livewire::test(TitleReviewList::class, ['title' => $title])
            ->call('toggleHelpful', $review->id)
            ->assertRedirect(route('login'));
    }
}
