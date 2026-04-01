<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbInterestPayloadAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbInterestEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_interest_endpoint_is_downloaded_and_saved_with_full_raw_payload(): void
    {
        $directory = storage_path('framework/testing/imdb-interest-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/interests/in0000159' => Http::response([
                'id' => 'in0000159',
                'name' => 'Cyberpunk',
                'description' => 'Neon futures and speculative noir.',
                'isSubgenre' => true,
                'primaryImage' => [
                    'url' => 'https://example.com/cyberpunk.jpg',
                    'width' => 1600,
                    'height' => 900,
                ],
                'similarInterests' => [
                    [
                        'id' => 'in0000160',
                        'name' => 'Tech Noir',
                        'description' => 'Rain-soaked conspiracy thrillers.',
                    ],
                ],
            ], 200),
        ]);

        $download = app(DownloadImdbInterestPayloadAction::class)->handle('in0000159', $directory, true);

        $this->assertTrue($download['downloaded']);
        $this->assertSame('in0000159', $download['imdb_id']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'in0000159.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'interest.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'manifest.json');

        $bundle = json_decode(
            (string) File::get($directory.DIRECTORY_SEPARATOR.'in0000159.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $artifactPayload = json_decode(
            (string) File::get($directory.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'interest.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('in0000159', data_get($bundle, 'imdbId'));
        $this->assertSame('Cyberpunk', data_get($bundle, 'interest.name'));
        $this->assertSame('Neon futures and speculative noir.', data_get($bundle, 'interest.description'));
        $this->assertTrue((bool) data_get($bundle, 'interest.isSubgenre'));
        $this->assertSame('https://example.com/cyberpunk.jpg', data_get($bundle, 'interest.primaryImage.url'));
        $this->assertSame('in0000160', data_get($bundle, 'interest.similarInterests.0.id'));
        $this->assertSame('Tech Noir', data_get($artifactPayload, 'similarInterests.0.name'));

        $manifest = json_decode(
            (string) File::get($directory.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'manifest.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('interest.json', data_get($manifest, 'artifacts.interest.path'));
        $this->assertTrue((bool) data_get($manifest, 'artifacts.interest.has_payload'));
    }
}
