<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\CrawlImdbGraphAction;
use App\Models\Credit;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImdbGraphCrawlCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_graph_crawl_recursively_imports_connected_titles_and_people_and_writes_node_reports(): void
    {
        $directory = storage_path('framework/testing/imdb-graph-crawl');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt1000001' => Http::response($this->titlePayload('tt1000001', 'Seed One'), 200),
                'https://api.imdbapi.dev/titles/tt1000001/credits' => Http::response($this->creditsPayload('Seed Hero'), 200),
                'https://api.imdbapi.dev/titles/tt1000001/releaseDates' => Http::response($this->releaseDatesPayload(), 200),
                'https://api.imdbapi.dev/titles/tt1000001/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt1000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/titles/tt1000002' => Http::response($this->titlePayload('tt1000002', 'Seed Two', 'A second linked title.'), 200),
                'https://api.imdbapi.dev/titles/tt1000002/credits' => Http::response($this->creditsPayload('Relay Pilot'), 200),
                'https://api.imdbapi.dev/titles/tt1000002/releaseDates' => Http::response($this->releaseDatesPayload(2025, 5, 1), 200),
                'https://api.imdbapi.dev/titles/tt1000002/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt1000002/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/names/nm1000001' => Http::response($this->nameDetailsPayload(), 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/names/nm1000001/relationships' => Http::response(['relationships' => []], 200),
                'https://api.imdbapi.dev/names/nm1000001/trivia' => Http::response(['triviaEntries' => []], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response($this->filmographyPayload(), 200),

                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        app(CrawlImdbGraphAction::class)->handle(['tt1000001'], [
            'storage_root' => $directory,
            'max_nodes' => 3,
        ]);

        $person = Person::query()->where('imdb_id', 'nm1000001')->firstOrFail();
        $seedTwo = Title::query()->where('imdb_id', 'tt1000002')->firstOrFail();

        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt1000001']);
        $this->assertDatabaseHas('titles', ['imdb_id' => 'tt1000002']);
        $this->assertDatabaseHas('people', ['imdb_id' => 'nm1000001']);
        $this->assertDatabaseHas('credits', [
            'title_id' => $seedTwo->id,
            'person_id' => $person->id,
        ]);

        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt1000001'.DIRECTORY_SEPARATOR.'import-report.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'import-report.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'titles'.DIRECTORY_SEPARATOR.'tt1000002'.DIRECTORY_SEPARATOR.'import-report.json');
        $this->assertNotEmpty(File::glob($directory.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'crawl-*.json'));
    }

    public function test_graph_crawl_only_fills_missing_fields_and_still_adds_new_links(): void
    {
        $directory = storage_path('framework/testing/imdb-graph-fill-missing');
        File::deleteDirectory($directory);

        $title = Title::factory()->create([
            'imdb_id' => 'tt2000001',
            'name' => 'Existing Seed',
            'plot_outline' => 'Keep this original plot.',
            'synopsis' => null,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/titles/tt2000001' => Http::response($this->titlePayload('tt2000001', 'Existing Seed', 'Incoming replacement plot.', 'New synopsis from IMDb.'), 200),
            'api.imdbapi.dev/titles/tt2000001/credits*' => Http::response($this->creditsPayload('New Link Character'), 200),
            'api.imdbapi.dev/titles/tt2000001/releaseDates*' => Http::response($this->releaseDatesPayload(), 200),
            'api.imdbapi.dev/titles/tt2000001/akas*' => Http::response(['akas' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/seasons*' => Http::response(['seasons' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/episodes*' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
            'api.imdbapi.dev/titles/tt2000001/images*' => Http::response(['images' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/videos*' => Http::response(['videos' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/awardNominations*' => Http::response(['awardNominations' => [], 'stats' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/parentsGuide*' => Http::response(['advisories' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/certificates*' => Http::response(['certificates' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/companyCredits*' => Http::response(['companyCredits' => []], 200),
            'api.imdbapi.dev/titles/tt2000001/boxOffice*' => Http::response([], 200),
            'api.imdbapi.dev/names/nm1000001' => Http::response($this->nameDetailsPayload(), 200),
            'api.imdbapi.dev/names/nm1000001/images*' => Http::response(['images' => []], 200),
            'api.imdbapi.dev/names/nm1000001/relationships*' => Http::response(['relationships' => []], 200),
            'api.imdbapi.dev/names/nm1000001/trivia*' => Http::response(['triviaEntries' => []], 200),
        ]);

        app(CrawlImdbGraphAction::class)->handle(['tt2000001'], [
            'storage_root' => $directory,
            'max_nodes' => 1,
        ]);

        $title->refresh();
        $person = Person::query()->where('imdb_id', 'nm1000001')->firstOrFail();

        $this->assertSame('Keep this original plot.', $title->plot_outline);
        $this->assertSame('New synopsis from IMDb.', $title->synopsis);
        $this->assertTrue(Credit::query()->where('title_id', $title->id)->where('person_id', $person->id)->exists());
    }

    #[DataProvider('nameEndpointReportProvider')]
    public function test_graph_crawl_writes_a_separate_report_for_each_name_endpoint(
        string $endpoint,
        string $artifactPath,
        ?string $expectedAddedField,
        ?string $expectedRelationKey,
    ): void {
        $directory = storage_path('framework/testing/imdb-graph-name-reports');
        File::deleteDirectory($directory);

        $this->fakeGraphResponses();

        app(CrawlImdbGraphAction::class)->handle(['tt1000001'], [
            'storage_root' => $directory,
            'max_nodes' => 2,
        ]);

        $report = $this->decodeJson(
            $directory.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm1000001'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.$endpoint.'.json'
        );

        $this->assertSame($endpoint, data_get($report, 'endpoint'));
        $this->assertSame($artifactPath, data_get($report, 'artifact_path'));
        $this->assertSame('nm1000001', data_get($report, 'imdb_id'));
        $this->assertTrue((bool) data_get($report, 'has_payload'));

        if ($expectedAddedField !== null) {
            $this->assertContains(
                $expectedAddedField,
                array_merge(
                    data_get($report, 'existing_fields', []),
                    data_get($report, 'added_fields', []),
                ),
            );
        }

        if ($expectedRelationKey !== null) {
            $relations = data_get($report, 'added_relations.'.$expectedRelationKey)
                ?? data_get($report, 'existing_relations.'.$expectedRelationKey);

            $this->assertNotNull($relations);
            $this->assertNotEmpty($relations);
        }
    }

    public function test_graph_crawl_writes_a_separate_report_for_interest_endpoint(): void
    {
        $directory = storage_path('framework/testing/imdb-graph-interest-reports');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/interests/in0000159' => Http::response([
                'id' => 'in0000159',
                'name' => 'Cyberpunk',
                'description' => 'Neon futures and speculative noir.',
                'similarInterests' => [
                    ['id' => 'in0000160', 'name' => 'Tech Noir'],
                ],
            ], 200),
        ]);

        app(CrawlImdbGraphAction::class)->handle(['in0000159'], [
            'storage_root' => $directory,
            'max_nodes' => 1,
        ]);

        $report = $this->decodeJson(
            $directory.DIRECTORY_SEPARATOR.'interests'.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'interest.json'
        );

        $this->assertSame('interest', data_get($report, 'endpoint'));
        $this->assertSame('interest.json', data_get($report, 'artifact_path'));
        $this->assertSame('in0000159', data_get($report, 'imdb_id'));
        $this->assertTrue((bool) data_get($report, 'has_payload'));
        $this->assertArrayHasKey('similar_interests', data_get($report, 'added_relations', []));
        $this->assertContains('in0000160', data_get($report, 'added_relations.similar_interests', []));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string|null, 3: string|null}>
     */
    public static function nameEndpointReportProvider(): array
    {
        return [
            'details' => ['details', 'details.json', 'Name', null],
            'images' => ['images', 'images.json', null, 'media_assets'],
            'filmography' => ['filmography', 'filmography.json', null, 'credits'],
            'relationships' => ['relationships', 'relationships.json', null, 'payload_sections'],
            'trivia' => ['trivia', 'trivia.json', null, 'payload_sections'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function titlePayload(
        string $imdbId,
        string $title,
        string $plot = 'A seed title opens the graph.',
        ?string $synopsis = null,
    ): array {
        return [
            'id' => $imdbId,
            'type' => 'movie',
            'primaryTitle' => $title,
            'primaryImage' => [
                'url' => 'https://example.com/'.$imdbId.'.jpg',
                'width' => 1600,
                'height' => 2400,
            ],
            'startYear' => 2024,
            'runtimeSeconds' => 7200,
            'genres' => ['Drama'],
            'plot' => $plot,
            'synopsis' => $synopsis,
            'stars' => [
                [
                    'id' => 'nm1000001',
                    'displayName' => 'Ava Stone',
                    'primaryProfessions' => ['actor'],
                ],
            ],
            'rating' => [
                'aggregateRating' => 7.8,
                'voteCount' => 1800,
            ],
            'originCountries' => [
                ['code' => 'US', 'name' => 'United States'],
            ],
            'spokenLanguages' => [
                ['code' => 'eng', 'name' => 'English'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditsPayload(string $characterName): array
    {
        return [
            'credits' => [
                [
                    'name' => [
                        'id' => 'nm1000001',
                        'displayName' => 'Ava Stone',
                        'primaryProfessions' => ['actor'],
                    ],
                    'category' => 'actor',
                    'characters' => [$characterName],
                ],
            ],
            'totalCount' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseDatesPayload(int $year = 2024, int $month = 4, int $day = 1): array
    {
        return [
            'releaseDates' => [
                [
                    'country' => ['code' => 'US', 'name' => 'United States'],
                    'releaseDate' => ['year' => $year, 'month' => $month, 'day' => $day],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nameDetailsPayload(): array
    {
        return [
            'id' => 'nm1000001',
            'displayName' => 'Ava Stone',
            'primaryProfessions' => ['actor'],
            'biography' => 'Ava Stone crosses between connected titles.',
            'birthDate' => ['year' => 1990, 'month' => 5, 'day' => 2],
            'birthLocation' => 'Seattle, Washington, USA',
            'primaryImage' => [
                'url' => 'https://example.com/ava-stone.jpg',
                'width' => 1000,
                'height' => 1500,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filmographyPayload(): array
    {
        return [
            'credits' => [
                [
                    'title' => [
                        'id' => 'tt1000001',
                        'primaryTitle' => 'Seed One',
                    ],
                    'category' => 'actor',
                ],
                [
                    'title' => [
                        'id' => 'tt1000002',
                        'primaryTitle' => 'Seed Two',
                    ],
                    'category' => 'actor',
                ],
            ],
            'totalCount' => 2,
        ];
    }

    private function fakeGraphResponses(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt1000001' => Http::response($this->titlePayload('tt1000001', 'Seed One'), 200),
                'https://api.imdbapi.dev/titles/tt1000001/credits' => Http::response($this->creditsPayload('Seed Hero'), 200),
                'https://api.imdbapi.dev/titles/tt1000001/releaseDates' => Http::response($this->releaseDatesPayload(), 200),
                'https://api.imdbapi.dev/titles/tt1000001/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt1000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000001/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/titles/tt1000002' => Http::response($this->titlePayload('tt1000002', 'Seed Two', 'A second linked title.'), 200),
                'https://api.imdbapi.dev/titles/tt1000002/credits' => Http::response($this->creditsPayload('Relay Pilot'), 200),
                'https://api.imdbapi.dev/titles/tt1000002/releaseDates' => Http::response($this->releaseDatesPayload(2025, 5, 1), 200),
                'https://api.imdbapi.dev/titles/tt1000002/akas' => Http::response(['akas' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/seasons' => Http::response(['seasons' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/episodes' => Http::response(['episodes' => [], 'totalCount' => 0], 200),
                'https://api.imdbapi.dev/titles/tt1000002/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/videos' => Http::response(['videos' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/awardNominations' => Http::response(['awardNominations' => [], 'stats' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/certificates' => Http::response(['certificates' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/companyCredits' => Http::response(['companyCredits' => []], 200),
                'https://api.imdbapi.dev/titles/tt1000002/boxOffice' => Http::response([], 200),

                'https://api.imdbapi.dev/names/nm1000001' => Http::response($this->nameDetailsPayload(), 200),
                'https://api.imdbapi.dev/names/nm1000001/images' => Http::response(['images' => []], 200),
                'https://api.imdbapi.dev/names/nm1000001/relationships' => Http::response(['relationships' => []], 200),
                'https://api.imdbapi.dev/names/nm1000001/trivia' => Http::response(['triviaEntries' => []], 200),
                'https://api.imdbapi.dev/names/nm1000001/filmography' => Http::response($this->filmographyPayload(), 200),

                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $path): array
    {
        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
