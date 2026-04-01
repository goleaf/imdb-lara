<?php

namespace Tests\Feature\Feature\Database;

use App\Models\Person;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImdbCatalogSeederTest extends TestCase
{
    public function test_migrate_fresh_with_seed_builds_a_demo_catalog(): void
    {
        $exitCode = Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertGreaterThanOrEqual(8, Title::query()->count());
        $this->assertGreaterThanOrEqual(8, Person::query()->count());
        $this->assertGreaterThanOrEqual(3, Review::query()->count());

        $admin = User::query()->where('email', 'admin@example.com')->first();
        $member = User::query()->where('email', 'member@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($member);
        $this->assertNotNull($member?->watchlist);
    }
}
