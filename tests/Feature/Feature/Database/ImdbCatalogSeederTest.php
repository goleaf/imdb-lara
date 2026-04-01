<?php

namespace Tests\Feature\Feature\Database;

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
        $contributor = User::query()->where('email', 'contributor@example.com')->first();
        $member = User::query()->where('email', 'member@example.com')->first();

        $this->assertNotNull($superAdmin);
        $this->assertNotNull($admin);
        $this->assertNotNull($editor);
        $this->assertNotNull($contributor);
        $this->assertNotNull($member);
        $this->assertNotNull($member?->watchlist);
        $this->assertGreaterThanOrEqual(1, $member?->notifications()->count() ?? 0);
    }
}
