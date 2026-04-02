<?php

namespace Tests\Feature\Feature\Users;

use App\Enums\ListVisibility;
use App\Models\ListItem;
use App\Models\Rating;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('account.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_settings_page_requires_authentication(): void
    {
        $this->get(route('account.settings'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_surfaces_recent_activity_summaries_and_quick_links(): void
    {
        $user = User::factory()->create([
            'name' => 'Ari Lane',
            'username' => 'ari-lane',
        ]);

        $watchlist = UserList::factory()->watchlist()->for($user)->create();
        $privateList = UserList::factory()->for($user)->create([
            'name' => 'Weekend Thrillers',
            'slug' => 'weekend-thrillers',
        ]);
        $publicList = UserList::factory()->public()->for($user)->create([
            'name' => 'Festival Favourites',
            'slug' => 'festival-favourites',
        ]);

        $ratedTitle = Title::factory()->movie()->create([
            'name' => 'Night Atlas',
            'slug' => 'night-atlas',
        ]);
        $reviewedTitle = Title::factory()->series()->create([
            'name' => 'Signal Harbor',
            'slug' => 'signal-harbor',
        ]);

        Rating::factory()->for($user)->for($ratedTitle)->create([
            'score' => 9,
            'created_at' => now()->subHour(),
        ]);

        Review::factory()->published()->for($user, 'author')->for($reviewedTitle)->create([
            'headline' => 'Tense from the first scene',
            'published_at' => now()->subMinutes(30),
        ]);

        ListItem::factory()->for($watchlist, 'userList')->for($ratedTitle)->create();
        ListItem::factory()->completed()->for($watchlist, 'userList')->for($reviewedTitle)->create();
        ListItem::factory()->for($privateList, 'userList')->for($ratedTitle)->create();
        ListItem::factory()->for($publicList, 'userList')->for($reviewedTitle)->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Recent activity')
            ->assertSee('Watchlist summary')
            ->assertSee('Recent ratings')
            ->assertSee('Recent reviews')
            ->assertSee('Quick links')
            ->assertSee('Profile settings')
            ->assertSee('Night Atlas')
            ->assertSee('Signal Harbor')
            ->assertSee('Weekend Thrillers')
            ->assertSee('Festival Favourites');
    }

    public function test_authenticated_member_can_open_dedicated_settings_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Ari Lane',
            'username' => 'ari-lane',
        ]);

        UserList::factory()->watchlist()->for($user)->create([
            'visibility' => ListVisibility::Public,
        ]);

        $this->actingAs($user)
            ->get(route('account.settings'))
            ->assertOk()
            ->assertSee('Profile Settings')
            ->assertSee('Current identity')
            ->assertSee('Visibility rules')
            ->assertSee('Save settings')
            ->assertSee('@ari-lane');
    }

    public function test_public_profile_renders_visible_sections_for_public_users(): void
    {
        $user = User::factory()->create([
            'name' => 'Morgan Vale',
            'username' => 'morgan-vale',
            'profile_visibility' => 'public',
            'show_ratings_on_profile' => true,
        ]);

        $ratedTitle = Title::factory()->movie()->create([
            'name' => 'Blue Summit',
            'slug' => 'blue-summit',
        ]);
        $reviewedTitle = Title::factory()->series()->create([
            'name' => 'Glass District',
            'slug' => 'glass-district',
        ]);

        Rating::factory()->for($user)->for($ratedTitle)->create([
            'score' => 8,
        ]);

        Review::factory()->published()->for($user, 'author')->for($reviewedTitle)->create([
            'headline' => 'A razor-sharp comeback season',
        ]);

        $watchlist = UserList::factory()->watchlist()->for($user)->create([
            'visibility' => ListVisibility::Public,
        ]);
        ListItem::factory()->for($watchlist, 'userList')->for($ratedTitle)->create();

        $publicList = UserList::factory()->public()->for($user)->create([
            'name' => 'Neo-noir Essentials',
            'slug' => 'neo-noir-essentials',
        ]);
        ListItem::factory()->for($publicList, 'userList')->for($reviewedTitle)->create();

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Public watchlist')
            ->assertSee('Public Lists')
            ->assertSee('Recent Reviews')
            ->assertSee('Recent ratings')
            ->assertSee('Blue Summit')
            ->assertSee('Glass District')
            ->assertSee('Neo-noir Essentials');
    }

    public function test_private_profiles_are_not_publicly_accessible(): void
    {
        $user = User::factory()->create([
            'username' => 'hidden-profile',
            'profile_visibility' => 'private',
        ]);

        $publicList = UserList::factory()->public()->for($user)->create();
        ListItem::factory()->for($publicList, 'userList')->create();

        Rating::factory()->for($user)->create();

        $this->get(route('public.users.show', $user))
            ->assertNotFound();
    }

    public function test_public_profile_hides_ratings_when_owner_disables_that_section(): void
    {
        $user = User::factory()->create([
            'username' => 'quiet-rater',
            'profile_visibility' => 'public',
            'show_ratings_on_profile' => false,
        ]);

        $hiddenRatingTitle = Title::factory()->movie()->create([
            'name' => 'Hidden Score Title',
            'slug' => 'hidden-score-title',
        ]);
        $visibleListTitle = Title::factory()->movie()->create([
            'name' => 'Visible List Title',
            'slug' => 'visible-list-title',
        ]);

        Rating::factory()->for($user)->for($hiddenRatingTitle)->create([
            'score' => 10,
        ]);

        $publicList = UserList::factory()->public()->for($user)->create([
            'name' => 'Visible Profile Anchor',
            'slug' => 'visible-profile-anchor',
        ]);
        ListItem::factory()->for($publicList, 'userList')->for($visibleListTitle)->create();

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Recent ratings')
            ->assertSee('Ratings are private on this profile.')
            ->assertDontSee('Hidden Score Title')
            ->assertSee('Visible Profile Anchor');
    }

    public function test_public_profile_stays_available_without_public_activity(): void
    {
        $user = User::factory()->create([
            'name' => 'Casey North',
            'username' => 'casey-north',
            'profile_visibility' => 'public',
            'show_ratings_on_profile' => false,
        ]);

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSee('Casey North')
            ->assertSee('Recent Reviews')
            ->assertSee('Recent ratings')
            ->assertSee('Ratings are private on this profile.')
            ->assertSee('Watchlist visibility')
            ->assertSee('Watchlist is private on this profile.')
            ->assertSee('No public lists are visible for this profile.');
    }

    public function test_public_profile_lists_are_ordered_by_recent_updates(): void
    {
        $user = User::factory()->create([
            'username' => 'list-curator',
            'profile_visibility' => 'public',
        ]);

        $olderList = UserList::factory()->public()->for($user)->create([
            'name' => 'Archive Run',
            'slug' => 'archive-run',
            'updated_at' => now()->subWeek(),
        ]);

        $newerList = UserList::factory()->public()->for($user)->create([
            'name' => 'Fresh Discoveries',
            'slug' => 'fresh-discoveries',
            'updated_at' => now()->subHour(),
        ]);

        ListItem::factory()->for($olderList, 'userList')->create();
        ListItem::factory()->for($newerList, 'userList')->create();

        $this->get(route('public.users.show', $user))
            ->assertOk()
            ->assertSeeInOrder([
                'Fresh Discoveries',
                'Archive Run',
            ]);
    }
}
