<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbSearchTitlesAction;
use App\Models\Credit;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbSearchTitlesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_titles_command_saves_raw_search_json_and_imports_found_titles(): void
    {
        $directory = storage_path('framework/testing/imdb-search-titles');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() !== 'https://api.imdbapi.dev/search/titles') {
                $this->fail('Unexpected HTTP request: '.$request->url());
            }

            $this->assertSame([
                'query' => 'matrix',
                'limit' => '2',
            ], $request->data());

            return Http::response([
                'titles' => [
                    [
                        'id' => 'tt0133093',
                        'type' => 'movie',
                        'primaryTitle' => 'The Matrix',
                        'originalTitle' => 'The Matrix',
                        'primaryImage' => [
                            'url' => 'https://example.com/matrix.jpg',
                            'width' => 2100,
                            'height' => 3156,
                        ],
                        'startYear' => 1999,
                        'runtimeSeconds' => 8160,
                        'genres' => ['Action', 'Sci-Fi'],
                        'rating' => [
                            'aggregateRating' => 8.7,
                            'voteCount' => 2237344,
                        ],
                        'plot' => 'A computer hacker learns the world is a simulation.',
                        'directors' => [
                            [
                                'id' => 'nm0905154',
                                'displayName' => 'Lana Wachowski',
                                'primaryProfessions' => ['director'],
                            ],
                        ],
                        'stars' => [
                            [
                                'id' => 'nm0000206',
                                'displayName' => 'Keanu Reeves',
                                'primaryProfessions' => ['actor'],
                            ],
                        ],
                        'originCountries' => [
                            ['code' => 'US', 'name' => 'United States'],
                        ],
                        'spokenLanguages' => [
                            ['code' => 'eng', 'name' => 'English'],
                        ],
                    ],
                    [
                        'id' => 'tt0234215',
                        'type' => 'movie',
                        'primaryTitle' => 'The Matrix Reloaded',
                        'originalTitle' => 'The Matrix Reloaded',
                        'startYear' => 2003,
                        'runtimeSeconds' => 8280,
                        'genres' => ['Action', 'Sci-Fi'],
                        'rating' => [
                            'aggregateRating' => 7.2,
                            'voteCount' => 655000,
                        ],
                        'plot' => 'Neo and the rebels race against the machines.',
                        'writers' => [
                            [
                                'id' => 'nm0905152',
                                'displayName' => 'Lilly Wachowski',
                                'primaryProfessions' => ['writer'],
                            ],
                        ],
                    ],
                ],
            ], 200);
        });

        $result = app(DownloadImdbSearchTitlesAction::class)->handle('matrix', 2, $directory);

        foreach ($result['titles'] as $titleArtifact) {
            $this->importImdbTitlePayloadFromPath(
                $titleArtifact['path'],
                ['fill_missing_only' => true],
                $titleArtifact['payload'],
            );
        }

        $searchDirectories = File::directories($directory);

        $this->assertCount(1, $searchDirectories);

        $searchDirectory = $searchDirectories[0];
        $searchPayload = json_decode(
            (string) File::get($searchDirectory.DIRECTORY_SEPARATOR.'search.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('The Matrix', data_get($searchPayload, 'titles.0.primaryTitle'));
        $this->assertFileExists($searchDirectory.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileExists($searchDirectory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt0133093.json');
        $this->assertFileExists($searchDirectory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt0234215.json');
        $this->assertFileExists($searchDirectory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'verification.json');

        $matrix = Title::query()->where('imdb_id', 'tt0133093')->firstOrFail();
        $reloaded = Title::query()->where('imdb_id', 'tt0234215')->firstOrFail();

        $this->assertSame('The Matrix', $matrix->name);
        $this->assertSame('1999', (string) $matrix->release_year);
        $this->assertSame(['Action', 'Sci-Fi'], $matrix->imdb_genres);
        $this->assertSame('The Matrix', $matrix->original_name);
        $this->assertSame('The Matrix Reloaded', $reloaded->name);
        $this->assertTrue(Credit::query()->where('title_id', $matrix->id)->where('imdb_source_group', 'directors')->exists());
        $this->assertTrue(Credit::query()->where('title_id', $matrix->id)->where('imdb_source_group', 'stars')->exists());
        $this->assertTrue(Credit::query()->where('title_id', $reloaded->id)->where('imdb_source_group', 'writers')->exists());
        $this->assertDatabaseHas('imdb_title_imports', [
            'imdb_id' => 'tt0133093',
        ]);
        $this->assertDatabaseHas('imdb_title_imports', [
            'imdb_id' => 'tt0234215',
        ]);
    }
}
