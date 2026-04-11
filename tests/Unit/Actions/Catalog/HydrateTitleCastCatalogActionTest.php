<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\HydrateTitleCastCatalogAction;
use App\Actions\Import\DownloadImdbTitlePayloadAction;
use App\Models\Credit;
use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Profession;
use App\Models\Title;
use App\Models\TitleImage;
use App\Models\TitleStatistic;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class HydrateTitleCastCatalogActionTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_it_hydrates_missing_remote_cast_people_and_hero_fields_from_the_imdb_title_bundle(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
        ]);

        $downloader = Mockery::mock(DownloadImdbTitlePayloadAction::class);
        $downloader
            ->shouldReceive('handle')
            ->once()
            ->andReturn([
                'downloaded' => true,
                'imdb_id' => 'tt0133093',
                'payload_hash' => 'hash',
                'source_url' => 'https://api.imdbapi.dev/titles/tt0133093',
                'storage_path' => storage_path('framework/testing/tt0133093.json'),
                'payload' => [
                    'title' => [
                        'id' => 'tt0133093',
                        'type' => 'movie',
                        'primaryTitle' => 'The Matrix',
                        'originalTitle' => 'The Matrix',
                        'startYear' => 1999,
                        'runtimeSeconds' => 8160,
                        'plot' => 'A hacker discovers reality is a simulation.',
                        'rating' => [
                            'aggregateRating' => 8.7,
                            'voteCount' => 1987654,
                        ],
                        'primaryImage' => [
                            'url' => 'https://example.com/matrix-primary.jpg',
                            'width' => 1500,
                            'height' => 2250,
                            'type' => 'poster',
                        ],
                    ],
                    'credits' => [
                        'credits' => [
                            [
                                'name' => [
                                    'id' => 'nm0000206',
                                    'displayName' => 'Keanu Reeves',
                                    'primaryProfessions' => ['actor'],
                                ],
                                'category' => 'actor',
                                'characters' => ['Neo'],
                            ],
                            [
                                'name' => [
                                    'id' => 'nm0905154',
                                    'displayName' => 'Lana Wachowski',
                                    'primaryProfessions' => ['director', 'writer'],
                                ],
                                'category' => 'director',
                            ],
                        ],
                    ],
                    'images' => [
                        'images' => [
                            [
                                'url' => 'https://example.com/matrix-still.jpg',
                                'width' => 1920,
                                'height' => 1080,
                                'type' => 'still_frame',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(DownloadImdbTitlePayloadAction::class, $downloader);

        app(HydrateTitleCastCatalogAction::class)->handle($title);

        $title->refresh();

        $this->assertSame(8160, $title->runtimeSeconds);
        $this->assertSame(136, $title->runtimeminutes);
        $this->assertSame('A hacker discovers reality is a simulation.', MoviePlot::query()->where('movie_id', $title->id)->value('plot'));
        $this->assertSame('https://example.com/matrix-primary.jpg', MoviePrimaryImage::query()->where('movie_id', $title->id)->value('url'));
        $this->assertSame('https://example.com/matrix-still.jpg', TitleImage::query()->where('movie_id', $title->id)->value('url'));
        $this->assertSame('8.70', (string) TitleStatistic::query()->where('movie_id', $title->id)->value('aggregate_rating'));
        $this->assertSame(1987654, TitleStatistic::query()->where('movie_id', $title->id)->value('vote_count'));

        $keanu = Person::query()->where('nconst', 'nm0000206')->firstOrFail();
        $this->assertSame('Keanu Reeves', $keanu->name);
        $this->assertCount(1, Profession::query()->where('name', 'actor')->get());
        $this->assertSame(1, PersonProfession::query()->where('name_basic_id', $keanu->id)->count());

        $credits = Credit::query()->where('movie_id', $title->id)->orderBy('position')->get();
        $this->assertCount(2, $credits);
        $this->assertSame('actor', $credits[0]->category);
        $this->assertSame('director', $credits[1]->category);
        $this->assertSame('Neo', $credits[0]->nameCreditCharacters()->orderBy('position')->value('character_name'));
    }

    public function test_it_merges_duplicate_remote_credit_rows_by_person_movie_and_category(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt31938062',
            'imdb_id' => 'tt31938062',
            'titletype' => 'movie',
            'primarytitle' => 'Duplicate Credits',
            'originaltitle' => 'Duplicate Credits',
            'isadult' => 0,
            'startyear' => 2026,
        ]);

        $downloader = Mockery::mock(DownloadImdbTitlePayloadAction::class);
        $downloader
            ->shouldReceive('handle')
            ->once()
            ->andReturn([
                'downloaded' => true,
                'imdb_id' => 'tt31938062',
                'payload_hash' => 'hash',
                'source_url' => 'https://api.imdbapi.dev/titles/tt31938062',
                'storage_path' => storage_path('framework/testing/tt31938062.json'),
                'payload' => [
                    'title' => [
                        'id' => 'tt31938062',
                        'type' => 'movie',
                        'primaryTitle' => 'Duplicate Credits',
                        'originalTitle' => 'Duplicate Credits',
                        'startYear' => 2026,
                    ],
                    'credits' => [
                        'credits' => [
                            [
                                'name' => [
                                    'id' => 'nm3142672',
                                    'displayName' => 'Duplicate Actor',
                                    'primaryProfessions' => ['actor'],
                                ],
                                'category' => 'actor',
                                'episodeCount' => 1,
                                'characters' => ['First Alias'],
                            ],
                            [
                                'name' => [
                                    'id' => 'nm3142672',
                                    'displayName' => 'Duplicate Actor',
                                    'primaryProfessions' => ['actor'],
                                ],
                                'category' => 'actor',
                                'episodeCount' => 2,
                                'characters' => ['Second Alias', 'First Alias'],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->app->instance(DownloadImdbTitlePayloadAction::class, $downloader);

        app(HydrateTitleCastCatalogAction::class)->handle($title);

        $credits = Credit::query()->where('movie_id', $title->id)->get();

        $this->assertCount(1, $credits);
        $this->assertSame(2, $credits->first()->episode_count);
        $this->assertSame(1, $credits->first()->position);
        $this->assertSame(
            ['First Alias', 'Second Alias'],
            $credits->first()->nameCreditCharacters()->orderBy('position')->pluck('character_name')->all(),
        );
    }
}
