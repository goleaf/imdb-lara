<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleCertificatesEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_certificates_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-certificates-endpoint');
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
                    'awardNominations' => [],
                    'stats' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/parentsGuide' => Http::response([
                    'advisories' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/certificates' => Http::response([
                    'certificates' => [
                        [
                            'rating' => 'R',
                            'country' => [
                                'code' => 'US',
                                'name' => 'United States',
                            ],
                            'attributes' => ['violence', 'language'],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'certificate-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/certificates?pageToken=certificate-page-2' => Http::response([
                    'certificates' => [
                        [
                            'rating' => '15',
                            'country' => [
                                'code' => 'GB',
                                'name' => 'United Kingdom',
                            ],
                            'attributes' => ['strong-threat'],
                        ],
                    ],
                    'totalCount' => 2,
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

        $this->assertCount(2, data_get($bundle, 'certificates.certificates', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['certificates']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'certificates.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();

        $this->assertCount(2, data_get($title->imdb_payload, 'certificates.certificates', []));
        $this->assertSame('GB', data_get($title->imdb_payload, 'certificates.certificates.1.country.code'));
        $this->assertSame('strong-threat', data_get($title->imdb_payload, 'certificates.certificates.1.attributes.0'));
        $this->assertSame('R', $title->age_rating);
    }
}
