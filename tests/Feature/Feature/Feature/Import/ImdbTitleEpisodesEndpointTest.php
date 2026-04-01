<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Episode;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleEpisodesEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_episodes_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-episodes-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt7654321' => Http::response([
                    'id' => 'tt7654321',
                    'type' => 'tvSeries',
                    'primaryTitle' => 'Neon Harbor',
                    'genres' => ['Drama'],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/credits' => Http::response([
                    'credits' => [],
                    'totalCount' => 0,
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/releaseDates' => Http::response([
                    'releaseDates' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/akas' => Http::response([
                    'akas' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/seasons' => Http::response([
                    'seasons' => [
                        ['season' => '1', 'episodeCount' => 2],
                    ],
                    'totalCount' => 1,
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/episodes' => Http::response([
                    'episodes' => [
                        [
                            'id' => 'tt7654322',
                            'title' => 'Pilot Light',
                            'primaryImage' => ['url' => 'https://example.com/pilot-light.jpg', 'width' => 1600, 'height' => 900],
                            'season' => '1',
                            'episodeNumber' => 1,
                            'runtimeSeconds' => 3300,
                            'plot' => 'The first night shift uncovers a sabotaged cargo chain.',
                            'rating' => ['aggregateRating' => 8.1, 'voteCount' => 1200],
                            'releaseDate' => ['year' => 2021, 'month' => 5, 'day' => 11],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'episode-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/episodes?pageToken=episode-page-2' => Http::response([
                    'episodes' => [
                        [
                            'id' => 'tt7654323',
                            'title' => 'Harbor Blackout',
                            'primaryImage' => ['url' => 'https://example.com/harbor-blackout.jpg', 'width' => 1600, 'height' => 900],
                            'season' => '1',
                            'episodeNumber' => 2,
                            'runtimeSeconds' => 3420,
                            'plot' => 'Power drops across the docks while the crew hunts a ghost signal.',
                            'rating' => ['aggregateRating' => 8.4, 'voteCount' => 980],
                            'releaseDate' => ['year' => 2021, 'month' => 5, 'day' => 18],
                        ],
                    ],
                    'totalCount' => 2,
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/videos' => Http::response([
                    'videos' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/awardNominations' => Http::response([
                    'awardNominations' => [],
                    'stats' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/parentsGuide' => Http::response([
                    'advisories' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/certificates' => Http::response([
                    'certificates' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/companyCredits' => Http::response([
                    'companyCredits' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/boxOffice' => Http::response([], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->downloadImdbTitlePayload('tt7654321', $directory);

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt7654321.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'episodes.episodes', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['episodes']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'episodes.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $series = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();
        $statistic = TitleStatistic::query()->where('title_id', $series->id)->firstOrFail();

        $this->assertCount(2, data_get($series->imdb_payload, 'episodes.episodes', []));
        $this->assertSame('Harbor Blackout', data_get($series->imdb_payload, 'episodes.episodes.1.title'));
        $this->assertSame('8.4', (string) data_get($series->imdb_payload, 'episodes.episodes.1.rating.aggregateRating'));
        $this->assertTrue(Title::query()->where('imdb_id', 'tt7654322')->exists());
        $this->assertTrue(Title::query()->where('imdb_id', 'tt7654323')->exists());
        $this->assertSame(2, Episode::query()->where('series_id', $series->id)->count());
        $this->assertSame(2, $statistic->episodes_count);
    }
}
