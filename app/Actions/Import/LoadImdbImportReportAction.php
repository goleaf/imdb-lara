<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

class LoadImdbImportReportAction
{
    /**
     * @return array<string, mixed>|null
     */
    public function forTitle(?string $imdbId): ?array
    {
        return $this->loadReport('titles', $imdbId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forPerson(?string $imdbId): ?array
    {
        return $this->loadReport('names', $imdbId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadReport(string $directory, ?string $imdbId): ?array
    {
        if (! is_string($imdbId) || trim($imdbId) === '') {
            return null;
        }

        $path = storage_path('app/private/imdb-temp/'.$directory.'/'.$imdbId.'/import-report.json');

        if (! File::exists($path)) {
            return null;
        }

        $lastModified = File::lastModified($path);
        $cacheKey = sprintf('imdb-import-report:%s:%s:%d', $directory, $imdbId, $lastModified);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($path): ?array {
            try {
                $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException(sprintf('Stored import report [%s] is invalid JSON.', $path), previous: $exception);
            }

            return is_array($payload) ? $payload : null;
        });
    }
}
