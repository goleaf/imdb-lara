<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\OptimizeImdbStorageAction;
use App\Models\ImdbTitleImport;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImdbOptimizeStorageCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_optimize_storage_command_compacts_duplicate_payloads_without_losing_supplemental_data(): void
    {
        $rawTitlePayload = [
            'schemaVersion' => 3,
            'imdbId' => 'tt0000001',
            'sourceUrl' => 'https://api.imdbapi.dev/titles/tt0000001',
            'downloadedAt' => '2026-04-01T12:00:00+00:00',
            'title' => [
                'id' => 'tt0000001',
                'type' => 'movie',
                'primaryTitle' => 'Neon Harbor',
                'plot' => 'A port city slips into a cold war.',
                'releaseDate' => ['year' => 2021, 'month' => 5, 'day' => 4],
                'meterRanking' => ['currentRank' => 120],
                'interests' => [
                    ['id' => 'in0000159', 'name' => 'Cyberpunk'],
                ],
            ],
            'credits' => [
                'credits' => [
                    ['name' => ['id' => 'nm0000001', 'displayName' => 'Ava Stone'], 'category' => 'actor'],
                ],
            ],
            'parentsGuide' => [
                'advisories' => [
                    [
                        'category' => 'PROFANITY',
                        'reviews' => [
                            ['text' => 'Strong language.'],
                        ],
                    ],
                ],
            ],
            'boxOffice' => [
                'worldwideGross' => ['amount' => '123456789', 'currency' => 'USD'],
            ],
            'interests' => [
                'in0000159' => [
                    'id' => 'in0000159',
                    'name' => 'Cyberpunk',
                    'description' => 'Neon futures and speculative noir.',
                ],
            ],
        ];

        $title = Title::factory()->create([
            'imdb_id' => 'tt0000001',
            'imdb_payload' => $rawTitlePayload,
        ]);

        ImdbTitleImport::query()->create([
            'imdb_id' => 'tt0000001',
            'source_url' => 'https://api.imdbapi.dev/titles/tt0000001',
            'storage_path' => storage_path('app/private/imdb-temp/titles/tt0000001.json'),
            'payload_hash' => hash('sha256', json_encode($rawTitlePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            'payload' => $rawTitlePayload,
        ]);

        $person = Person::factory()->create([
            'imdb_id' => 'nm0000001',
            'alternate_names' => 'A. Stone',
            'imdb_alternative_names' => ['A. Stone'],
            'imdb_payload' => [
                'details' => [
                    'id' => 'nm0000001',
                    'displayName' => 'Ava Stone',
                    'biography' => 'Actor bio.',
                    'birthName' => 'Ava Maria Stone',
                    'heightCm' => 178,
                ],
                'images' => [
                    'images' => [
                        ['url' => 'https://example.com/ava-stone.jpg'],
                    ],
                ],
                'relationships' => [
                    'relationships' => [
                        [
                            'name' => ['id' => 'nm0000002', 'displayName' => 'Jordan Vale'],
                            'relationType' => 'spouse',
                        ],
                    ],
                ],
                'trivia' => [
                    'triviaEntries' => [
                        ['id' => 'nt1', 'text' => 'Won stage awards.'],
                    ],
                ],
            ],
        ]);

        app(OptimizeImdbStorageAction::class)->handle();

        $title->refresh();
        $person->refresh();

        $this->assertSame(1, data_get($title->imdb_payload, 'storageVersion'));
        $this->assertNull(data_get($title->imdb_payload, 'credits'));
        $this->assertSame(2021, data_get($title->imdb_payload, 'title.releaseDate.year'));
        $this->assertSame('Strong language.', data_get($title->imdb_payload, 'parentsGuide.advisories.0.reviews.0.text'));
        $this->assertSame('Neon futures and speculative noir.', data_get($title->imdb_payload, 'interests.in0000159.description'));
        $this->assertSame(
            $rawTitlePayload,
            ImdbTitleImport::query()->where('imdb_id', 'tt0000001')->firstOrFail()->payload,
        );

        $this->assertSame(1, data_get($person->imdb_payload, 'storageVersion'));
        $this->assertSame('Ava Maria Stone', data_get($person->imdb_payload, 'details.birthName'));
        $this->assertSame(178, data_get($person->imdb_payload, 'details.heightCm'));
        $this->assertSame('Won stage awards.', data_get($person->imdb_payload, 'trivia.triviaEntries.0.text'));
        $this->assertSame('https://example.com/ava-stone.jpg', data_get($person->imdb_payload, 'images.images.0.url'));
        $this->assertNull($person->alternate_names);
    }
}
