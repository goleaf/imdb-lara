<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbNamePayloadAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Models\MediaAsset;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbNameDetailsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_details_endpoint_data_is_downloaded_and_persisted_without_losing_non_normalized_fields(): void
    {
        $directory = storage_path('framework/testing/imdb-name-details-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/names/nm1000001' => Http::response([
                'id' => 'nm1000001',
                'displayName' => 'Ava Stone',
                'alternativeNames' => ['A. Stone'],
                'primaryProfessions' => ['actor'],
                'biography' => 'Ava Stone is an actor from Seattle.',
                'heightCm' => 175,
                'birthName' => 'Ava Marie Stone',
                'birthDate' => ['year' => 1988, 'month' => 3, 'day' => 2],
                'birthLocation' => 'Seattle, Washington, USA',
                'deathReason' => 'N/A',
                'meterRanking' => [
                    'currentRank' => 14,
                    'changeDirection' => 'UP',
                    'difference' => 3,
                ],
                'primaryImage' => [
                    'url' => 'https://example.com/ava-stone.jpg',
                    'width' => 1200,
                    'height' => 1800,
                    'caption' => ['plainText' => 'Portrait'],
                ],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/images*' => Http::response([
                'images' => [],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/filmography*' => Http::response([
                'credits' => [],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/relationships*' => Http::response([
                'relationships' => [],
            ], 200),
            'api.imdbapi.dev/names/nm1000001/trivia*' => Http::response([
                'triviaEntries' => [],
            ], 200),
        ]);

        $download = app(DownloadImdbNamePayloadAction::class)->handle('nm1000001', $directory, true);

        $this->assertTrue($download['downloaded']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'details.json');

        $person = app(ImportImdbNamePayloadAction::class)->handle($download['payload'], $download['storage_path']);

        $person->refresh();

        $this->assertSame('Ava Stone', $person->name);
        $this->assertSame(['A. Stone', 'Ava Marie Stone'], $person->imdb_alternative_names);
        $this->assertSame('Cast', $person->known_for_department);
        $this->assertSame('1988-03-02', $person->birth_date?->toDateString());
        $this->assertSame('Seattle, Washington, USA', $person->birth_place);
        $this->assertSame(14, $person->popularity_rank);
        $this->assertSame('175', (string) data_get($person->imdb_payload, 'details.heightCm'));
        $this->assertSame('Ava Marie Stone', data_get($person->imdb_payload, 'details.birthName'));
        $this->assertSame('N/A', data_get($person->imdb_payload, 'details.deathReason'));
        $this->assertSame('UP', data_get($person->imdb_payload, 'details.meterRanking.changeDirection'));
        $this->assertSame('3', (string) data_get($person->imdb_payload, 'details.meterRanking.difference'));
        $this->assertTrue(
            MediaAsset::query()
                ->where('mediable_type', Person::class)
                ->where('mediable_id', $person->id)
                ->where('provider', 'imdb')
                ->get()
                ->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'image.caption.plainText') === 'Portrait')
        );
    }
}
