<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleImagesEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_images_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-images-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt7654321' => Http::response([
                    'id' => 'tt7654321',
                    'type' => 'movie',
                    'primaryTitle' => 'Neon Harbor',
                    'primaryImage' => [
                        'url' => 'https://example.com/neon-harbor-primary.jpg',
                        'width' => 1800,
                        'height' => 2700,
                    ],
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
                    'images' => [
                        [
                            'url' => 'https://example.com/neon-harbor-still.jpg',
                            'width' => 1920,
                            'height' => 1080,
                            'type' => 'still_frame',
                            'caption' => [
                                'plainText' => 'Dockside confrontation.',
                            ],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'image-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/images?pageToken=image-page-2' => Http::response([
                    'images' => [
                        [
                            'url' => 'https://example.com/neon-harbor-poster.jpg',
                            'width' => 1500,
                            'height' => 2250,
                            'type' => 'poster',
                            'copyright' => '(c) Harbor Network',
                        ],
                    ],
                    'totalCount' => 2,
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

        $this->assertCount(2, data_get($bundle, 'images.images', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['images']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'images.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();
        $assets = MediaAsset::query()
            ->where('mediable_type', Title::class)
            ->where('mediable_id', $title->id)
            ->get();

        $this->assertCount(2, data_get($title->imdb_payload, 'images.images', []));
        $this->assertSame('(c) Harbor Network', data_get($title->imdb_payload, 'images.images.1.copyright'));
        $this->assertSame('Dockside confrontation.', data_get($title->imdb_payload, 'images.images.0.caption.plainText'));
        $this->assertGreaterThanOrEqual(3, $assets->count());
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'image.copyright') === '(c) Harbor Network'));
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'image.caption.plainText') === 'Dockside confrontation.'));
    }
}
