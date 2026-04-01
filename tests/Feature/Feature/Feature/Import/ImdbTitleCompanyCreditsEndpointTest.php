<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Company;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleCompanyCreditsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_company_credits_are_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-company-credits-endpoint');
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
                    'certificates' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/companyCredits' => Http::response([
                    'companyCredits' => [
                        [
                            'company' => [
                                'id' => 'co123',
                                'name' => 'Harbor Network',
                            ],
                            'category' => 'distribution',
                            'countries' => [
                                ['code' => 'US', 'name' => 'United States'],
                            ],
                            'attributes' => ['streaming'],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'company-page-2',
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/companyCredits?pageToken=company-page-2' => Http::response([
                    'companyCredits' => [
                        [
                            'company' => [
                                'id' => 'co124',
                                'name' => 'Dockside Studio',
                            ],
                            'category' => 'production',
                            'countries' => [
                                ['code' => 'GB', 'name' => 'United Kingdom'],
                            ],
                            'attributes' => ['principal-photography'],
                        ],
                    ],
                    'totalCount' => 2,
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

        $this->assertCount(2, data_get($bundle, 'companyCredits.companyCredits', []));
        $this->assertArrayNotHasKey('nextPageToken', $bundle['companyCredits']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'company-credits.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();

        $this->assertCount(2, data_get($title->imdb_payload, 'companyCredits.companyCredits', []));
        $this->assertSame('co124', data_get($title->imdb_payload, 'companyCredits.companyCredits.1.company.id'));
        $this->assertSame(
            'principal-photography',
            data_get($title->imdb_payload, 'companyCredits.companyCredits.1.attributes.0')
        );
        $this->assertTrue(Company::query()->where('slug', 'co123-harbor-network')->where('country_code', 'US')->exists());
        $this->assertTrue(Company::query()->where('slug', 'co124-dockside-studio')->where('country_code', 'GB')->exists());
        $this->assertDatabaseHas('company_title', [
            'title_id' => $title->id,
            'relationship' => 'distribution',
            'credited_as' => 'streaming',
        ]);
        $this->assertTrue(
            DB::table('company_title')
                ->where('title_id', $title->id)
                ->where('relationship', 'production')
                ->where('credited_as', 'principal-photography')
                ->exists()
        );
    }
}
