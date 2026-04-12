<?php

namespace Tests\Unit\Database;

use App\Actions\Database\RunDatabaseMigrationTransactionAction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class RunDatabaseMigrationTransactionActionTest extends TestCase
{
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    public function test_it_retries_retryable_query_exceptions_until_the_callback_succeeds(): void
    {
        $attempts = 0;

        $result = app(RunDatabaseMigrationTransactionAction::class)->handle(function () use (&$attempts): string {
            $attempts++;

            if ($attempts < 3) {
                $exception = new QueryException(
                    'imdb_mysql',
                    'insert into genres (id, name) values (?, ?)',
                    [2, 'Drama'],
                    new \RuntimeException('Lock wait timeout exceeded'),
                );

                $exception->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded'];

                throw $exception;
            }

            return 'completed';
        });

        $this->assertSame('completed', $result);
        $this->assertSame(3, $attempts);
    }

    public function test_it_does_not_retry_non_retryable_query_exceptions(): void
    {
        $this->expectException(QueryException::class);

        app(RunDatabaseMigrationTransactionAction::class)->handle(function (): never {
            $exception = new QueryException(
                'imdb_mysql',
                'insert into genres (id, name) values (?, ?)',
                [2, 'Drama'],
                new \RuntimeException('Syntax error or access violation'),
            );

            $exception->errorInfo = ['HY000', 1064, 'Syntax error or access violation'];

            throw $exception;
        });
    }
}
