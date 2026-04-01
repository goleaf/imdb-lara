<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleParentsGuideEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_parents_guide_is_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-parents-guide-endpoint');
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
                    'advisories' => [
                        [
                            'category' => 'violence',
                            'severity' => 'moderate',
                            'text' => 'Dockside fistfights and tense gun standoffs.',
                        ],
                    ],
                    'spoilers' => [
                        'Mild reveal about the final shipment.',
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'parents-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/parentsGuide?pageToken=parents-page-2' => Http::response([
                    'advisories' => [
                        [
                            'category' => 'language',
                            'severity' => 'mild',
                            'text' => 'Occasional strong language in heated scenes.',
                        ],
                    ],
                    'spoilers' => [
                        'Mild reveal about the final shipment.',
                    ],
                    'totalCount' => 2,
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

        $this->assertCount(2, data_get($bundle, 'parentsGuide.advisories', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['parentsGuide']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'parents-guide.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();

        $this->assertCount(2, data_get($title->imdb_payload, 'parentsGuide.advisories', []));
        $this->assertSame('language', data_get($title->imdb_payload, 'parentsGuide.advisories.1.category'));
        $this->assertSame('mild', data_get($title->imdb_payload, 'parentsGuide.advisories.1.severity'));
        $this->assertSame(
            'Mild reveal about the final shipment.',
            data_get($title->imdb_payload, 'parentsGuide.spoilers.0')
        );
    }
}
