<?php

namespace Tests\Feature\Feature\Feature\Import;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbImportTitlesFrontierCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    public function test_command_imports_the_paginated_titles_frontier_and_recursively_processes_connected_nodes(): void
    {
        $directory = storage_path('framework/testing/imdb-frontier-import');
        File::deleteDirectory($directory);
        $requestedUrls = [];

        config()->set('services.imdb.storage_root', $directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$requestedUrls) {
            $url = $request->url();
            $requestedUrls[] = $url;

            return match ($url) {
                'https://api.imdbapi.dev/titles' => Http::response([
                    'titles' => [
                        ['id' => 'tt3000001', 'primaryTitle' => 'Frontier One'],
                    ],
                    'nextPageToken' => 'page-2',
                ], 200),
                'https://api.imdbapi.dev/titles?pageToken=page-2' => Http::response([
                    'titles' => [
                        ['id' => 'tt3000002', 'primaryTitle' => 'Frontier Two'],
                    ],
                ], 200),
                'https://api.imdbapi.dev/interests' => Http::response([
                    'categories' => [],
                ], 200),
                'https://api.imdbapi.dev/chart/starmeter' => Http::response([
                    'names' => [],
                ], 200),

                'https://api.imdbapi.dev/titles/tt3000001' => Http::response($this->titlePayload('tt3000001', 'Frontier One', 'nm3000001'), 200),
                'https://api.imdbapi.dev/titles/tt3000001/credits' => Http::response($this->creditsPayload('nm3000001', 'Frontier Lead', 'Pilot Lead'), 200),
                'https://api.imdbapi.dev/titles/tt3000001/releaseDates' => Http::response($this->releaseDatesPayload(2024, 4, 1), 200),
                'https://api.imdbapi.dev/titles/tt3000001/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt3000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/titles/tt3000002' => Http::response($this->titlePayload('tt3000002', 'Frontier Two', 'nm3000002'), 200),
                'https://api.imdbapi.dev/titles/tt3000002/credits' => Http::response($this->creditsPayload('nm3000002', 'Relay Star', 'Relay'), 200),
                'https://api.imdbapi.dev/titles/tt3000002/releaseDates' => Http::response($this->releaseDatesPayload(2024, 4, 8), 200),
                'https://api.imdbapi.dev/titles/tt3000002/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt3000002/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/titles/tt3000003' => Http::response($this->titlePayload('tt3000003', 'Filmography Discovery', 'nm3000001'), 200),
                'https://api.imdbapi.dev/titles/tt3000003/credits' => Http::response($this->creditsPayload('nm3000001', 'Frontier Lead', 'Return'), 200),
                'https://api.imdbapi.dev/titles/tt3000003/releaseDates' => Http::response($this->releaseDatesPayload(2024, 4, 15), 200),
                'https://api.imdbapi.dev/titles/tt3000003/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt3000003/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000003/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/names/nm3000001' => Http::response($this->nameDetailsPayload('nm3000001', 'Frontier Lead'), 200),
                'https://api.imdbapi.dev/names/nm3000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/names/nm3000001/relationships' => Http::response(['relationships' => []], 200),
                'https://api.imdbapi.dev/names/nm3000001/trivia' => Http::response(['triviaEntries' => []], 200),
                'https://api.imdbapi.dev/names/nm3000001/filmography' => Http::response([
                    'credits' => [
                        [
                            'title' => [
                                'id' => 'tt3000001',
                                'primaryTitle' => 'Frontier One',
                            ],
                            'category' => 'actor',
                        ],
                        [
                            'title' => [
                                'id' => 'tt3000003',
                                'primaryTitle' => 'Filmography Discovery',
                            ],
                            'category' => 'actor',
                        ],
                    ],
                    'totalCount' => 2,
                ], 200),

                'https://api.imdbapi.dev/names/nm3000002' => Http::response($this->nameDetailsPayload('nm3000002', 'Relay Star'), 200),
                'https://api.imdbapi.dev/names/nm3000002/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/names/nm3000002/relationships' => Http::response(['relationships' => []], 200),
                'https://api.imdbapi.dev/names/nm3000002/trivia' => Http::response(['triviaEntries' => []], 200),
                'https://api.imdbapi.dev/names/nm3000002/filmography' => Http::response([
                    'credits' => [
                        [
                            'title' => [
                                'id' => 'tt3000002',
                                'primaryTitle' => 'Frontier Two',
                            ],
                            'category' => 'actor',
                        ],
                    ],
                    'totalCount' => 1,
                ], 200),

                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->artisan('imdb:import-titles-frontier')
            ->expectsOutputToContain('Starting Title tt3000001 from frontier:titles')
            ->expectsOutputToContain('Endpoint digest:')
            ->expectsOutputToContain('Verification: passed')
            ->expectsOutputToContain('Import stabilized after pass 2.')
            ->assertSuccessful();

        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt3000001']);
        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt3000002']);
        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt3000003']);
        $this->assertDatabaseHas('people', ['imdb_id' => 'nm3000001']);

        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'page-0001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'page-0002.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt3000001'.DIRECTORY_SEPARATOR.'import-report.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt3000002'.DIRECTORY_SEPARATOR.'import-report.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt3000003'.DIRECTORY_SEPARATOR.'import-report.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt3000001'.DIRECTORY_SEPARATOR.'verification.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm3000001'.DIRECTORY_SEPARATOR.'verification.json');
        $this->assertNotEmpty(File::glob($directory.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'crawl-*.json'));

        $pageTwoRequestIndex = array_search('https://api.imdbapi.dev/titles?pageToken=page-2', $requestedUrls, true);
        $firstTitleRequestIndex = array_search('https://api.imdbapi.dev/titles/tt3000001', $requestedUrls, true);

        $this->assertIsInt($pageTwoRequestIndex);
        $this->assertIsInt($firstTitleRequestIndex);
        $this->assertGreaterThan($firstTitleRequestIndex, $pageTwoRequestIndex);

        $runReports = collect(File::glob($directory.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'crawl-*.json'))
            ->map(function (string $path): array {
                return json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
            });

        $this->assertTrue($runReports->contains(fn (array $report): bool => (int) data_get($report, 'summary.db_counts.titles', 0) >= 1));
        $this->assertTrue($runReports->contains(fn (array $report): bool => (int) data_get($report, 'summary.db_counts.credits', 0) >= 1));
        $this->assertTrue($runReports->contains(fn (array $report): bool => (int) data_get($report, 'summary.db_counts.media_assets', 0) >= 1));
    }

    public function test_command_stops_titles_frontier_when_next_page_token_repeats(): void
    {
        $directory = storage_path('framework/testing/imdb-frontier-repeated-token');
        File::deleteDirectory($directory);

        config()->set('services.imdb.storage_root', $directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles' => Http::response([
                    'titles' => [
                        ['id' => 'tt3000001', 'primaryTitle' => 'Frontier One'],
                    ],
                    'nextPageToken' => 'repeat-token',
                ], 200),
                'https://api.imdbapi.dev/titles?pageToken=repeat-token' => Http::response([
                    'titles' => [
                        ['id' => 'tt3000002', 'primaryTitle' => 'Frontier Two'],
                    ],
                    'nextPageToken' => 'repeat-token',
                ], 200),
                'https://api.imdbapi.dev/interests' => Http::response([
                    'categories' => [],
                ], 200),
                'https://api.imdbapi.dev/chart/starmeter' => Http::response([
                    'names' => [],
                ], 200),

                'https://api.imdbapi.dev/titles/tt3000001' => Http::response($this->titlePayload('tt3000001', 'Frontier One', 'nm3000001'), 200),
                'https://api.imdbapi.dev/titles/tt3000001/credits' => Http::response($this->creditsPayload('nm3000001', 'Frontier Lead', 'Pilot Lead'), 200),
                'https://api.imdbapi.dev/titles/tt3000001/releaseDates' => Http::response($this->releaseDatesPayload(2024, 4, 1), 200),
                'https://api.imdbapi.dev/titles/tt3000001/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt3000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000001/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/titles/tt3000002' => Http::response($this->titlePayload('tt3000002', 'Frontier Two', 'nm3000002'), 200),
                'https://api.imdbapi.dev/titles/tt3000002/credits' => Http::response($this->creditsPayload('nm3000002', 'Relay Star', 'Relay'), 200),
                'https://api.imdbapi.dev/titles/tt3000002/releaseDates' => Http::response($this->releaseDatesPayload(2024, 4, 8), 200),
                'https://api.imdbapi.dev/titles/tt3000002/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt3000002/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt3000002/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/names/nm3000001' => Http::response($this->nameDetailsPayload('nm3000001', 'Frontier Lead'), 200),
                'https://api.imdbapi.dev/names/nm3000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/names/nm3000001/relationships' => Http::response(['relationships' => []], 200),
                'https://api.imdbapi.dev/names/nm3000001/trivia' => Http::response(['triviaEntries' => []], 200),
                'https://api.imdbapi.dev/names/nm3000001/filmography' => Http::response(['credits' => [], 'totalCount' => 0], 200),

                'https://api.imdbapi.dev/names/nm3000002' => Http::response($this->nameDetailsPayload('nm3000002', 'Relay Star'), 200),
                'https://api.imdbapi.dev/names/nm3000002/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/names/nm3000002/relationships' => Http::response(['relationships' => []], 200),
                'https://api.imdbapi.dev/names/nm3000002/trivia' => Http::response(['triviaEntries' => []], 200),
                'https://api.imdbapi.dev/names/nm3000002/filmography' => Http::response(['credits' => [], 'totalCount' => 0], 200),

                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->artisan('imdb:import-titles-frontier')->assertSuccessful();

        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt3000001']);
        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt3000002']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'page-0001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'page-0002.json');
        $this->assertFileDoesNotExist($directory.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'page-0003.json');
    }

    /**
     * @return array<string, mixed>
     */
    private function titlePayload(string $imdbId, string $title, string $starId): array
    {
        return [
            'id' => $imdbId,
            'type' => 'movie',
            'primaryTitle' => $title,
            'primaryImage' => [
                'url' => 'https://example.com/'.$imdbId.'.jpg',
                'width' => 1600,
                'height' => 2400,
            ],
            'startYear' => 2024,
            'runtimeSeconds' => 7200,
            'genres' => ['Drama'],
            'plot' => 'A title imported from the frontier.',
            'stars' => [
                [
                    'id' => $starId,
                    'displayName' => $starId === 'nm3000001' ? 'Frontier Lead' : 'Relay Star',
                    'primaryProfessions' => ['actor'],
                ],
            ],
            'rating' => [
                'aggregateRating' => 7.8,
                'voteCount' => 1800,
            ],
            'originCountries' => [
                ['code' => 'US', 'name' => 'United States'],
            ],
            'spokenLanguages' => [
                ['code' => 'eng', 'name' => 'English'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditsPayload(string $personId, string $displayName, string $characterName): array
    {
        return [
            'credits' => [
                [
                    'name' => [
                        'id' => $personId,
                        'displayName' => $displayName,
                        'primaryProfessions' => ['actor'],
                    ],
                    'category' => 'actor',
                    'characters' => [$characterName],
                ],
            ],
            'totalCount' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseDatesPayload(int $year, int $month, int $day): array
    {
        return [
            'releaseDates' => [
                [
                    'country' => ['code' => 'US', 'name' => 'United States'],
                    'releaseDate' => ['year' => $year, 'month' => $month, 'day' => $day],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nameDetailsPayload(string $imdbId, string $name): array
    {
        return [
            'id' => $imdbId,
            'displayName' => $name,
            'primaryProfessions' => ['actor'],
            'biography' => $name.' crosses multiple imported titles.',
            'birthDate' => ['year' => 1990, 'month' => 5, 'day' => 2],
            'birthLocation' => 'Seattle, Washington, USA',
            'primaryImage' => [
                'url' => 'https://example.com/'.$imdbId.'.jpg',
                'width' => 1000,
                'height' => 1500,
            ],
        ];
    }
}
