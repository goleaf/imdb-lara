<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AdminCatalogReadonlyPagesTest extends TestCase
{
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    public function test_catalog_only_create_pages_hide_mutation_forms(): void
    {
        config()->set('screenbase.catalog_only', true);

        $admin = User::factory()->admin()->create();

        $pages = [
            route('admin.titles.create'),
            route('admin.people.create'),
            route('admin.genres.create'),
            route('admin.credits.create'),
        ];

        foreach ($pages as $pageUrl) {
            $this->actingAs($admin)
                ->get($pageUrl)
                ->assertOk()
                ->assertSee('Catalog-only mode')
                ->assertSee('Livewire shell active')
                ->assertDontSee('<form method="POST"', false);
        }
    }

    public function test_catalog_only_edit_pages_hide_mutation_forms(): void
    {
        config()->set('screenbase.catalog_only', true);

        $admin = User::factory()->admin()->create();
        $title = Title::factory()->create();
        $person = Person::factory()->create();
        $genre = Genre::factory()->create();
        $series = Title::factory()->series()->create();
        $season = Season::factory()->for($series, 'series')->create();
        $episodeTitle = Title::factory()->episode()->create();
        $episode = Episode::factory()
            ->for($episodeTitle, 'title')
            ->for($series, 'series')
            ->for($season, 'season')
            ->create([
                'season_number' => $season->season_number,
            ]);
        $credit = Credit::factory()
            ->for($title)
            ->for($person)
            ->create();
        $mediaAsset = MediaAsset::factory()->for($title, 'mediable')->create();

        $pages = [
            route('admin.titles.edit', $title),
            route('admin.people.edit', $person),
            route('admin.genres.edit', $genre),
            route('admin.credits.edit', $credit),
            route('admin.media-assets.edit', $mediaAsset),
            route('admin.seasons.edit', $season),
            route('admin.episodes.edit', $episode),
        ];

        foreach ($pages as $pageUrl) {
            $this->actingAs($admin)
                ->get($pageUrl)
                ->assertOk()
                ->assertSee('Catalog-only mode')
                ->assertSee('Livewire shell active')
                ->assertDontSee('<form method="POST"', false);
        }
    }

    public function test_catalog_only_media_assets_index_hides_delete_actions(): void
    {
        config()->set('screenbase.catalog_only', true);

        $admin = User::factory()->admin()->create();
        $title = Title::factory()->create();
        $mediaAsset = MediaAsset::factory()->for($title, 'mediable')->create();

        $this->actingAs($admin)
            ->get(route('admin.media-assets.index'))
            ->assertOk()
            ->assertSee('Media Asset Mutations Paused')
            ->assertSee('Read only')
            ->assertDontSee('<form method="POST"', false);
    }
}
