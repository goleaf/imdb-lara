<?php

namespace Tests\Feature\Feature;

use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BrowseTopRatedSeriesPageLocalRenderTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
        $this->setUpCatalogCardLookupTables();
    }

    public function test_top_rated_series_page_renders_lowercase_imdb_series_types(): void
    {
        $series = Title::query()->create([
            'tconst' => 'tt1122334',
            'imdb_id' => 'tt1122334',
            'titletype' => 'tvseries',
            'primarytitle' => 'Severance',
            'originaltitle' => 'Severance',
            'isadult' => 0,
            'startyear' => 2022,
            'runtimeminutes' => 50,
            'runtimeSeconds' => 3000,
        ]);

        $miniSeries = Title::query()->create([
            'tconst' => 'tt2233445',
            'imdb_id' => 'tt2233445',
            'titletype' => 'tvminiseries',
            'primarytitle' => 'Chernobyl',
            'originaltitle' => 'Chernobyl',
            'isadult' => 0,
            'startyear' => 2019,
            'runtimeminutes' => 60,
            'runtimeSeconds' => 3600,
        ]);

        MoviePrimaryImage::query()->create([
            'movie_id' => $series->id,
            'url' => 'https://images.test/severance-poster.jpg',
            'width' => 1000,
            'height' => 1500,
            'type' => 'poster',
        ]);

        MoviePrimaryImage::query()->create([
            'movie_id' => $miniSeries->id,
            'url' => 'https://images.test/chernobyl-poster.jpg',
            'width' => 1000,
            'height' => 1500,
            'type' => 'poster',
        ]);

        TitleStatistic::query()->create([
            'movie_id' => $series->id,
            'aggregate_rating' => 8.7,
            'vote_count' => 845123,
        ]);

        TitleStatistic::query()->create([
            'movie_id' => $miniSeries->id,
            'aggregate_rating' => 9.3,
            'vote_count' => 910234,
        ]);

        MoviePlot::query()->create([
            'movie_id' => $series->id,
            'plot' => 'A fractured workplace thriller where memory is divided between office life and the outside world.',
        ]);

        MoviePlot::query()->create([
            'movie_id' => $miniSeries->id,
            'plot' => 'A historical disaster drama about the Chernobyl nuclear accident and the people forced to contain it.',
        ]);

        $response = $this->get(route('public.rankings.series'))
            ->assertOk()
            ->assertSee('Top Rated Series')
            ->assertSee('Chernobyl')
            ->assertSee('Severance')
            ->assertSee('A historical disaster drama about the Chernobyl nuclear accident')
            ->assertSee('View title')
            ->assertSeeHtml('data-slot="chart-title-card"');

        $this->assertSame(1, substr_count($response->getContent(), '910,234 votes'));
    }

    private function setUpCatalogCardLookupTables(): void
    {
        Schema::connection('imdb_mysql')->create('countries', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('name')->nullable();
        });

        Schema::connection('imdb_mysql')->create('languages', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('name')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_origin_countries', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->string('country_code');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_spoken_languages', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->string('language_code');
            $table->unsignedSmallInteger('position')->nullable();
        });
    }
}
