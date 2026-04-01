<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbNamePayloadAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbNameTriviaEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_name_trivia_is_downloaded_and_preserved_in_person_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-name-trivia-endpoint');
        File::deleteDirectory($directory);

        $requestedUrls = [];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$requestedUrls) {
            $url = $request->url();
            $requestedUrls[] = $url;

            return match ($url) {
                'https://api.imdbapi.dev/names/nm1000001' => Http::response([
                    'id' => 'nm1000001',
                    'displayName' => 'Ava Stone',
                    'alternativeNames' => ['A. Stone'],
                    'primaryProfessions' => ['actor'],
                    'biography' => 'Ava Stone is an actor from Seattle.',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response([
                    'credits' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/relationships' => Http::response([
                    'relationships' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/trivia' => Http::response([
                    'triviaEntries' => [
                        [
                            'id' => 'nt-1',
                            'text' => 'Won stage awards.',
                            'interestCount' => 12,
                            'voteCount' => 8,
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'trivia-page-2',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/trivia?pageToken=trivia-page-2' => Http::response([
                    'triviaEntries' => [
                        [
                            'id' => 'nt-2',
                            'text' => 'Speaks four languages.',
                            'interestCount' => 4,
                            'voteCount' => 3,
                        ],
                    ],
                    'totalCount' => 2,
                ], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $download = app(DownloadImdbNamePayloadAction::class)->handle('nm1000001', $directory, true);

        $this->assertTrue($download['downloaded']);
        $this->assertContains('https://api.imdbapi.dev/names/nm1000001/trivia?pageToken=trivia-page-2', $requestedUrls);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'trivia.json');

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'trivia.triviaEntries', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['trivia']);
        $this->assertSame('Won stage awards.', data_get($bundle, 'trivia.triviaEntries.0.text'));
        $this->assertSame('Speaks four languages.', data_get($bundle, 'trivia.triviaEntries.1.text'));

        $person = app(ImportImdbNamePayloadAction::class)->handle($download['payload'], $download['storage_path']);

        $person->refresh();

        $this->assertCount(2, data_get($person->imdb_payload, 'trivia.triviaEntries', []));
        $this->assertSame('2', (string) data_get($person->imdb_payload, 'trivia.totalCount'));
        $this->assertSame('Won stage awards.', data_get($person->imdb_payload, 'trivia.triviaEntries.0.text'));
        $this->assertSame('Speaks four languages.', data_get($person->imdb_payload, 'trivia.triviaEntries.1.text'));

        $triviaImportReport = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'trivia.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertTrue($triviaImportReport['has_payload']);
        $this->assertContains('Trivia', data_get($triviaImportReport, 'added_relations.payload_sections', []));
    }
}
