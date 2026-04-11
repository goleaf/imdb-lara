<?php

namespace Tests\Feature\Feature\Admin;

use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AdminCatalogReadonlyPagesTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_catalog_only_create_pages_hide_mutation_forms(): void
    {
        config()->set('screenbase.catalog_only', true);

        $admin = User::factory()->admin()->create();

        $pages = [
            route('admin.titles.create'),
            route('admin.people.create'),
            route('admin.genres.create'),
            route('admin.aka-attributes.create'),
            route('admin.aka-types.create'),
            route('admin.award-categories.create'),
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
        $titleId = $this->seedCatalogMovie('tt0000001', 'Readonly Title');
        $personId = $this->seedCatalogPerson('nm0000001', 'Readonly Person');
        $genreId = $this->seedCatalogGenre('Thriller');
        $akaAttributeId = $this->seedCatalogAkaAttribute('Festival title');
        $akaTypeId = $this->seedCatalogAkaType('imdbDisplay');
        $awardCategoryId = $this->seedCatalogAwardCategory('Best Picture');
        $seriesId = $this->seedCatalogMovie('tt0000002', 'Readonly Series', 'tvSeries');
        $season = Season::factory()->create([
            'series_id' => $seriesId,
        ]);
        $episodeTitleId = $this->seedCatalogMovie('tt0000003', 'Readonly Episode', 'tvEpisode');
        $episode = Episode::factory()
            ->for($season, 'season')
            ->create([
                'title_id' => $episodeTitleId,
                'series_id' => $seriesId,
                'season_number' => $season->season_number,
            ]);
        $creditId = $this->seedCatalogCredit($titleId, $personId);
        $mediaAsset = MediaAsset::factory()->create([
            'mediable_type' => Title::class,
            'mediable_id' => $titleId,
        ]);

        $pages = [
            route('admin.titles.edit', ['title' => $titleId]),
            route('admin.people.edit', ['person' => $personId]),
            route('admin.genres.edit', ['genre' => $genreId]),
            route('admin.aka-attributes.edit', ['akaAttribute' => $akaAttributeId]),
            route('admin.aka-types.edit', ['akaType' => $akaTypeId]),
            route('admin.award-categories.edit', ['awardCategory' => $awardCategoryId]),
            route('admin.credits.edit', ['credit' => $creditId]),
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
        $titleId = $this->seedCatalogMovie('tt0000004', 'Readonly Media Asset Title');
        $mediaAsset = MediaAsset::factory()->create([
            'mediable_type' => Title::class,
            'mediable_id' => $titleId,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.media-assets.index'))
            ->assertOk()
            ->assertSee('Media Asset Mutations Paused')
            ->assertSee('Read only')
            ->assertDontSee('<form method="POST"', false);
    }

    private function seedCatalogMovie(string $imdbId, string $primaryTitle, string $titleType = 'movie'): int
    {
        DB::connection('imdb_mysql')->table('movies')->insert([
            'tconst' => $imdbId,
            'imdb_id' => $imdbId,
            'titletype' => $titleType,
            'primarytitle' => $primaryTitle,
            'originaltitle' => $primaryTitle,
            'isadult' => 0,
            'startyear' => 2024,
            'endyear' => null,
            'runtimeminutes' => 120,
            'title_type_id' => null,
            'runtimeSeconds' => 7200,
        ]);

        $movieId = (int) DB::connection('imdb_mysql')->table('movies')
            ->where('tconst', $imdbId)
            ->value('id');

        DB::table('titles')->insert([
            'id' => $movieId,
            'name' => $primaryTitle,
            'original_name' => $primaryTitle,
            'slug' => strtolower($imdbId),
            'title_type' => $titleType === 'tvEpisode' ? 'episode' : ($titleType === 'tvSeries' ? 'series' : 'movie'),
            'release_year' => 2024,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $movieId;
    }

    private function seedCatalogGenre(string $name): int
    {
        DB::connection('imdb_mysql')->table('genres')->insert([
            'name' => $name,
        ]);

        return (int) DB::connection('imdb_mysql')->table('genres')
            ->where('name', $name)
            ->value('id');
    }

    private function seedCatalogAkaAttribute(string $name): int
    {
        DB::connection('imdb_mysql')->table('aka_attributes')->insert([
            'name' => $name,
        ]);

        return (int) DB::connection('imdb_mysql')->table('aka_attributes')
            ->where('name', $name)
            ->value('id');
    }

    private function seedCatalogAkaType(string $name): int
    {
        DB::connection('imdb_mysql')->table('aka_types')->insert([
            'name' => $name,
        ]);

        return (int) DB::connection('imdb_mysql')->table('aka_types')
            ->where('name', $name)
            ->value('id');
    }

    private function seedCatalogAwardCategory(string $name): int
    {
        DB::connection('imdb_mysql')->table('award_categories')->insert([
            'name' => $name,
        ]);

        return (int) DB::connection('imdb_mysql')->table('award_categories')
            ->where('name', $name)
            ->value('id');
    }

    private function seedCatalogPerson(string $imdbId, string $primaryName): int
    {
        DB::connection('imdb_mysql')->table('name_basics')->insert([
            'nconst' => $imdbId,
            'imdb_id' => $imdbId,
            'primaryname' => $primaryName,
            'displayName' => $primaryName,
            'primaryprofession' => 'director',
            'alternativeNames' => $primaryName,
            'biography' => 'Catalog-only readonly fixture biography.',
            'primaryProfessions' => 'director',
        ]);

        return (int) DB::connection('imdb_mysql')->table('name_basics')
            ->where('nconst', $imdbId)
            ->value('id');
    }

    private function seedCatalogCredit(int $titleId, int $personId): int
    {
        DB::connection('imdb_mysql')->table('name_credits')->insert([
            'name_basic_id' => $personId,
            'movie_id' => $titleId,
            'category' => 'director',
            'episode_count' => 1,
            'position' => 1,
        ]);

        return (int) DB::connection('imdb_mysql')->table('name_credits')
            ->where('name_basic_id', $personId)
            ->where('movie_id', $titleId)
            ->where('category', 'director')
            ->value('id');
    }
}
