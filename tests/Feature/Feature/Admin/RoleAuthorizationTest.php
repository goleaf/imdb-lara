<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\Contribution;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_capability_gates_match_the_staff_role_matrix(): void
    {
        $regularUser = User::factory()->create();
        $contributor = User::factory()->contributor()->create();
        $editor = User::factory()->editor()->create();
        $moderator = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse($regularUser->can('access-admin-area'));
        $this->assertFalse($contributor->can('access-admin-area'));
        $this->assertTrue($editor->can('access-admin-area'));
        $this->assertTrue($moderator->can('access-admin-area'));
        $this->assertTrue($admin->can('access-admin-area'));
        $this->assertTrue($superAdmin->can('access-admin-area'));

        $this->assertFalse($editor->can('moderate-content'));
        $this->assertTrue($moderator->can('moderate-content'));
        $this->assertTrue($admin->can('manage-catalog'));
        $this->assertTrue($editor->can('manage-catalog'));
        $this->assertFalse($moderator->can('manage-catalog'));
        $this->assertTrue($contributor->can('submit-contribution'));
        $this->assertFalse($regularUser->can('submit-contribution'));
        $this->assertTrue($editor->can('review-contribution'));
        $this->assertFalse($moderator->can('review-contribution'));
    }

    public function test_policies_distinguish_catalog_moderation_and_contribution_access(): void
    {
        $regularUser = User::factory()->create();
        $contributor = User::factory()->contributor()->create();
        $editor = User::factory()->editor()->create();
        $moderator = User::factory()->moderator()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $title = Title::factory()->create();
        $review = Review::factory()->for($regularUser, 'author')->create();
        $report = Report::factory()->for($regularUser, 'reporter')->for($review, 'reportable')->create();
        $contribution = Contribution::factory()->for($contributor)->for($title, 'contributable')->create();
        $mediaAsset = MediaAsset::factory()->create([
            'mediable_type' => Title::class,
            'mediable_id' => $title->id,
        ]);
        $publicList = UserList::factory()->public()->for($regularUser)->create();
        $privateList = UserList::factory()->for($regularUser)->create();

        $this->assertFalse($regularUser->can('viewAny', Title::class));
        $this->assertTrue($editor->can('viewAny', Title::class));
        $this->assertTrue($editor->can('viewAny', Person::class));
        $this->assertFalse($moderator->can('viewAny', Title::class));
        $this->assertTrue($moderator->can('viewAny', Review::class));
        $this->assertTrue($moderator->can('viewAny', Report::class));
        $this->assertFalse($editor->can('viewAny', Review::class));
        $this->assertFalse($contributor->can('viewAny', Report::class));

        $this->assertTrue($contributor->can('create', Contribution::class));
        $this->assertTrue($contributor->can('view', $contribution));
        $this->assertTrue($editor->can('view', $contribution));
        $this->assertFalse($regularUser->can('view', $contribution));

        $this->assertTrue($editor->can('create', MediaAsset::class));
        $this->assertFalse($moderator->can('create', MediaAsset::class));

        $this->assertTrue($regularUser->can('view', $publicList));
        $this->assertFalse($contributor->can('view', $privateList));
        $this->assertTrue($moderator->can('view', $privateList));

        $this->assertTrue($superAdmin->can('forceDelete', $report));
        $this->assertTrue($superAdmin->can('forceDelete', $mediaAsset));
    }
}
