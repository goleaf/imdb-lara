<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbNamePayloadAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbNameRelationshipsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_relationships_are_downloaded_and_preserved_in_person_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-name-relationships-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/names/nm1000001' => Http::response([
                'id' => 'nm1000001',
                'displayName' => 'Ava Stone',
                'alternativeNames' => ['A. Stone'],
                'primaryProfessions' => ['actor'],
                'biography' => 'Ava Stone is an actor from Seattle.',
            ], 200),
            'api.imdbapi.dev/names/nm1000001/images*' => Http::response([
                'images' => [],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/filmography*' => Http::response([
                'credits' => [],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/relationships' => Http::response([
                'relationships' => [
                    [
                        'type' => 'spouse',
                        'name' => [
                            'id' => 'nm1000002',
                            'displayName' => 'Morgan Lake',
                        ],
                        'attributes' => ['married'],
                        'startYear' => 2020,
                    ],
                    [
                        'type' => 'sibling',
                        'name' => [
                            'id' => 'nm1000003',
                            'displayName' => 'Theo Stone',
                        ],
                        'attributes' => ['older brother'],
                    ],
                ],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/trivia*' => Http::response([
                'triviaEntries' => [],
            ], 200),
        ]);

        $download = app(DownloadImdbNamePayloadAction::class)->handle('nm1000001', $directory, true);

        $this->assertTrue($download['downloaded']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'relationships.json');

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'relationships.relationships', []));
        $this->assertSame('spouse', data_get($bundle, 'relationships.relationships.0.type'));
        $this->assertSame('Morgan Lake', data_get($bundle, 'relationships.relationships.0.name.displayName'));
        $this->assertSame('older brother', data_get($bundle, 'relationships.relationships.1.attributes.0'));

        $person = app(ImportImdbNamePayloadAction::class)->handle($download['payload'], $download['storage_path']);

        $person->refresh();

        $this->assertCount(2, data_get($person->imdb_payload, 'relationships.relationships', []));
        $this->assertSame('spouse', data_get($person->imdb_payload, 'relationships.relationships.0.type'));
        $this->assertSame('nm1000002', data_get($person->imdb_payload, 'relationships.relationships.0.name.id'));
        $this->assertSame('Morgan Lake', data_get($person->imdb_payload, 'relationships.relationships.0.name.displayName'));
        $this->assertSame('older brother', data_get($person->imdb_payload, 'relationships.relationships.1.attributes.0'));

        $relationshipsImportReport = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'relationships.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertTrue($relationshipsImportReport['has_payload']);
        $this->assertContains('Relationships', data_get($relationshipsImportReport, 'added_relations.payload_sections', []));
    }
}
