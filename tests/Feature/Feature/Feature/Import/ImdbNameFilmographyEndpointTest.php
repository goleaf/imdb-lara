<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbNamePayloadAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Models\Credit;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbNameFilmographyEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_name_filmography_is_downloaded_preserved_and_linked_to_existing_titles(): void
    {
        $directory = storage_path('framework/testing/imdb-name-filmography-endpoint');
        File::deleteDirectory($directory);

        Title::factory()->movie()->create([
            'imdb_id' => 'tt2000001',
            'name' => 'Signal Run',
            'original_name' => 'Signal Run',
        ]);
        Title::factory()->movie()->create([
            'imdb_id' => 'tt2000002',
            'name' => 'Harbor Shift',
            'original_name' => 'Harbor Shift',
        ]);

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
                    'primaryProfessions' => ['actor', 'producer'],
                    'biography' => 'Ava Stone is an actor and producer.',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response([
                    'credits' => [
                        [
                            'title' => [
                                'id' => 'tt2000001',
                                'primaryTitle' => 'Signal Run',
                            ],
                            'category' => 'actor',
                            'characters' => ['Mara Vale'],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'filmography-page-2',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography?pageToken=filmography-page-2' => Http::response([
                    'credits' => [
                        [
                            'title' => [
                                'id' => 'tt2000002',
                                'primaryTitle' => 'Harbor Shift',
                            ],
                            'category' => 'producer',
                        ],
                    ],
                    'totalCount' => 2,
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/relationships' => Http::response([
                    'relationships' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/trivia' => Http::response([
                    'triviaEntries' => [],
                ], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $download = app(DownloadImdbNamePayloadAction::class)->handle('nm1000001', $directory, true);

        $this->assertTrue($download['downloaded']);
        $this->assertContains('https://api.imdbapi.dev/names/nm1000001/filmography?pageToken=filmography-page-2', $requestedUrls);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'filmography.json');

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'filmography.credits', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['filmography']);
        $this->assertSame('Mara Vale', data_get($bundle, 'filmography.credits.0.characters.0'));
        $this->assertSame('producer', data_get($bundle, 'filmography.credits.1.category'));

        $person = app(ImportImdbNamePayloadAction::class)->handle($download['payload'], $download['storage_path']);

        $person->refresh();

        $this->assertCount(2, data_get($person->imdb_payload, 'filmography.credits', []));
        $this->assertSame('tt2000001', data_get($person->imdb_payload, 'filmography.credits.0.title.id'));
        $this->assertSame('producer', data_get($person->imdb_payload, 'filmography.credits.1.category'));

        $credits = Credit::query()
            ->where('person_id', $person->id)
            ->where('imdb_source_group', 'imdb:filmography')
            ->with('title')
            ->orderBy('billing_order')
            ->get();

        $this->assertCount(2, $credits);
        $this->assertSame('tt2000001', $credits[0]->title?->imdb_id);
        $this->assertSame('Actor', $credits[0]->job);
        $this->assertSame('Cast', $credits[0]->department);
        $this->assertSame('Mara Vale', $credits[0]->character_name);
        $this->assertSame('tt2000002', $credits[1]->title?->imdb_id);
        $this->assertSame('Producer', $credits[1]->job);
        $this->assertSame('Production', $credits[1]->department);

        $filmographyImportReport = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'filmography.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertTrue($filmographyImportReport['has_payload']);
        $this->assertContains('Filmography', data_get($filmographyImportReport, 'added_relations.payload_sections', []));
        $this->assertNotEmpty(data_get($filmographyImportReport, 'added_relations.credits', []));
    }
}
