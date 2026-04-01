<?php

namespace Tests\Feature\Feature\Foundations;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
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

    public function test_demo_catalog_seeder_uses_curated_imdb_media_asset_urls(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $northernSignalPoster = MediaAsset::query()
            ->whereMorphedTo('mediable', Title::query()->where('slug', 'northern-signal')->firstOrFail())
            ->where('kind', MediaKind::Poster)
            ->firstOrFail();

        $avaMercerHeadshot = MediaAsset::query()
            ->whereMorphedTo('mediable', Person::query()->where('slug', 'ava-mercer')->firstOrFail())
            ->where('kind', MediaKind::Headshot)
            ->firstOrFail();

        $this->assertSame('imdb', $northernSignalPoster->provider);
        $this->assertSame('imdb', $avaMercerHeadshot->provider);
        $this->assertStringStartsWith('https://m.media-amazon.com/images/M/', $northernSignalPoster->url);
        $this->assertStringStartsWith('https://m.media-amazon.com/images/M/', $avaMercerHeadshot->url);
        $this->assertNotNull($northernSignalPoster->provider_key);
        $this->assertNotNull($avaMercerHeadshot->provider_key);
    }
}
