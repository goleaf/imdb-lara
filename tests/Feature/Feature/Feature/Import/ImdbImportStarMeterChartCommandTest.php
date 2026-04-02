<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\DownloadImdbStarMeterChartAction;
use App\Models\MediaAsset;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbImportStarMeterChartCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_star_meter_chart_command_saves_raw_chart_json_and_imports_ranked_names(): void
    {
        $directory = storage_path('framework/testing/imdb-starmeter-chart');
        File::deleteDirectory($directory);

        $requestedUrls = [];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$requestedUrls) {
            $url = $request->url();
            $requestedUrls[] = $url;

            return match ($url) {
                'https://api.imdbapi.dev/chart/starmeter' => Http::response([
                    'names' => [
                        [
                            'id' => 'nm1000001',
                            'displayName' => 'Ava Stone',
                            'alternativeNames' => ['A. Stone'],
                            'primaryProfessions' => ['actor'],
                            'biography' => 'Ava Stone is an actor from Seattle.',
                            'meterRanking' => [
                                'currentRank' => 1,
                                'changeDirection' => 'UP',
                                'difference' => 4,
                            ],
                            'primaryImage' => [
                                'url' => 'https://example.com/ava-stone.jpg',
                                'width' => 1000,
                                'height' => 1500,
                            ],
                        ],
                    ],
                    'nextPageToken' => 'chart-page-2',
                ], 200),
                'https://api.imdbapi.dev/chart/starmeter?pageToken=chart-page-2' => Http::response([
                    'names' => [
                        [
                            'id' => 'nm1000002',
                            'displayName' => 'Morgan Lake',
                            'alternativeNames' => ['M. Lake'],
                            'primaryProfessions' => ['director'],
                            'biography' => 'Morgan Lake directs festival dramas.',
                            'meterRanking' => [
                                'currentRank' => 2,
                                'changeDirection' => 'DOWN',
                                'difference' => 1,
                            ],
                        ],
                    ],
                ], 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $result = app(DownloadImdbStarMeterChartAction::class)->handle($directory);

        foreach ($result['names'] as $nameArtifact) {
            $this->importImdbNamePayload(
                $nameArtifact['path'],
                $nameArtifact['payload'],
                ['fill_missing_only' => true],
            );
        }

        $this->assertContains('https://api.imdbapi.dev/chart/starmeter?pageToken=chart-page-2', $requestedUrls);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'chart.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'details.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000002.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000002'.DIRECTORY_SEPARATOR.'details.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'details.json');

        $chartPayload = json_decode(
            (string) File::get($directory.DIRECTORY_SEPARATOR.'chart.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($chartPayload, 'names', []));
        $this->assertArrayNotHasKey('nextPageToken', $chartPayload);
        $this->assertSame('Ava Stone', data_get($chartPayload, 'names.0.displayName'));
        $this->assertSame('Morgan Lake', data_get($chartPayload, 'names.1.displayName'));

        $ava = Person::query()->where('imdb_id', 'nm1000001')->firstOrFail();
        $morgan = Person::query()->where('imdb_id', 'nm1000002')->firstOrFail();

        $this->assertSame('Ava Stone', $ava->name);
        $this->assertSame(1, $ava->popularity_rank);
        $this->assertSame('UP', data_get($ava->imdb_payload, 'details.meterRanking.changeDirection'));
        $this->assertSame('4', (string) data_get($ava->imdb_payload, 'details.meterRanking.difference'));
        $this->assertSame('Morgan Lake', $morgan->name);
        $this->assertSame(2, $morgan->popularity_rank);
        $this->assertSame('DOWN', data_get($morgan->imdb_payload, 'details.meterRanking.changeDirection'));

        $this->assertTrue(
            MediaAsset::query()
                ->where('mediable_type', Person::class)
                ->where('mediable_id', $ava->id)
                ->where('provider', 'imdb')
                ->exists()
        );
    }
}
