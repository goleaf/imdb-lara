<?php

namespace Tests\Feature\Feature\Foundations;

use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_catalog_seeder_creates_foundational_imdb_data(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $this->assertGreaterThan(0, Title::query()->count());
        $this->assertGreaterThan(0, Person::query()->count());

        $admin = User::query()->where('email', 'admin@example.com')->first();
        $member = User::query()->where('email', 'member@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($member);
        $this->assertNotNull($member?->watchlist);
    }
}
