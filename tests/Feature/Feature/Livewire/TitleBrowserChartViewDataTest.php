<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use App\Livewire\Catalog\TitleBrowser;
use App\Models\Genre;
use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleBrowserChartViewDataTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
        $this->setUpCatalogCardLookupTables();
    }

    public function test_chart_mode_prepares_chart_card_view_data_in_livewire(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'Matrix',
            'isadult' => 0,
            'startyear' => 1999,
            'runtimeminutes' => 136,
            'runtimeSeconds' => 8160,
        ]);

        $genre = Genre::query()->create([
            'name' => 'Science Fiction',
        ]);

        DB::connection('imdb_mysql')->table('movie_genres')->insert([
            'movie_id' => $title->id,
            'genre_id' => $genre->id,
            'position' => 1,
        ]);

        DB::connection('imdb_mysql')->table('countries')->insert([
            'code' => 'US',
            'name' => 'United States',
        ]);

        DB::connection('imdb_mysql')->table('movie_origin_countries')->insert([
            'movie_id' => $title->id,
            'country_code' => 'US',
            'position' => 1,
        ]);

        MoviePrimaryImage::query()->create([
            'movie_id' => $title->id,
            'url' => 'https://images.test/the-matrix-poster.jpg',
            'width' => 1000,
            'height' => 1500,
            'type' => 'poster',
        ]);

        MoviePlot::query()->create([
            'movie_id' => $title->id,
            'plot' => 'A hacker learns the world is a simulation and joins the resistance.',
        ]);

        TitleStatistic::query()->create([
            'movie_id' => $title->id,
            'aggregate_rating' => 8.7,
            'vote_count' => 2100000,
        ]);

        $titles = Title::query()
            ->selectCatalogCardColumns()
            ->with(Title::catalogCardRelations())
            ->whereKey($title->id)
            ->get();

        $action = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $action
            ->shouldReceive('handleSafely')
            ->andReturn([
                'titles' => new Paginator(
                    items: $titles,
                    perPage: 12,
                    currentPage: 1,
                    options: [
                        'path' => route('public.rankings.movies'),
                        'pageName' => 'titles',
                    ],
                ),
                'usingStaleCache' => false,
                'isUnavailable' => false,
            ]);

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $action);

        $viewData = Livewire::test(TitleBrowser::class, [
            'displayMode' => 'chart',
        ])->instance()->viewData();

        $this->assertSame([
            'comparisonLabel' => '2,100,000 votes',
            'comparisonToken' => null,
            'movementAmount' => 0,
            'movementDirection' => 'steady',
            'movementIcon' => 'minus',
            'movementLabel' => 'Steady',
            'movementNote' => null,
            'originCountryCode' => 'US',
            'originCountryLabel' => 'United States',
            'originalTitle' => 'Matrix',
            'rank' => 1,
            'releaseYear' => 1999,
            'runtimeLabel' => $title->runtimeMinutesLabel(),
            'summaryText' => 'A hacker learns the world is a simulation and joins the resistance.',
            'titleUrl' => route('public.titles.show', $title),
            'voteLabel' => '2,100,000 votes',
        ], collect($viewData['chartRows'][$title->id])
            ->except(['genres', 'poster'])
            ->all());

        $this->assertSame('Science Fiction', $viewData['chartRows'][$title->id]['genres']->first()?->name);
        $this->assertSame('https://images.test/the-matrix-poster.jpg', $viewData['chartRows'][$title->id]['poster']?->url);
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
