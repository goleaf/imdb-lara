<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbNamePayloadAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Models\MediaAsset;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbNameImagesEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_download_continues_when_optional_relationships_endpoint_returns_server_error(): void
    {
        $directory = storage_path('framework/testing/imdb-name-images-optional-relationships-error');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/names/nm1000001' => Http::response([
                    'id' => 'nm1000001',
                    'displayName' => 'Ava Stone',
                    'primaryProfessions' => ['actor'],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response([
                    'credits' => [],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/relationships' => Http::response([
                    'code' => 13,
                    'message' => 'upstream parser failed',
                ], 500),
                'https://api.imdbapi.dev/names/nm1000001/trivia' => Http::response([
                    'triviaEntries' => [],
                ], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $download = app(DownloadImdbNamePayloadAction::class)->handle('nm1000001', $directory, true);

        $this->assertTrue($download['downloaded']);
        $this->assertNull(data_get($download['payload'], 'relationships'));
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'relationships.json');

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNull(data_get($bundle, 'relationships'));
    }

    public function test_repeated_name_images_page_token_stops_pagination_without_failing_the_download(): void
    {
        $directory = storage_path('framework/testing/imdb-name-images-repeated-token');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/names/nm1000001' => Http::response([
                    'id' => 'nm1000001',
                    'displayName' => 'Ava Stone',
                    'alternativeNames' => ['A. Stone'],
                    'primaryProfessions' => ['actor'],
                    'biography' => 'Ava Stone is an actor from Seattle.',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response([
                    'images' => [
                        [
                            'url' => 'https://example.com/ava-stone-portrait.jpg',
                            'width' => 1000,
                            'height' => 1500,
                        ],
                    ],
                    'totalCount' => 3,
                    'nextPageToken' => 'repeat-me',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images?pageToken=repeat-me' => Http::response([
                    'images' => [
                        [
                            'url' => 'https://example.com/ava-stone-red-carpet.jpg',
                            'width' => 1600,
                            'height' => 900,
                        ],
                    ],
                    'totalCount' => 3,
                    'nextPageToken' => 'repeat-me',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response([
                    'credits' => [],
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

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'images.images', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['images']);
        $this->assertSame('https://example.com/ava-stone-portrait.jpg', data_get($bundle, 'images.images.0.url'));
        $this->assertSame('https://example.com/ava-stone-red-carpet.jpg', data_get($bundle, 'images.images.1.url'));
    }

    public function test_paginated_name_images_are_downloaded_and_preserved_in_person_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-name-images-endpoint');
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
                    'primaryImage' => [
                        'url' => 'https://example.com/ava-stone-primary.jpg',
                        'width' => 1200,
                        'height' => 1800,
                    ],
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response([
                    'images' => [
                        [
                            'url' => 'https://example.com/ava-stone-portrait.jpg',
                            'width' => 1000,
                            'height' => 1500,
                            'type' => 'portrait',
                            'caption' => [
                                'plainText' => 'Portrait session',
                            ],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'name-image-page-2',
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/images?pageToken=name-image-page-2' => Http::response([
                    'images' => [
                        [
                            'url' => 'https://example.com/ava-stone-red-carpet.jpg',
                            'width' => 1600,
                            'height' => 900,
                            'type' => 'event',
                            'copyright' => '(c) Awards Wire',
                        ],
                    ],
                    'totalCount' => 2,
                ], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response([
                    'credits' => [],
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
        $this->assertContains('https://api.imdbapi.dev/names/nm1000001/images?pageToken=name-image-page-2', $requestedUrls);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'images.json');

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($bundle, 'images.images', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['images']);
        $this->assertSame('Portrait session', data_get($bundle, 'images.images.0.caption.plainText'));
        $this->assertSame('(c) Awards Wire', data_get($bundle, 'images.images.1.copyright'));

        $person = app(ImportImdbNamePayloadAction::class)->handle($download['payload'], $download['storage_path']);

        $person->refresh();

        $this->assertCount(2, data_get($person->imdb_payload, 'images.images', []));
        $this->assertSame('2', (string) data_get($person->imdb_payload, 'images.totalCount'));
        $this->assertSame('portrait', data_get($person->imdb_payload, 'images.images.0.type'));
        $this->assertSame('(c) Awards Wire', data_get($person->imdb_payload, 'images.images.1.copyright'));

        $assets = MediaAsset::query()
            ->where('mediable_type', Person::class)
            ->where('mediable_id', $person->id)
            ->get();

        $this->assertGreaterThanOrEqual(3, $assets->count());
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'image.caption.plainText') === 'Portrait session'));
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'image.copyright') === '(c) Awards Wire'));

        $imagesImportReport = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'images.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertTrue($imagesImportReport['has_payload']);
        $this->assertContains('Images', data_get($imagesImportReport, 'added_relations.payload_sections', []));
        $this->assertNotEmpty(data_get($imagesImportReport, 'added_relations.media_assets', []));
    }
}
