<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\Credit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImdbTitleVerificationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_command_writes_a_verification_file_that_confirms_total_count_and_db_relations(): void
    {
        $directory = storage_path('framework/testing/imdb-import-verification');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'tt7654321.json';
        File::put($path, json_encode($this->bundleWithCreditsCount(67), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->importImdbTitlePayloadFromPath($path);

        $verification = $this->decodeJson($directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'verification.json');

        $this->assertSame('passed', data_get($verification, 'status'));
        $this->assertSame(67, data_get($verification, 'checks.credits.source_total_count'));
        $this->assertSame(67, data_get($verification, 'checks.credits.downloaded_count'));
        $this->assertSame(67, data_get($verification, 'checks.credits.stored_payload_count'));
        $this->assertSame(67, data_get($verification, 'checks.credits.normalized_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.credits.download_complete'));
        $this->assertTrue((bool) data_get($verification, 'checks.credits.stored_payload_complete'));
        $this->assertTrue((bool) data_get($verification, 'checks.credits.normalized_complete'));
        $this->assertTrue((bool) data_get($verification, 'checks.credits.relation_integrity_ok'));
        $this->assertTrue((bool) data_get($verification, 'checks.credits.ok'));
        $this->assertSame(67, Credit::query()->count());
    }

    public function test_verification_counts_award_nomination_rows_per_nominee(): void
    {
        $directory = storage_path('framework/testing/imdb-import-award-verification');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'tt7777001.json';
        File::put($path, json_encode($this->bundleWithAwardNominationNominees(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->importImdbTitlePayloadFromPath($path);

        $verification = $this->decodeJson($directory.DIRECTORY_SEPARATOR.'tt7777001'.DIRECTORY_SEPARATOR.'verification.json');

        $this->assertSame('passed', data_get($verification, 'status'));
        $this->assertSame(2, data_get($verification, 'checks.awardNominations.source_total_count'));
        $this->assertSame(2, data_get($verification, 'checks.awardNominations.downloaded_count'));
        $this->assertSame(2, data_get($verification, 'checks.awardNominations.stored_payload_count'));
        $this->assertSame(4, data_get($verification, 'checks.awardNominations.normalized_expected_count'));
        $this->assertSame(4, data_get($verification, 'checks.awardNominations.normalized_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.awardNominations.normalized_complete'));
        $this->assertTrue((bool) data_get($verification, 'checks.awardNominations.ok'));
    }

    /**
     * @return array<string, mixed>
     */
    private function bundleWithCreditsCount(int $count): array
    {
        return [
            'schemaVersion' => 3,
            'imdbId' => 'tt7654321',
            'sourceUrl' => 'https://api.imdbapi.dev/titles/tt7654321',
            'title' => [
                'id' => 'tt7654321',
                'type' => 'tvSeries',
                'primaryTitle' => 'Neon Harbor',
                'primaryImage' => ['url' => 'https://example.com/neon-harbor.jpg', 'width' => 1800, 'height' => 2700],
                'startYear' => 2021,
                'runtimeSeconds' => 3600,
                'genres' => ['Drama', 'Sci-Fi'],
                'rating' => ['aggregateRating' => 8.6, 'voteCount' => 43000],
                'metacritic' => ['score' => 74, 'reviewCount' => 18],
                'plot' => 'A freight hub on the edge of the Pacific turns into a battleground for rival futures.',
                'originCountries' => [['code' => 'US', 'name' => 'United States']],
                'spokenLanguages' => [['code' => 'eng', 'name' => 'English']],
                'interests' => [['id' => 'in0000159', 'name' => 'Cyberpunk']],
            ],
            'credits' => [
                'credits' => $this->generatedCreditsPayload($count),
                'totalCount' => $count,
            ],
            'releaseDates' => ['releaseDates' => [['country' => ['code' => 'US', 'name' => 'United States'], 'releaseDate' => ['year' => 2021, 'month' => 5, 'day' => 4]]]],
            'akas' => ['akas' => []],
            'seasons' => ['seasons' => []],
            'episodes' => ['episodes' => [], 'totalCount' => 0],
            'images' => ['images' => []],
            'videos' => ['videos' => []],
            'awardNominations' => ['stats' => ['nominationCount' => 0, 'winCount' => 0], 'awardNominations' => []],
            'parentsGuide' => ['advisories' => []],
            'certificates' => ['certificates' => []],
            'companyCredits' => ['companyCredits' => []],
            'boxOffice' => ['domesticGross' => ['amount' => '100000', 'currency' => 'USD']],
            'interests' => [
                'in0000159' => [
                    'id' => 'in0000159',
                    'name' => 'Cyberpunk',
                    'description' => 'Neon futures and speculative noir.',
                    'isSubgenre' => true,
                ],
            ],
            'names' => [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function generatedCreditsPayload(int $count): array
    {
        $credits = [];

        for ($index = 1; $index <= $count; $index++) {
            $credits[] = [
                'name' => [
                    'id' => sprintf('nm9%07d', $index),
                    'displayName' => 'Performer '.$index,
                    'primaryProfessions' => ['actor'],
                ],
                'category' => 'actor',
                'characters' => ['Character '.$index],
            ];
        }

        return $credits;
    }

    /**
     * @return array<string, mixed>
     */
    private function bundleWithAwardNominationNominees(): array
    {
        return [
            ...$this->bundleWithCreditsCount(0),
            'imdbId' => 'tt7777001',
            'sourceUrl' => 'https://api.imdbapi.dev/titles/tt7777001',
            'title' => [
                ...$this->bundleWithCreditsCount(0)['title'],
                'id' => 'tt7777001',
                'primaryTitle' => 'Awards Multiplex',
            ],
            'awardNominations' => [
                'stats' => ['nominationCount' => 2, 'winCount' => 1],
                'awardNominations' => [
                    [
                        'event' => ['id' => 'ev100', 'name' => 'Guild Awards'],
                        'year' => 2024,
                        'category' => 'Best Ensemble',
                        'text' => 'Best Ensemble',
                        'isWinner' => true,
                        'winnerRank' => 1,
                        'nominees' => [
                            ['id' => 'nm7000001', 'displayName' => 'Alex Lane', 'primaryProfessions' => ['actor']],
                            ['id' => 'nm7000002', 'displayName' => 'Blair North', 'primaryProfessions' => ['actor']],
                        ],
                    ],
                    [
                        'event' => ['id' => 'ev100', 'name' => 'Guild Awards'],
                        'year' => 2024,
                        'category' => 'Best Ensemble',
                        'text' => 'Best Ensemble',
                        'isWinner' => false,
                        'winnerRank' => 2,
                        'nominees' => [
                            ['id' => 'nm7000003', 'displayName' => 'Casey Hart', 'primaryProfessions' => ['actor']],
                            ['id' => 'nm7000004', 'displayName' => 'Drew Cole', 'primaryProfessions' => ['actor']],
                        ],
                    ],
                ],
            ],
        ];
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
