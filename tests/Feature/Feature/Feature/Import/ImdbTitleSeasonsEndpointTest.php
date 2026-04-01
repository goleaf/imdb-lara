<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Season;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleSeasonsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_seasons_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-seasons-endpoint');
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
                        ['season' => '1', 'episodeCount' => 8],
                        ['season' => '2', 'episodeCount' => 10],
                    ],
                    'totalCount' => 3,
                    'nextPageToken' => 'season-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/seasons?pageToken=season-page-2' => Http::response([
                    'seasons' => [
                        ['season' => '3', 'episodeCount' => 6],
                    ],
                    'totalCount' => 3,
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/episodes' => Http::response([
                    'episodes' => [],
                    'totalCount' => 0,
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

        $this->assertCount(3, data_get($bundle, 'seasons.seasons', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['seasons']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'seasons.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();
        $statistic = TitleStatistic::query()->where('title_id', $title->id)->firstOrFail();

        $this->assertCount(3, data_get($title->imdb_payload, 'seasons.seasons', []));
        $this->assertSame('10', (string) data_get($title->imdb_payload, 'seasons.seasons.1.episodeCount'));
        $this->assertSame(3, Season::query()->where('series_id', $title->id)->count());
        $this->assertTrue(Season::query()->where('series_id', $title->id)->where('season_number', 3)->exists());
        $this->assertSame(24, $statistic->episodes_count);
    }
}
