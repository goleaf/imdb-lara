<?php

namespace Tests\Feature\Feature;

use App\Models\Country;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BrowseTitlesPageLocalRenderTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();

        Schema::connection('imdb_mysql')->create('countries', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('name')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_origin_countries', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->string('country_code');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('languages', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('name')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_spoken_languages', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->string('language_code');
            $table->unsignedSmallInteger('position')->nullable();
        });
    }

    public function test_titles_index_renders_local_title_cards_with_rating_and_votes(): void
    {
        $title = $this->makeTitle('tt0133093', 'The Matrix', 'movie', 1999);
        $genre = Genre::query()->create(['name' => 'Science Fiction']);

        $title->genres()->attach($genre->id, ['position' => 1]);
        TitleStatistic::query()->create([
            'movie_id' => $title->id,
            'aggregate_rating' => '8.70',
            'vote_count' => 2100000,
        ]);

        $this->get(route('public.titles.index'))
            ->assertOk()
            ->assertSee('Browse Titles')
            ->assertSee('The Matrix')
            ->assertSee('Science Fiction')
            ->assertSee('8.7')
            ->assertSee('2,100,000 votes');
    }

    public function test_trending_chart_respects_the_selected_country_context(): void
    {
        $title = $this->makeTitle('tt0095016', 'Die Hard', 'movie', 1988);

        Country::query()->create([
            'code' => 'LT',
            'name' => 'Lithuania',
        ]);
        Language::query()->create([
            'code' => 'EN',
            'name' => 'English',
        ]);

        $title->countries()->attach('LT', ['position' => 1]);
        $title->languages()->attach('EN', ['position' => 1]);
        TitleStatistic::query()->create([
            'movie_id' => $title->id,
            'aggregate_rating' => '8.20',
            'vote_count' => 750000,
        ]);

        $this->get(route('public.trending', ['country' => 'LT']))
            ->assertOk()
            ->assertSee('Local Charts')
            ->assertSee('Lithuania')
            ->assertSee('Lithuania local chart');
    }

    private function makeTitle(string $imdbId, string $name, string $type, int $year): Title
    {
        return Title::query()->create([
            'tconst' => $imdbId,
            'imdb_id' => $imdbId,
            'titletype' => $type,
            'primarytitle' => $name,
            'originaltitle' => $name,
            'isadult' => 0,
            'startyear' => $year,
        ]);
    }
}
