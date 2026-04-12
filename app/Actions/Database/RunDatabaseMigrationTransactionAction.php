<?php

namespace App\Actions\Database;

use Illuminate\Database\QueryException;
use RuntimeException;

class RunDatabaseMigrationTransactionAction
{
    private const RETRYABLE_ERROR_CODES = [
        1205,
        1213,
    ];

    private const RETRY_SLEEP_MICROSECONDS = 150000;

    public function handle(callable $callback, int $attempts = 3): mixed
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $callback();
            } catch (QueryException $exception) {
                if (! $this->shouldRetry($exception) || $attempt === $attempts) {
                    throw $exception;
                }

                usleep(self::RETRY_SLEEP_MICROSECONDS * $attempt);
            }
        }

        throw new RuntimeException('Database migration transaction retries were exhausted.');
    }

    public function shouldRetry(QueryException $exception): bool
    {
        return in_array(
            (int) ($exception->errorInfo[1] ?? 0),
            self::RETRYABLE_ERROR_CODES,
            true,
        );
    }
}
