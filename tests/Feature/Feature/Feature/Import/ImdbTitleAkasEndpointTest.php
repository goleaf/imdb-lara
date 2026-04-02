<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Title;
use App\Models\TitleTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleAkasEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_akas_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-akas-endpoint');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt0133093' => Http::response([
                    'id' => 'tt0133093',
                    'type' => 'movie',
                    'primaryTitle' => 'Neon Harbor',
                    'genres' => ['Drama'],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/credits' => Http::response([
                    'credits' => [],
                    'totalCount' => 0,
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/releaseDates' => Http::response([
                    'releaseDates' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/akas' => Http::response([
                    'akas' => [
                        [
                            'text' => 'Неонова гавань',
                            'country' => ['code' => 'UA', 'name' => 'Ukraine'],
                            'language' => ['code' => 'uk', 'name' => 'Ukrainian'],
                            'attributes' => ['literal title'],
                        ],
                        [
                            'text' => 'Неон Харбор',
                            'country' => ['code' => 'UA', 'name' => 'Ukraine'],
                            'language' => ['code' => 'uk', 'name' => 'Ukrainian'],
                            'attributes' => ['festival title'],
                        ],
                    ],
                    'totalCount' => 3,
                    'nextPageToken' => 'aka-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/akas?pageToken=aka-page-2' => Http::response([
                    'akas' => [
                        [
                            'text' => 'Neon Harbour',
                            'country' => ['code' => 'CA', 'name' => 'Canada'],
                            'attributes' => ['English title'],
                        ],
                    ],
                    'totalCount' => 3,
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/seasons' => Http::response([
                    'seasons' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/episodes' => Http::response([
                    'episodes' => [],
                    'totalCount' => 0,
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/images' => Http::response([
                    'images' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/videos' => Http::response([
                    'videos' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/awardNominations' => Http::response([
                    'awardNominations' => [],
                    'stats' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/parentsGuide' => Http::response([
                    'advisories' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/certificates' => Http::response([
                    'certificates' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/companyCredits' => Http::response([
                    'companyCredits' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt0133093/boxOffice' => Http::response([], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->downloadImdbTitlePayload('tt0133093', $directory);

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt0133093.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(3, data_get($bundle, 'akas.akas', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['akas']);

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt0133093.json');

        $title = Title::query()->where('imdb_id', 'tt0133093')->firstOrFail();

        $this->assertCount(3, data_get($title->imdb_payload, 'akas.akas', []));
        $this->assertSame('festival title', data_get($title->imdb_payload, 'akas.akas.1.attributes.0'));
        $this->assertStringContainsString('Неонова гавань', (string) $title->search_keywords);
        $this->assertStringContainsString('Неон Харбор', (string) $title->search_keywords);
        $this->assertTrue(TitleTranslation::query()->where('title_id', $title->id)->where('locale', 'uk-UA')->exists());
        $this->assertTrue(TitleTranslation::query()->where('title_id', $title->id)->where('locale', 'und-CA')->exists());
    }
}
