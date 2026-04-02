<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleBoxOfficeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_box_office_payload_is_downloaded_and_preserved_in_title_payload_after_import(): void
    {
        $directory = storage_path('framework/testing/imdb-box-office-endpoint');
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
                    'companyCredits' => [],
                ], 200),
                'https://api.imdbapi.dev/titles/tt7654321/boxOffice' => Http::response([
                    'budget' => [
                        'amount' => '55000000',
                        'currency' => 'USD',
                    ],
                    'openingWeekendGross' => [
                        'amount' => '12345000',
                        'currency' => 'USD',
                    ],
                    'domesticGross' => [
                        'amount' => '45000000',
                        'currency' => 'USD',
                    ],
                    'worldwideGross' => [
                        'amount' => '98000000',
                        'currency' => 'USD',
                    ],
                    'theatricalRuns' => [
                        [
                            'market' => 'US',
                            'weeks' => 8,
                        ],
                    ],
                ], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->downloadImdbTitlePayload('tt7654321', $directory);

        $bundle = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt7654321.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('55000000', data_get($bundle, 'boxOffice.budget.amount'));
        $this->assertSame('USD', data_get($bundle, 'boxOffice.worldwideGross.currency'));
        $this->assertSame('US', data_get($bundle, 'boxOffice.theatricalRuns.0.market'));
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'box-office.json');

        $this->importImdbTitlePayloadFromPath($directory.DIRECTORY_SEPARATOR.'tt7654321.json');

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();

        $this->assertSame('12345000', data_get($title->imdb_payload, 'boxOffice.openingWeekendGross.amount'));
        $this->assertSame('98000000', data_get($title->imdb_payload, 'boxOffice.worldwideGross.amount'));
        $this->assertSame('USD', data_get($title->imdb_payload, 'boxOffice.budget.currency'));
        $this->assertSame('8', (string) data_get($title->imdb_payload, 'boxOffice.theatricalRuns.0.weeks'));
    }
}
