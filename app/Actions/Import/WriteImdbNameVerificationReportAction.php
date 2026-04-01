<?php

namespace App\Actions\Import;

use App\Models\Credit;
use App\Models\MediaAsset;
use App\Models\Person;
use Illuminate\Support\Facades\File;

class WriteImdbNameVerificationReportAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Person $person, array $payload, string $artifactDirectory): string
    {
        $storedPayload = is_array($person->imdb_payload) ? $person->imdb_payload : [];
        $checks = [
            'details' => $this->buildCheck(
                sourceTotalCount: $this->hasDetailsPayload($payload) ? 1 : 0,
                downloadedCount: $this->hasDetailsPayload($payload) ? 1 : 0,
                storedPayloadCount: $this->hasDetailsPayload($storedPayload) ? 1 : 0,
                normalizedCount: Person::query()->whereKey($person->getKey())->count(),
                relationIntegrity: ! is_array(data_get($payload, 'primaryImage'))
                    || $this->hasMediaAssetsByContext($person, ['person-primary-image']),
            ),
            'images' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'images.totalCount', 'images.images'),
                downloadedCount: $this->countItems($payload, 'images.images'),
                storedPayloadCount: $this->countItems($storedPayload, 'images.images'),
                normalizedCount: $this->countMediaAssetsByContext($person, ['person-image']),
                relationIntegrity: ! $this->hasMediaAssetsWithoutUrl($person, ['person-image']),
            ),
            'filmography' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'filmography.totalCount', 'filmography.credits'),
                downloadedCount: $this->countItems($payload, 'filmography.credits'),
                storedPayloadCount: $this->countItems($storedPayload, 'filmography.credits'),
                normalizedCount: Credit::query()
                    ->where('person_id', $person->id)
                    ->where('imdb_source_group', 'imdb:filmography')
                    ->count(),
                relationIntegrity: ! Credit::query()
                    ->where('person_id', $person->id)
                    ->where('imdb_source_group', 'imdb:filmography')
                    ->where(function ($query): void {
                        $query->whereNull('title_id')
                            ->orWhereDoesntHave('title');
                    })
                    ->exists(),
            ),
            'relationships' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'relationships.totalCount', 'relationships.relationships'),
                downloadedCount: $this->countItems($payload, 'relationships.relationships'),
                storedPayloadCount: $this->countItems($storedPayload, 'relationships.relationships'),
                normalizedCount: $this->countItems($storedPayload, 'relationships.relationships'),
                relationIntegrity: is_array(data_get($storedPayload, 'relationships'))
                    || $this->countItems($payload, 'relationships.relationships') === 0,
            ),
            'trivia' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'trivia.totalCount', 'trivia.triviaEntries'),
                downloadedCount: $this->countItems($payload, 'trivia.triviaEntries'),
                storedPayloadCount: $this->countItems($storedPayload, 'trivia.triviaEntries'),
                normalizedCount: $this->countItems($storedPayload, 'trivia.triviaEntries'),
                relationIntegrity: is_array(data_get($storedPayload, 'trivia'))
                    || $this->countItems($payload, 'trivia.triviaEntries') === 0,
            ),
        ];

        $status = collect($checks)->every(fn (array $check): bool => (bool) data_get($check, 'ok'))
            ? 'passed'
            : 'failed';
        $report = [
            'imdb_id' => $person->imdb_id,
            'verified_at' => now()->toIso8601String(),
            'status' => $status,
            'checks' => $checks,
        ];
        $path = rtrim($artifactDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'verification.json';

        File::ensureDirectoryExists($artifactDirectory);
        File::put($path, json_encode(
            $report,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }

    private function hasDetailsPayload(array $payload): bool
    {
        return is_array(data_get($payload, 'details')) || is_string(data_get($payload, 'displayName'));
    }

    private function sourceTotalCount(array $payload, string $totalCountPath, string $itemsPath): int
    {
        $totalCount = data_get($payload, $totalCountPath);

        if (is_numeric($totalCount)) {
            return (int) $totalCount;
        }

        return $this->countItems($payload, $itemsPath);
    }

    private function countItems(array $payload, string $path): int
    {
        $items = data_get($payload, $path);

        return is_array($items) && array_is_list($items) ? count($items) : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCheck(
        int $sourceTotalCount,
        int $downloadedCount,
        int $storedPayloadCount,
        int $normalizedCount,
        bool $relationIntegrity,
    ): array {
        return [
            'source_total_count' => $sourceTotalCount,
            'downloaded_count' => $downloadedCount,
            'stored_payload_count' => $storedPayloadCount,
            'normalized_count' => $normalizedCount,
            'download_complete' => $sourceTotalCount === $downloadedCount,
            'stored_payload_complete' => $sourceTotalCount === $storedPayloadCount,
            'normalized_complete' => $sourceTotalCount === $normalizedCount,
            'relation_integrity_ok' => $relationIntegrity,
            'ok' => $sourceTotalCount === $downloadedCount
                && $sourceTotalCount === $storedPayloadCount
                && $sourceTotalCount === $normalizedCount
                && $relationIntegrity,
        ];
    }

    /**
     * @param  list<string>  $contexts
     */
    private function countMediaAssetsByContext(Person $person, array $contexts): int
    {
        return MediaAsset::query()
            ->where('mediable_type', $person::class)
            ->where('mediable_id', $person->id)
            ->get()
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array(data_get($mediaAsset->metadata, 'source_context'), $contexts, true))
            ->count();
    }

    /**
     * @param  list<string>  $contexts
     */
    private function hasMediaAssetsByContext(Person $person, array $contexts): bool
    {
        return $this->countMediaAssetsByContext($person, $contexts) > 0;
    }

    /**
     * @param  list<string>  $contexts
     */
    private function hasMediaAssetsWithoutUrl(Person $person, array $contexts): bool
    {
        return MediaAsset::query()
            ->where('mediable_type', $person::class)
            ->where('mediable_id', $person->id)
            ->get()
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array(data_get($mediaAsset->metadata, 'source_context'), $contexts, true))
            ->contains(fn (MediaAsset $mediaAsset): bool => ! is_string($mediaAsset->url) || trim($mediaAsset->url) === '');
    }
}
