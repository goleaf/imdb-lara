<?php

namespace Tests\Feature\Feature\Livewire;

use App\Enums\ListVisibility;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileSettingsPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_update_profile_settings_from_the_dashboard_panel(): void
    {
        $user = User::factory()->create([
            'name' => 'Initial Curator',
            'username' => 'initial-curator',
        ]);

        UserList::factory()->watchlist()->for($user)->create([
            'visibility' => ListVisibility::Public,
        ]);

        Livewire::actingAs($user)
            ->test('account.profile-settings-panel')
            ->assertSeeHtml('data-slot="checkbox-wrapper"')
            ->assertSee('Show ratings on your public profile')
            ->assertSee('Reviews, lists, and watchlist visibility continue to follow their own privacy rules.')
            ->assertSet('watchlistVisibility', ListVisibility::Public->value)
            ->set('name', 'Updated Curator')
            ->set('bio', 'Obsessed with conspiracy thrillers and prestige miniseries.')
            ->set('profileVisibility', 'private')
            ->set('showRatingsOnProfile', false)
            ->call('save')
            ->assertSet('statusMessage', 'Profile settings updated.')
            ->assertSet('publicProfileIsLive', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Curator',
            'bio' => 'Obsessed with conspiracy thrillers and prestige miniseries.',
            'profile_visibility' => 'private',
            'show_ratings_on_profile' => false,
        ]);
    }

    public function test_public_profile_is_marked_live_even_without_visible_profile_content(): void
    {
        $user = User::factory()->create([
            'name' => 'Casey North',
            'username' => 'casey-north',
            'profile_visibility' => 'public',
            'show_ratings_on_profile' => false,
        ]);

        UserList::factory()->watchlist()->for($user)->create([
            'visibility' => ListVisibility::Private,
        ]);

        Livewire::actingAs($user)
            ->test('account.profile-settings-panel')
            ->assertSet('profileVisibility', 'public')
            ->assertSet('watchlistVisibility', ListVisibility::Private->value)
            ->assertSet('showRatingsOnProfile', false)
            ->assertSet('publicProfileIsLive', true);
    }
}
