<?php

namespace Tests\Feature\Feature\Database;

use App\Enums\UserRole;
use App\Models\AwardNomination;
use App\Models\Contribution;
use App\Models\Episode;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleTranslation;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImdbCatalogSeederTest extends TestCase
{
    public function test_migrate_fresh_with_seed_builds_a_full_demo_catalog(): void
    {
        $exitCode = Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertGreaterThanOrEqual(12, Title::query()->count());
        $this->assertGreaterThanOrEqual(8, Person::query()->count());
        $this->assertGreaterThanOrEqual(3, Season::query()->count());
        $this->assertGreaterThanOrEqual(4, Episode::query()->count());
        $this->assertGreaterThanOrEqual(2, TitleTranslation::query()->count());
        $this->assertGreaterThanOrEqual(3, AwardNomination::query()->count());
        $this->assertGreaterThanOrEqual(2, Contribution::query()->count());

        $superAdmin = User::query()->where('email', 'superadmin@example.com')->first();
        $admin = User::query()->where('email', 'admin@example.com')->first();
        $editor = User::query()->where('email', 'editor@example.com')->first();
        $moderator = User::query()->where('email', 'moderator@example.com')->first();
        $contributor = User::query()->where('email', 'contributor@example.com')->first();
        $member = User::query()->where('email', 'member@example.com')->first();

        $this->assertNotNull($superAdmin);
        $this->assertNotNull($admin);
        $this->assertNotNull($editor);
        $this->assertNotNull($moderator);
        $this->assertNotNull($contributor);
        $this->assertNotNull($member);
        $this->assertSame(UserRole::SuperAdmin, $superAdmin?->role);
        $this->assertSame(UserRole::Admin, $admin?->role);
        $this->assertSame(UserRole::Editor, $editor?->role);
        $this->assertSame(UserRole::Moderator, $moderator?->role);
        $this->assertSame(UserRole::Contributor, $contributor?->role);
        $this->assertSame(UserRole::RegularUser, $member?->role);
        $this->assertTrue($superAdmin?->can('access-admin-area') ?? false);
        $this->assertTrue($admin?->can('access-admin-area') ?? false);
        $this->assertTrue($editor?->can('manage-catalog') ?? false);
        $this->assertTrue($moderator?->can('moderate-content') ?? false);
        $this->assertTrue($contributor?->can('submit-contribution') ?? false);
        $this->assertNotNull($member?->watchlist);
        $this->assertGreaterThanOrEqual(1, $member?->notifications()->count() ?? 0);
    }
}
