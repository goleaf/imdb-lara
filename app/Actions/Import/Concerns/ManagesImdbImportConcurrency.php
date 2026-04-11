<?php

namespace App\Actions\Import\Concerns;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait ManagesImdbImportConcurrency
{
    private const IMPORT_LOCK_SECONDS = 300;

    private const IMPORT_LOCK_WAIT_SECONDS = 10;

    private const IMPORT_TRANSACTION_ATTEMPTS = 3;

    private const IMPORT_TRANSACTION_RETRY_SLEEP_MICROSECONDS = 150000;

    protected function runLockedImport(string $resourceType, string $imdbId, callable $callback): mixed
    {
        try {
            return Cache::lock($this->importLockKey($resourceType, $imdbId), self::IMPORT_LOCK_SECONDS)
                ->block(
                    self::IMPORT_LOCK_WAIT_SECONDS,
                    fn (): mixed => $this->runImportTransaction($callback),
                );
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException(sprintf(
                'The IMDb %s import for [%s] is already running.',
                $resourceType,
                $imdbId,
            ), previous: $exception);
        }
    }

    protected function runImportTransaction(callable $callback): mixed
    {
        for ($attempt = 1; $attempt <= self::IMPORT_TRANSACTION_ATTEMPTS; $attempt++) {
            try {
                return DB::connection('imdb_mysql')->transaction($callback);
            } catch (QueryException $exception) {
                if (! $this->shouldRetryImportTransaction($exception) || $attempt === self::IMPORT_TRANSACTION_ATTEMPTS) {
                    throw $exception;
                }

                usleep(self::IMPORT_TRANSACTION_RETRY_SLEEP_MICROSECONDS * $attempt);
            }
        }

        throw new RuntimeException('The IMDb catalog import transaction exhausted its retry attempts.');
    }

    protected function shouldRetryImportTransaction(QueryException $exception): bool
    {
        return in_array(
            (int) ($exception->errorInfo[1] ?? 0),
            [1205, 1213],
            true,
        );
    }

    protected function importLockKey(string $resourceType, string $imdbId): string
    {
        return sprintf('imdb-catalog-import:%s:%s', $resourceType, $imdbId);
    }
}
