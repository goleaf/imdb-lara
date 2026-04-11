<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AdminCatalogReadonlyPagesTest extends TestCase
{
    use InteractsWithRemoteCatalog;
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
        $title = $this->sampleTitle();
        $series = $this->sampleSeries();
        $person = $this->samplePerson();
        $genre = $this->sampleGenre();
        $season = $this->sampleSeason();
        $episode = $this->sampleEpisode();
        $credit = $this->sampleCredit();
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
        $title = $this->sampleTitle();
        $mediaAsset = MediaAsset::factory()->for($title, 'mediable')->create();

        $this->actingAs($admin)
            ->get(route('admin.media-assets.index'))
            ->assertOk()
            ->assertSee('Media Asset Mutations Paused')
            ->assertSee('Read only')
            ->assertDontSee('<form method="POST"', false);
    }

    private function sampleCredit(): Credit
    {
        return Credit::query()
            ->select([
                'name_credits.id',
                'name_credits.movie_id',
                'name_credits.name_basic_id',
                'name_credits.category',
                'name_credits.episode_count',
                'name_credits.position',
            ])
            ->orderBy('name_credits.position')
            ->orderBy('name_credits.id')
            ->firstOrFail();
    }

    private function sampleSeason(): Season
    {
        return Season::query()
            ->select([
                'movie_seasons.movie_id',
                'movie_seasons.season',
                'movie_seasons.episode_count',
            ])
            ->where('movie_seasons.episode_count', '>', 0)
            ->with([
                'series' => fn ($query) => $query->select([
                    'movies.id',
                    'movies.tconst',
                    'movies.imdb_id',
                    'movies.primarytitle',
                    'movies.originaltitle',
                    'movies.titletype',
                    'movies.isadult',
                    'movies.startyear',
                    'movies.endyear',
                    'movies.runtimeminutes',
                    'movies.title_type_id',
                    'movies.runtimeSeconds',
                ]),
            ])
            ->orderBy('movie_seasons.movie_id')
            ->orderBy('movie_seasons.season')
            ->firstOrFail();
    }

    private function sampleEpisode(): Episode
    {
        return Episode::query()
            ->select([
                'movie_episodes.episode_movie_id',
                'movie_episodes.movie_id',
                'movie_episodes.season',
                'movie_episodes.episode_number',
                'movie_episodes.release_year',
                'movie_episodes.release_month',
                'movie_episodes.release_day',
            ])
            ->orderBy('movie_episodes.movie_id')
            ->orderBy('movie_episodes.season')
            ->orderBy('movie_episodes.episode_number')
            ->firstOrFail();
    }
}
