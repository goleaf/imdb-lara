<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Actions\Catalog\GetFeaturedGenresAction;
use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Catalog\GetFeaturedTitlesAction;
use App\Actions\Home\GetAwardsSpotlightNominationsAction;
use App\Actions\Home\GetHeroSpotlightAction;
use App\Actions\Home\GetLatestTrailerTitlesAction;
use App\Actions\Home\GetPopularPeopleAction;
use App\Models\Credit;
use App\Models\Genre;
use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\Person;
use App\Models\Title;
use App\Models\TitleImage;
use App\Models\TitleStatistic;
use App\Models\TitleVideo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_home_page_renders_the_local_catalog_surface_without_remote_queries(): void
    {
        $this->mockHomePageDependencies($this->makeHeroTitle());

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Start')
            ->assertSee('Browse')
            ->assertSee('Tools')
            ->assertSee('Titles')
            ->assertSee('Trailers')
            ->assertSee('Catalog Spotlight')
            ->assertSee('Theme lanes')
            ->assertSee('Awards Spotlight')
            ->assertSee('Latest Trailers')
            ->assertSee('Trending titles')
            ->assertSee('Popular people');
    }

    public function test_home_page_renders_the_local_spotlight_details_when_a_hero_title_is_available(): void
    {
        $this->mockHomePageDependencies($this->makeHeroTitle());

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('The Matrix')
            ->assertSee('Science Fiction')
            ->assertSee('Keanu Reeves')
            ->assertSee('View title page')
            ->assertSee('Watch trailer')
            ->assertSeeHtml('sb-home-hero-media')
            ->assertSeeHtml('sb-home-hero-media-asset');
    }

    public function test_home_page_renders_the_empty_spotlight_fallback_when_no_hero_title_is_available(): void
    {
        $this->mockHomePageDependencies();

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Imported catalog, adapted to the current app.')
            ->assertSee('Start discovering')
            ->assertSee('Search the catalog')
            ->assertSeeHtml('sb-home-hero-media')
            ->assertSeeHtml('sb-home-hero-media-placeholder');
    }

    private function mockHomePageDependencies(?Title $heroSpotlight = null): void
    {
        $buildPublicTitleIndexQuery = Mockery::mock(BuildPublicTitleIndexQueryAction::class);
        $buildPublicTitleIndexQuery
            ->shouldReceive('handle')
            ->times(3)
            ->andReturnUsing(fn (): Builder => Title::query()
                ->selectCatalogCardColumns()
                ->publishedCatalog()
                ->whereKey(-1));

        $getHeroSpotlight = Mockery::mock(GetHeroSpotlightAction::class);
        $getHeroSpotlight
            ->shouldReceive('handle')
            ->once()
            ->andReturn($heroSpotlight);

        $getLatestTrailerTitles = Mockery::mock(GetLatestTrailerTitlesAction::class);
        $getLatestTrailerTitles
            ->shouldReceive('handle')
            ->once()
            ->with(4)
            ->andReturn(new EloquentCollection);

        $getAwardsSpotlightNominations = Mockery::mock(GetAwardsSpotlightNominationsAction::class);
        $getAwardsSpotlightNominations
            ->shouldReceive('handle')
            ->once()
            ->with(4)
            ->andReturn(collect());

        $getFeaturedTitles = Mockery::mock(GetFeaturedTitlesAction::class);
        $getFeaturedTitles
            ->shouldReceive('handle')
            ->once()
            ->with(6)
            ->andReturn(new EloquentCollection);

        $getFeaturedGenres = Mockery::mock(GetFeaturedGenresAction::class);
        $getFeaturedGenres
            ->shouldReceive('handle')
            ->once()
            ->with(8)
            ->andReturn(new EloquentCollection);

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories
            ->shouldReceive('handle')
            ->once()
            ->with(4)
            ->andReturn(new EloquentCollection);

        $getPopularPeople = Mockery::mock(GetPopularPeopleAction::class);
        $getPopularPeople
            ->shouldReceive('handle')
            ->once()
            ->with(6)
            ->andReturn(new EloquentCollection);

        $this->app->instance(BuildPublicTitleIndexQueryAction::class, $buildPublicTitleIndexQuery);
        $this->app->instance(GetHeroSpotlightAction::class, $getHeroSpotlight);
        $this->app->instance(GetLatestTrailerTitlesAction::class, $getLatestTrailerTitles);
        $this->app->instance(GetAwardsSpotlightNominationsAction::class, $getAwardsSpotlightNominations);
        $this->app->instance(GetFeaturedTitlesAction::class, $getFeaturedTitles);
        $this->app->instance(GetFeaturedGenresAction::class, $getFeaturedGenres);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);
        $this->app->instance(GetPopularPeopleAction::class, $getPopularPeople);
    }

    private function makeHeroTitle(): Title
    {
        $title = new Title;
        $title->forceFill([
            'id' => 1,
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
            'endyear' => null,
            'runtimeminutes' => 136,
            'title_type_id' => null,
            'runtimeSeconds' => 8160,
        ]);
        $title->exists = true;

        $title->setRelation('primaryImageRecord', $this->makeMoviePrimaryImage([
            'movie_id' => 1,
            'url' => 'https://images.test/matrix-poster.jpg',
            'width' => 1000,
            'height' => 1500,
            'type' => 'poster',
        ]));

        $title->setRelation('titleImages', new EloquentCollection([
            $this->makeTitleImage([
                'movie_id' => 1,
                'position' => 1,
                'url' => 'https://images.test/matrix-backdrop.jpg',
                'width' => 1920,
                'height' => 1080,
                'type' => 'backdrop',
            ]),
        ]));

        $title->setRelation('plotRecord', $this->makeMoviePlot([
            'movie_id' => 1,
            'plot' => 'A hacker learns the world is a simulation and joins the resistance.',
        ]));

        $title->setRelation('statistic', $this->makeTitleStatistic([
            'movie_id' => 1,
            'aggregate_rating' => 8.7,
            'vote_count' => 2100000,
        ]));

        $title->setRelation('genres', new EloquentCollection([
            $this->makeGenre([
                'id' => 1,
                'name' => 'Science Fiction',
            ]),
        ]));

        $person = $this->makePerson([
            'id' => 1,
            'nconst' => 'nm0000206',
            'imdb_id' => 'nm0000206',
            'displayName' => 'Keanu Reeves',
            'primaryname' => 'Keanu Reeves',
        ]);

        $credit = new Credit;
        $credit->forceFill([
            'id' => 1,
            'movie_id' => 1,
            'name_basic_id' => 1,
            'category' => 'actor',
            'episode_count' => null,
            'position' => 1,
        ]);
        $credit->exists = true;
        $credit->setRelation('person', $person);

        $title->setRelation('credits', new EloquentCollection([$credit]));

        $video = new TitleVideo;
        $video->forceFill([
            'imdb_id' => 'vi1032782617',
            'movie_id' => 1,
            'video_type_id' => 1,
            'name' => 'Official Trailer',
            'description' => 'Official trailer',
            'width' => 1920,
            'height' => 1080,
            'runtime_seconds' => 150,
            'position' => 1,
        ]);
        $video->exists = true;

        $title->setRelation('titleVideos', new EloquentCollection([$video]));

        return $title;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeMoviePrimaryImage(array $attributes): MoviePrimaryImage
    {
        $model = new MoviePrimaryImage;
        $model->forceFill($attributes);
        $model->exists = true;

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeMoviePlot(array $attributes): MoviePlot
    {
        $model = new MoviePlot;
        $model->forceFill($attributes);
        $model->exists = true;

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTitleStatistic(array $attributes): TitleStatistic
    {
        $model = new TitleStatistic;
        $model->forceFill($attributes);
        $model->exists = true;

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTitleImage(array $attributes): TitleImage
    {
        $model = new TitleImage;
        $model->forceFill($attributes);
        $model->exists = true;

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeGenre(array $attributes): Genre
    {
        $model = new Genre;
        $attributes['slug'] ??= Str::slug((string) ($attributes['name'] ?? 'genre'));
        $model->forceFill($attributes);
        $model->exists = true;

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePerson(array $attributes): Person
    {
        $model = new Person;
        $model->forceFill($attributes);
        $model->exists = true;

        return $model;
    }
}
