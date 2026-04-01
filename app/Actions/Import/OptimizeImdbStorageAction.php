<?php

namespace App\Actions\Import;

use App\Models\Person;
use App\Models\Title;

class OptimizeImdbStorageAction
{
    public function __construct(private readonly BuildCompactImdbPayloadAction $buildCompactImdbPayloadAction) {}

    /**
     * @return array{
     *     updated_titles: int,
     *     updated_people: int,
     *     cleared_alternate_names: int,
     *     dry_run: bool
     * }
     */
    public function handle(bool $dryRun = false, int $chunkSize = 100): array
    {
        $chunkSize = max(1, $chunkSize);
        $updatedTitles = 0;
        $updatedPeople = 0;
        $clearedAlternateNames = 0;

        Title::query()
            ->select(['id', 'imdb_id', 'imdb_payload'])
            ->whereNotNull('imdb_id')
            ->with(['imdbImport:id,imdb_id,payload'])
            ->chunkById($chunkSize, function ($titles) use ($dryRun, &$updatedTitles): void {
                foreach ($titles as $title) {
                    $sourcePayload = is_array($title->imdbImport?->payload)
                        ? $title->imdbImport->payload
                        : (is_array($title->imdb_payload) ? $title->imdb_payload : null);

                    if (! is_array($sourcePayload)) {
                        continue;
                    }

                    $compactPayload = $this->buildCompactImdbPayloadAction->forTitle($sourcePayload);

                    if (! $this->payloadChanged($title->imdb_payload, $compactPayload)) {
                        continue;
                    }

                    $updatedTitles++;

                    if (! $dryRun) {
                        $title->forceFill([
                            'imdb_payload' => $compactPayload,
                        ])->saveQuietly();
                    }
                }
            });

        Person::query()
            ->select(['id', 'imdb_id', 'alternate_names', 'imdb_alternative_names', 'imdb_payload'])
            ->whereNotNull('imdb_id')
            ->chunkById($chunkSize, function ($people) use ($dryRun, &$updatedPeople, &$clearedAlternateNames): void {
                foreach ($people as $person) {
                    $updates = [];
                    $compactPayload = $this->buildCompactImdbPayloadAction->forPerson(
                        is_array($person->imdb_payload) ? $person->imdb_payload : null,
                    );

                    if ($this->payloadChanged($person->imdb_payload, $compactPayload)) {
                        $updates['imdb_payload'] = $compactPayload;
                    }

                    $alternateNames = $person->resolvedAlternateNames();
                    $importedAlternateNames = collect(is_array($person->imdb_alternative_names) ? $person->imdb_alternative_names : [])
                        ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                        ->map(fn (string $value): string => trim($value))
                        ->unique()
                        ->values()
                        ->all();

                    if ($alternateNames !== [] && $alternateNames === $importedAlternateNames && $person->alternate_names !== null) {
                        $updates['alternate_names'] = null;
                        $clearedAlternateNames++;
                    }

                    if ($updates === []) {
                        continue;
                    }

                    $updatedPeople++;

                    if (! $dryRun) {
                        $person->forceFill($updates)->saveQuietly();
                    }
                }
            });

        return [
            'updated_titles' => $updatedTitles,
            'updated_people' => $updatedPeople,
            'cleared_alternate_names' => $clearedAlternateNames,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $existingPayload
     * @param  array<string, mixed>  $newPayload
     */
    private function payloadChanged(?array $existingPayload, array $newPayload): bool
    {
        if (! is_array($existingPayload)) {
            return $newPayload !== [];
        }

        return json_encode($existingPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            !== json_encode($newPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
