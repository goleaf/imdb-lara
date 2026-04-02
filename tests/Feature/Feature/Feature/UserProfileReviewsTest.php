<?php

namespace Tests\Feature\Feature\Feature;

use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_shows_only_published_reviews(): void
    {
        $user = User::factory()->create([
            'name' => 'Review Author',
        ]);
        $publishedTitle = Title::factory()->create([
            'name' => 'Visible Title',
        ]);
        $draftTitle = Title::factory()->create([
            'name' => 'Draft Title',
        ]);

        Review::factory()->for($user, 'author')->for($publishedTitle)->published()->create([
            'headline' => 'Published profile review',
            'body' => 'Visible review body for the public profile page.',
        ]);
        Review::factory()->for($user, 'author')->for($draftTitle)->draft()->create([
            'headline' => 'Hidden draft review',
            'body' => 'This draft should not appear publicly.',
        ]);

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Published profile review')
            ->assertSee('Visible Title')
            ->assertDontSee('Hidden draft review')
            ->assertDontSee('Draft Title');
    }
}
