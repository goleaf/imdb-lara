<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleVideosEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_videos_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-videos-endpoint');
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
                    'videos' => [
                        [
                            'id' => 'vi1000001',
                            'type' => 'trailer',
                            'name' => 'Official Trailer',
                            'description' => 'Launch trailer.',
                            'width' => 1920,
                            'height' => 1080,
                            'runtimeSeconds' => 90,
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'video-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/videos?pageToken=video-page-2' => Http::response([
                    'videos' => [
                        [
                            'id' => 'vi1000002',
                            'type' => 'clip',
                            'name' => 'Dockyard Chase',
                            'description' => 'Extended chase sequence.',
                            'width' => 1280,
                            'height' => 720,
                            'runtimeSeconds' => 45,
                        ],
                    ],
                    'totalCount' => 2,
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

        $this->assertCount(2, data_get($bundle, 'videos.videos', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['videos']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'videos.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();
        $assets = MediaAsset::query()
            ->where('mediable_type', Title::class)
            ->where('mediable_id', $title->id)
            ->get();

        $this->assertCount(2, data_get($title->imdb_payload, 'videos.videos', []));
        $this->assertSame('Dockyard Chase', data_get($title->imdb_payload, 'videos.videos.1.name'));
        $this->assertSame('45', (string) data_get($title->imdb_payload, 'videos.videos.1.runtimeSeconds'));
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'video.id') === 'vi1000001'));
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => data_get($asset->metadata, 'video.id') === 'vi1000002'));
        $this->assertTrue($assets->contains(fn (MediaAsset $asset): bool => $asset->provider_key === sha1('title-video:tt7654321:vi1000002')));
    }
}
