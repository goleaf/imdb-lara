<?php

namespace Database\Factories;

use App\Models\ImdbTitleImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImdbTitleImport>
 */
class ImdbTitleImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $imdbId = 'tt'.$this->faker->unique()->numerify('########');
        $payload = [
            'id' => $imdbId,
            'type' => 'movie',
            'primaryTitle' => $this->faker->sentence(3),
        ];

        return [
            'imdb_id' => $imdbId,
            'source_url' => sprintf('https://api.imdbapi.dev/titles/%s', $imdbId),
            'storage_path' => storage_path(sprintf('app/private/imdb-temp/titles/%s.json', $imdbId)),
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'payload' => $payload,
            'downloaded_at' => now(),
            'imported_at' => now(),
        ];
    }
}
