<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Actions\Import\WriteImdbNameVerificationReportAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImdbNameVerificationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_verification_report_confirms_payload_counts_and_normalized_relations(): void
    {
        $directory = storage_path('framework/testing/imdb-name-verification');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);

        Title::factory()->create([
            'imdb_id' => 'tt1234567',
            'name' => 'Signal Harbor',
            'title_type' => TitleType::Movie,
            'imdb_type' => 'movie',
        ]);

        $payload = [
            'id' => 'nm1000001',
            'displayName' => 'Ava Stone',
            'primaryProfessions' => ['actor'],
            'biography' => 'Ava Stone headlines Signal Harbor.',
            'birthDate' => ['year' => 1988, 'month' => 3, 'day' => 2],
            'birthLocation' => 'Seattle, Washington, USA',
            'primaryImage' => [
                'url' => 'https://example.com/ava-stone-primary.jpg',
                'width' => 1200,
                'height' => 1800,
            ],
            'images' => [
                'images' => [
                    [
                        'url' => 'https://example.com/ava-stone-1.jpg',
                        'width' => 1200,
                        'height' => 1800,
                        'type' => 'portrait',
                    ],
                    [
                        'url' => 'https://example.com/ava-stone-2.jpg',
                        'width' => 1000,
                        'height' => 1500,
                        'type' => 'portrait',
                    ],
                ],
                'totalCount' => 2,
            ],
            'relationships' => [
                'relationships' => [
                    ['name' => 'Collaborator', 'relationship' => 'Co-Star'],
                ],
                'totalCount' => 1,
            ],
            'trivia' => [
                'triviaEntries' => [
                    ['id' => 'tr1', 'text' => 'Collects vintage posters.'],
                ],
                'totalCount' => 1,
            ],
            'filmography' => [
                'credits' => [
                    [
                        'title' => [
                            'id' => 'tt1234567',
                            'primaryTitle' => 'Signal Harbor',
                        ],
                        'category' => 'actor',
                        'characters' => ['Captain Vale'],
                    ],
                ],
                'totalCount' => 1,
            ],
        ];

        $person = app(ImportImdbNamePayloadAction::class)->handle(
            $payload,
            $directory.DIRECTORY_SEPARATOR.'nm1000001.json',
        );

        $path = app(WriteImdbNameVerificationReportAction::class)->handle(
            $person->fresh(),
            $payload,
            $directory.DIRECTORY_SEPARATOR.'nm1000001',
        );
        $verification = $this->decodeJson($path);

        $this->assertSame('passed', data_get($verification, 'status'));
        $this->assertSame(1, data_get($verification, 'checks.details.source_total_count'));
        $this->assertSame(1, data_get($verification, 'checks.details.normalized_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.details.ok'));
        $this->assertSame(2, data_get($verification, 'checks.images.source_total_count'));
        $this->assertSame(2, data_get($verification, 'checks.images.normalized_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.images.ok'));
        $this->assertSame(1, data_get($verification, 'checks.filmography.source_total_count'));
        $this->assertSame(1, data_get($verification, 'checks.filmography.normalized_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.filmography.ok'));
        $this->assertSame(1, data_get($verification, 'checks.relationships.source_total_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.relationships.ok'));
        $this->assertSame(1, data_get($verification, 'checks.trivia.source_total_count'));
        $this->assertTrue((bool) data_get($verification, 'checks.trivia.ok'));
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
