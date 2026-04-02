<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\AwardNomination;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleAwardNominationsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_award_nominations_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-award-nominations-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt7654321' => Http::response([
                    'id' => 'tt7654321',
                    'type' => 'movie',
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
                    'seasons' => [],
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
                    'stats' => [
                        'nominationCount' => 2,
                        'winCount' => 1,
                    ],
                    'awardNominations' => [
                        [
                            'event' => [
                                'id' => 'ev123',
                                'name' => 'Nebula Screen Awards',
                            ],
                            'year' => 2022,
                            'category' => 'Best Performance',
                            'text' => 'Nebula Prize',
                            'isWinner' => true,
                            'winnerRank' => 1,
                            'nominees' => [
                                [
                                    'id' => 'nm1000001',
                                    'displayName' => 'Ava Stone',
                                    'primaryProfessions' => ['actor'],
                                ],
                            ],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'award-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/awardNominations?pageToken=award-page-2' => Http::response([
                    'stats' => [
                        'nominationCount' => 2,
                        'winCount' => 1,
                    ],
                    'awardNominations' => [
                        [
                            'event' => [
                                'id' => 'ev123',
                                'name' => 'Nebula Screen Awards',
                            ],
                            'year' => 2022,
                            'category' => 'Best Ensemble',
                            'text' => 'Shared honor',
                            'isWinner' => false,
                            'winnerRank' => 2,
                            'nominees' => [
                                [
                                    'id' => 'nm1000002',
                                    'displayName' => 'Noah Flint',
                                    'primaryProfessions' => ['director'],
                                ],
                            ],
                        ],
                    ],
                    'totalCount' => 2,
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
                'https://api.imdbapi.dev/names/nm1000001' => Http::response([
                    'id' => 'nm1000001',
                    'displayName' => 'Ava Stone',
                    'primaryProfessions' => ['actor'],
                    'biography' => 'Ava Stone is an actor from Seattle.',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/relationships' => Http::response([
                    'relationships' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/trivia' => Http::response([
                    'triviaEntries' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000002' => Http::response([
                    'id' => 'nm1000002',
                    'displayName' => 'Noah Flint',
                    'primaryProfessions' => ['director'],
                    'biography' => 'Noah Flint directs speculative dramas.',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000002/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000002/relationships' => Http::response([
                    'relationships' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000002/trivia' => Http::response([
                    'triviaEntries' => [],
                ], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->downloadImdbTitlePayload('tt7654321', $directory);

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt7654321.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'awardNominations.awardNominations', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['awardNominations']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'award-nominations.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();
        $statistic = TitleStatistic::query()->where('title_id', $title->id)->firstOrFail();

        $this->assertCount(2, data_get($title->imdb_payload, 'awardNominations.awardNominations', []));
        $this->assertSame('Best Ensemble', data_get($title->imdb_payload, 'awardNominations.awardNominations.1.category'));
        $this->assertSame('2', (string) data_get($title->imdb_payload, 'awardNominations.awardNominations.1.winnerRank'));
        $this->assertSame('1', (string) data_get($title->imdb_payload, 'awardNominations.stats.winCount'));
        $this->assertSame(2, AwardNomination::query()->where('title_id', $title->id)->count());
        $this->assertSame(2, $statistic->awards_nominated_count);
        $this->assertSame(1, $statistic->awards_won_count);
    }
}
