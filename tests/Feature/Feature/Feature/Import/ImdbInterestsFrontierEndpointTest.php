<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\CrawlImdbGraphAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbInterestsFrontierEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_interests_frontier_is_saved_and_queued_interest_nodes_are_processed(): void
    {
        $directory = storage_path('framework/testing/imdb-interests-frontier');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/interests' => Http::response([
                'categories' => [
                    [
                        'category' => 'genres',
                        'interests' => [
                            [
                                'id' => 'in0000159',
                                'name' => 'Cyberpunk',
                                'description' => 'Neon futures and speculative noir.',
                                'isSubgenre' => true,
                            ],
                            [
                                'id' => 'in0000160',
                                'name' => 'Tech Noir',
                            ],
                        ],
                    ],
                    [
                        'category' => 'moods',
                        'interests' => [
                            [
                                'id' => 'in0000999',
                                'name' => 'Melancholic',
                            ],
                        ],
                    ],
                ],
            ], 200),
            'api.imdbapi.dev/interests/in0000159' => Http::response([
                'id' => 'in0000159',
                'name' => 'Cyberpunk',
                'description' => 'Neon futures and speculative noir.',
                'isSubgenre' => true,
                'similarInterests' => [
                    ['id' => 'in0000160', 'name' => 'Tech Noir'],
                ],
            ], 200),
        ]);

        $report = app(CrawlImdbGraphAction::class)->handle([], [
            'storage_root' => $directory,
            'bootstrap_titles' => false,
            'bootstrap_interests' => true,
            'bootstrap_star_meter' => false,
            'max_nodes' => 1,
        ]);

        $this->assertSame(1, count($report['frontiers']));
        $this->assertSame('interests', data_get($report, 'frontiers.0.type'));
        $this->assertSame('3', (string) data_get($report, 'frontiers.0.discovered_count'));

        $categoriesPath = $directory.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'interests'.DIRECTORY_SEPARATOR.'categories.json';
        $interestBundlePath = $directory.DIRECTORY_SEPARATOR.'interests'.DIRECTORY_SEPARATOR.'in0000159.json';
        $interestArtifactPath = $directory.DIRECTORY_SEPARATOR.'interests'.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'interest.json';
        $interestImportReportPath = $directory.DIRECTORY_SEPARATOR.'interests'.DIRECTORY_SEPARATOR.'in0000159'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'interest.json';

        $this->assertFileExists($categoriesPath);
        $this->assertFileExists($interestBundlePath);
        $this->assertFileExists($interestArtifactPath);
        $this->assertFileExists($interestImportReportPath);

        $frontierPayload = json_decode(
            (string) File::get($categoriesPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, data_get($frontierPayload, 'categories', []));
        $this->assertSame('genres', data_get($frontierPayload, 'categories.0.category'));
        $this->assertSame('in0000159', data_get($frontierPayload, 'categories.0.interests.0.id'));
        $this->assertSame('Melancholic', data_get($frontierPayload, 'categories.1.interests.0.name'));

        $interestBundle = json_decode(
            (string) File::get($interestBundlePath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('in0000159', data_get($interestBundle, 'imdbId'));
        $this->assertSame('Cyberpunk', data_get($interestBundle, 'interest.name'));
        $this->assertSame('in0000160', data_get($interestBundle, 'interest.similarInterests.0.id'));

        $interestImportReport = json_decode(
            (string) File::get($interestImportReportPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertTrue((bool) data_get($interestImportReport, 'has_payload'));
        $this->assertContains('Interest', data_get($interestImportReport, 'added_relations.payload_sections', []));
        $this->assertContains('in0000160', data_get($interestImportReport, 'added_relations.similar_interests', []));
    }
}
