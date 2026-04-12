<?php

namespace App\Actions\Database;

use App\Models\DatabaseMigrationState;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MigrateSourceDatabaseAction
{
    public function __construct(
        private readonly RunDatabaseMigrationTransactionAction $runDatabaseMigrationTransactionAction,
    ) {}

    /**
     * @param  list<string>  $tables
     * @param  (callable(array<string, mixed>): void)|null  $progress
     * @return array{
     *     source_connection: string,
     *     target_connection: string,
     *     total_tables: int,
     *     completed_tables: int,
     *     estimated_rows: int,
     *     rows_copied: int,
     *     batch_size: int,
     *     retry_attempts: int,
     *     started_at: string,
     *     finished_at: string,
     *     duration_seconds: float,
     *     tables: list<array{
     *         table: string,
     *         rows_copied: int,
     *         estimated_rows: int,
     *         progress_percentage: float,
     *         status: string,
     *         last_cursor: array<string, mixed>|null,
     *         started_at: string|null,
     *         completed_at: string|null,
     *         duration_seconds: float|null
     *     }>
     * }
     */
    public function handle(
        string $credentialsFile,
        string $sourceDriver = 'mysql',
        int $sourcePort = 3306,
        array $tables = [],
        int $batchSize = 1000,
        int $retryAttempts = 3,
        bool $resetProgress = false,
        ?callable $progress = null,
    ): array {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('The batch size must be at least 1.');
        }

        if ($retryAttempts < 1) {
            throw new InvalidArgumentException('The retry attempts value must be at least 1.');
        }

        $sourceCredentials = $this->parseCredentialsFile($credentialsFile);
        $sourceConnection = 'database_migration_source';
        $targetConnection = (string) config('database.default');

        Config::set(
            "database.connections.{$sourceConnection}",
            $this->buildConnectionConfig($sourceDriver, $sourcePort, $sourceCredentials),
        );

        DB::purge($sourceConnection);

        $sourceSchema = Schema::connection($sourceConnection);
        $targetSchema = Schema::connection($targetConnection);

        $selectedTables = $this->resolveTables(
            sourceSchema: $sourceSchema,
            targetSchema: $targetSchema,
            requestedTables: $tables,
        );

        if ($resetProgress) {
            DatabaseMigrationState::query()
                ->where('source_driver', $sourceDriver)
                ->where('source_host', $sourceCredentials['host'])
                ->where('source_database', $sourceCredentials['database'])
                ->whereIn('table_name', $selectedTables)
                ->delete();
        }

        $tableMetadata = $this->resolveTableMetadata(
            sourceConnection: $sourceConnection,
            tables: $selectedTables,
            sourceDriver: $sourceDriver,
            sourceHost: $sourceCredentials['host'],
            sourceDatabase: $sourceCredentials['database'],
        );
        $startedAt = now();
        $startedAtTimestamp = microtime(true);
        $estimatedRows = (int) $tableMetadata->sum('estimated_rows');
        $completedTables = (int) $tableMetadata
            ->filter(fn (array $metadata): bool => $metadata['status'] === 'completed')
            ->count();
        $initialRowsCopied = (int) $tableMetadata->sum('rows_copied');
        $rowsCopiedThisRun = 0;

        $this->dispatchProgress($progress, [
            'event' => 'start',
            'source_connection' => $sourceConnection,
            'target_connection' => $targetConnection,
            'total_tables' => count($selectedTables),
            'completed_tables' => $completedTables,
            'estimated_rows' => $estimatedRows,
            'rows_copied' => $initialRowsCopied,
            'batch_size' => $batchSize,
            'retry_attempts' => $retryAttempts,
            'started_at' => $startedAt->toIso8601String(),
        ]);

        $targetSchema->disableForeignKeyConstraints();

        try {
            $reports = [];

            foreach ($tableMetadata->values() as $index => $metadata) {
                $table = $metadata['table'];
                $tablePosition = $index + 1;
                $stateWasCompleted = $metadata['status'] === 'completed';

                $this->dispatchProgress($progress, [
                    'event' => 'table_start',
                    'table' => $table,
                    'table_position' => $tablePosition,
                    'total_tables' => count($selectedTables),
                    'completed_tables' => $completedTables,
                    'estimated_rows' => $metadata['estimated_rows'],
                    'rows_copied' => $metadata['rows_copied'],
                    'progress_percentage' => $this->progressPercentage(
                        $metadata['rows_copied'],
                        $metadata['estimated_rows'],
                    ),
                    'last_cursor' => $metadata['last_cursor'],
                    'status' => $metadata['status'],
                ]);

                $report = $this->migrateTable(
                    sourceConnection: $sourceConnection,
                    targetConnection: $targetConnection,
                    sourceSchema: $sourceSchema,
                    targetSchema: $targetSchema,
                    table: $table,
                    batchSize: $batchSize,
                    retryAttempts: $retryAttempts,
                    sourceDriver: $sourceDriver,
                    sourceHost: $sourceCredentials['host'],
                    sourceDatabase: $sourceCredentials['database'],
                    estimatedRows: $metadata['estimated_rows'],
                    progress: function (array $event) use (
                        $progress,
                        $table,
                        $tablePosition,
                        $selectedTables,
                        $completedTables,
                        $startedAtTimestamp,
                        $estimatedRows,
                        $initialRowsCopied,
                        &$rowsCopiedThisRun,
                    ): void {
                        $rowsCopiedThisRun += (int) ($event['batch_rows'] ?? 0);
                        $overallRowsCopied = $initialRowsCopied + $rowsCopiedThisRun;
                        $elapsedSeconds = max(microtime(true) - $startedAtTimestamp, 0.001);
                        $remainingRows = max($estimatedRows - $overallRowsCopied, 0);
                        $throughput = $rowsCopiedThisRun > 0
                            ? $rowsCopiedThisRun / $elapsedSeconds
                            : null;

                        $this->dispatchProgress($progress, [
                            'event' => 'batch_processed',
                            'table' => $table,
                            'table_position' => $tablePosition,
                            'total_tables' => count($selectedTables),
                            'completed_tables' => $completedTables,
                            'estimated_rows' => $event['estimated_rows'],
                            'rows_copied' => $event['rows_copied'],
                            'progress_percentage' => $this->progressPercentage(
                                (int) $event['rows_copied'],
                                (int) $event['estimated_rows'],
                            ),
                            'batch_rows' => $event['batch_rows'],
                            'last_cursor' => $event['last_cursor'],
                            'status' => $event['status'],
                            'overall_estimated_rows' => $estimatedRows,
                            'overall_rows_copied' => $overallRowsCopied,
                            'overall_progress_percentage' => $this->progressPercentage(
                                $overallRowsCopied,
                                $estimatedRows,
                            ),
                            'elapsed_seconds' => $elapsedSeconds,
                            'eta_seconds' => $throughput === null || $throughput <= 0.0
                                ? null
                                : $remainingRows / $throughput,
                        ]);
                    },
                );

                $reports[] = $report;

                if (! $stateWasCompleted && $report['status'] === 'completed') {
                    $completedTables++;
                }

                $this->dispatchProgress($progress, [
                    'event' => 'table_completed',
                    'table' => $table,
                    'table_position' => $tablePosition,
                    'total_tables' => count($selectedTables),
                    'completed_tables' => $completedTables,
                    'estimated_rows' => $report['estimated_rows'],
                    'rows_copied' => $report['rows_copied'],
                    'progress_percentage' => $report['progress_percentage'],
                    'last_cursor' => $report['last_cursor'],
                    'status' => $report['status'],
                    'duration_seconds' => $report['duration_seconds'],
                ]);
            }
        } finally {
            $targetSchema->enableForeignKeyConstraints();
            DB::disconnect($sourceConnection);
        }

        $finishedAt = now();
        $rowsCopied = (int) collect($reports)->sum('rows_copied');
        $durationSeconds = round(microtime(true) - $startedAtTimestamp, 3);

        $this->dispatchProgress($progress, [
            'event' => 'finish',
            'source_connection' => $sourceConnection,
            'target_connection' => $targetConnection,
            'total_tables' => count($selectedTables),
            'completed_tables' => (int) collect($reports)
                ->filter(fn (array $report): bool => $report['status'] === 'completed')
                ->count(),
            'estimated_rows' => $estimatedRows,
            'rows_copied' => $rowsCopied,
            'duration_seconds' => $durationSeconds,
            'finished_at' => $finishedAt->toIso8601String(),
        ]);

        return [
            'source_connection' => $sourceConnection,
            'target_connection' => $targetConnection,
            'total_tables' => count($selectedTables),
            'completed_tables' => (int) collect($reports)
                ->filter(fn (array $report): bool => $report['status'] === 'completed')
                ->count(),
            'estimated_rows' => $estimatedRows,
            'rows_copied' => $rowsCopied,
            'batch_size' => $batchSize,
            'retry_attempts' => $retryAttempts,
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => $finishedAt->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'tables' => $reports,
        ];
    }

    /**
     * @param  (callable(array<string, mixed>): void)|null  $progress
     * @return array{
     *     table: string,
     *     rows_copied: int,
     *     estimated_rows: int,
     *     progress_percentage: float,
     *     status: string,
     *     last_cursor: array<string, mixed>|null,
     *     started_at: string|null,
     *     completed_at: string|null,
     *     duration_seconds: float|null
     * }
     */
    private function migrateTable(
        string $sourceConnection,
        string $targetConnection,
        Builder $sourceSchema,
        Builder $targetSchema,
        string $table,
        int $batchSize,
        int $retryAttempts,
        string $sourceDriver,
        ?string $sourceHost,
        string $sourceDatabase,
        int $estimatedRows,
        ?callable $progress = null,
    ): array {
        $cursorColumns = $this->resolveCursorColumns($sourceSchema, $table);
        $transferColumns = $this->resolveTransferColumns($sourceSchema, $targetSchema, $table);
        $identity = [
            'source_driver' => $sourceDriver,
            'source_host' => $sourceHost,
            'source_database' => $sourceDatabase,
            'table_name' => $table,
        ];
        $state = DatabaseMigrationState::query()->firstOrNew($identity);

        if ($state->status === 'completed') {
            return $this->stateReport($table, $state, $estimatedRows);
        }

        $state->fill([
            'cursor_columns' => $cursorColumns,
            'rows_copied' => $state->rows_copied ?? 0,
            'status' => $state->status ?? 'pending',
            'started_at' => $state->started_at ?? now(),
            'completed_at' => null,
            'last_error' => null,
        ]);
        $state->save();

        while (true) {
            $rows = $this->fetchBatch(
                sourceConnection: $sourceConnection,
                table: $table,
                transferColumns: $transferColumns,
                cursorColumns: $cursorColumns,
                lastCursor: $state->last_cursor,
                batchSize: $batchSize,
            );

            if ($rows === []) {
                $state->forceFill([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'last_error' => null,
                ])->save();

                return $this->stateReport($table, $state->fresh() ?? $state, $estimatedRows);
            }

            $lastCursor = $this->extractCursor(end($rows), $cursorColumns);
            $updateColumns = array_values(array_diff($transferColumns, $cursorColumns));

            try {
                $this->runDatabaseMigrationTransactionAction->handle(function () use (
                    $targetConnection,
                    $table,
                    $rows,
                    $cursorColumns,
                    $updateColumns,
                    $state,
                    $lastCursor,
                ): void {
                    DB::connection($targetConnection)->transaction(function () use (
                        $targetConnection,
                        $table,
                        $rows,
                        $cursorColumns,
                        $updateColumns,
                        $state,
                        $lastCursor,
                    ): void {
                        $targetQuery = DB::connection($targetConnection)->table($table);

                        if ($updateColumns === []) {
                            $targetQuery->insertOrIgnore($rows);
                        } else {
                            $targetQuery->upsert($rows, $cursorColumns, $updateColumns);
                        }

                        $state->forceFill([
                            'cursor_columns' => $cursorColumns,
                            'last_cursor' => $lastCursor,
                            'rows_copied' => (int) $state->rows_copied + count($rows),
                            'status' => 'running',
                            'last_error' => null,
                            'started_at' => $state->started_at ?? now(),
                            'completed_at' => null,
                        ])->save();
                    });
                }, $retryAttempts);
            } catch (\Throwable $throwable) {
                $state->forceFill([
                    'status' => 'failed',
                    'last_error' => Str::limit($throwable->getMessage(), 65535, ''),
                    'completed_at' => null,
                ])->save();

                throw $throwable;
            }

            $state = $state->fresh() ?? $state;

            $this->dispatchProgress($progress, [
                'batch_rows' => count($rows),
                'rows_copied' => (int) $state->rows_copied,
                'estimated_rows' => $estimatedRows,
                'last_cursor' => $state->last_cursor,
                'status' => (string) $state->status,
            ]);
        }
    }

    /**
     * @param  list<string>  $transferColumns
     * @param  list<string>  $cursorColumns
     * @param  array<string, mixed>|null  $lastCursor
     * @return list<array<string, mixed>>
     */
    private function fetchBatch(
        string $sourceConnection,
        string $table,
        array $transferColumns,
        array $cursorColumns,
        ?array $lastCursor,
        int $batchSize,
    ): array {
        $query = DB::connection($sourceConnection)
            ->table($table)
            ->select($transferColumns);

        foreach ($cursorColumns as $column) {
            $query->orderBy($column);
        }

        if ($lastCursor !== null) {
            $query->where(function ($outerQuery) use ($cursorColumns, $lastCursor): void {
                foreach ($cursorColumns as $index => $column) {
                    $outerQuery->orWhere(function ($branchQuery) use ($cursorColumns, $lastCursor, $index, $column): void {
                        for ($priorIndex = 0; $priorIndex < $index; $priorIndex++) {
                            $priorColumn = $cursorColumns[$priorIndex];
                            $branchQuery->where($priorColumn, '=', $lastCursor[$priorColumn]);
                        }

                        $branchQuery->where($column, '>', $lastCursor[$column]);
                    });
                }
            });
        }

        return $query
            ->limit($batchSize)
            ->get()
            ->map(fn (object $row): array => Arr::only((array) $row, $transferColumns))
            ->all();
    }

    /**
     * @return array{host: string|null, database: string, username: string|null, password: string|null}
     */
    private function parseCredentialsFile(string $credentialsFile): array
    {
        if (! File::exists($credentialsFile)) {
            throw new InvalidArgumentException(sprintf('The credentials file [%s] does not exist.', $credentialsFile));
        }

        $lines = collect(preg_split('/\R/', (string) File::get($credentialsFile)) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->values();

        $credentials = [
            'host' => $this->valueAfterLabel($lines, 'Hostname'),
            'database' => $this->valueAfterLabel($lines, 'Database'),
            'username' => $this->valueAfterLabel($lines, 'Username'),
            'password' => $this->valueAfterLabel($lines, 'Password'),
        ];

        if ($credentials['database'] === null || $credentials['database'] === '') {
            throw new InvalidArgumentException(sprintf('The credentials file [%s] is missing a Database value.', $credentialsFile));
        }

        return $credentials;
    }

    /**
     * @param  array{host: string|null, database: string, username: string|null, password: string|null}  $sourceCredentials
     * @return array<string, mixed>
     */
    private function buildConnectionConfig(string $sourceDriver, int $sourcePort, array $sourceCredentials): array
    {
        if ($sourceDriver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => $sourceCredentials['database'],
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        if (! in_array($sourceDriver, ['mysql', 'mariadb'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported source driver [%s].', $sourceDriver));
        }

        if ($sourceCredentials['host'] === null || $sourceCredentials['host'] === '') {
            throw new InvalidArgumentException('A Hostname value is required for MySQL-compatible source connections.');
        }

        return [
            'driver' => $sourceDriver,
            'host' => $sourceCredentials['host'],
            'port' => $sourcePort,
            'database' => $sourceCredentials['database'],
            'username' => $sourceCredentials['username'],
            'password' => $sourceCredentials['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
        ];
    }

    /**
     * @param  list<string>  $requestedTables
     * @return list<string>
     */
    private function resolveTables(Builder $sourceSchema, Builder $targetSchema, array $requestedTables): array
    {
        $sourceTables = $this->tableListing($sourceSchema);
        $targetTables = $this->tableListing($targetSchema);
        $sharedTables = $sourceTables
            ->intersect($targetTables)
            ->reject(fn (string $table): bool => in_array($table, ['database_migration_states', 'migrations'], true))
            ->values();

        if ($requestedTables === []) {
            return $sharedTables->all();
        }

        $selectedTables = collect($requestedTables)
            ->map(fn (string $table): string => trim($table))
            ->filter()
            ->unique()
            ->values();

        $missingTables = $selectedTables
            ->reject(fn (string $table): bool => $sharedTables->contains($table))
            ->values();

        if ($missingTables->isNotEmpty()) {
            throw new InvalidArgumentException(
                'The following tables do not exist on both source and target connections: '.$missingTables->implode(', '),
            );
        }

        return $selectedTables->all();
    }

    private function tableListing(Builder $schema): Collection
    {
        return collect($schema->getTables())
            ->map(function (mixed $table): string {
                if (is_array($table)) {
                    return (string) ($table['name'] ?? $table['table_name'] ?? $table['TABLE_NAME'] ?? '');
                }

                if (is_object($table)) {
                    return (string) ($table->name ?? $table->table_name ?? $table->TABLE_NAME ?? '');
                }

                return (string) $table;
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<string>  $tables
     * @return Collection<int, array{
     *     table: string,
     *     estimated_rows: int,
     *     rows_copied: int,
     *     status: string,
     *     last_cursor: array<string, mixed>|null
     * }>
     */
    private function resolveTableMetadata(
        string $sourceConnection,
        array $tables,
        string $sourceDriver,
        ?string $sourceHost,
        string $sourceDatabase,
    ): Collection {
        $states = DatabaseMigrationState::query()
            ->where('source_driver', $sourceDriver)
            ->where('source_host', $sourceHost)
            ->where('source_database', $sourceDatabase)
            ->whereIn('table_name', $tables)
            ->get()
            ->keyBy('table_name');

        return collect($tables)
            ->map(function (string $table) use ($sourceConnection, $states): array {
                /** @var DatabaseMigrationState|null $state */
                $state = $states->get($table);

                return [
                    'table' => $table,
                    'estimated_rows' => $this->estimateTableRows($sourceConnection, $table),
                    'rows_copied' => (int) ($state?->rows_copied ?? 0),
                    'status' => (string) ($state?->status ?? 'pending'),
                    'last_cursor' => $state?->last_cursor,
                ];
            })
            ->values();
    }

    private function estimateTableRows(string $sourceConnection, string $table): int
    {
        return (int) DB::connection($sourceConnection)
            ->table($table)
            ->count();
    }

    /**
     * @return list<string>
     */
    private function resolveCursorColumns(Builder $schema, string $table): array
    {
        $indexes = collect($schema->getIndexes($table));

        $primaryIndex = $indexes->first(
            fn (mixed $index): bool => (bool) data_get($index, 'primary', false)
                || (bool) data_get($index, 'is_primary', false)
                || data_get($index, 'name') === 'primary',
        );

        $primaryColumns = $this->normalizeColumns(data_get($primaryIndex, 'columns', []));

        if ($primaryColumns !== []) {
            return $primaryColumns;
        }

        $uniqueIndex = $indexes->first(
            fn (mixed $index): bool => (bool) data_get($index, 'unique', false)
                || (bool) data_get($index, 'is_unique', false),
        );

        $uniqueColumns = $this->normalizeColumns(data_get($uniqueIndex, 'columns', []));

        if ($uniqueColumns !== []) {
            return $uniqueColumns;
        }

        throw new RuntimeException(sprintf(
            'Table [%s] requires a primary or unique index for resumable migration.',
            $table,
        ));
    }

    /**
     * @return list<string>
     */
    private function resolveTransferColumns(Builder $sourceSchema, Builder $targetSchema, string $table): array
    {
        $sourceColumns = collect($sourceSchema->getColumns($table))
            ->map(fn (array $column): string => (string) $column['name'])
            ->values();
        $targetColumns = collect($targetSchema->getColumns($table))
            ->map(fn (array $column): string => (string) $column['name'])
            ->values();

        $commonColumns = $sourceColumns
            ->intersect($targetColumns)
            ->values();

        if ($commonColumns->isEmpty()) {
            throw new RuntimeException(sprintf(
                'Table [%s] does not share any transferable columns across the source and target schemas.',
                $table,
            ));
        }

        return $commonColumns->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $cursorColumns
     * @return array<string, mixed>
     */
    private function extractCursor(array $row, array $cursorColumns): array
    {
        return collect($cursorColumns)
            ->mapWithKeys(fn (string $column): array => [$column => $row[$column]])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function normalizeColumns(mixed $columns): array
    {
        return collect($columns)
            ->map(fn (mixed $column): string => (string) $column)
            ->filter()
            ->values()
            ->all();
    }

    private function valueAfterLabel(Collection $lines, string $label): ?string
    {
        $index = $lines->search(fn (string $line): bool => Str::lower($line) === Str::lower($label.':'));

        if ($index === false) {
            return null;
        }

        return collect($lines->slice($index + 1))
            ->first(fn (string $line): bool => $line !== '');
    }

    /**
     * @return array{
     *     table: string,
     *     rows_copied: int,
     *     estimated_rows: int,
     *     progress_percentage: float,
     *     status: string,
     *     last_cursor: array<string, mixed>|null,
     *     started_at: string|null,
     *     completed_at: string|null,
     *     duration_seconds: float|null
     * }
     */
    private function stateReport(string $table, DatabaseMigrationState $state, int $estimatedRows): array
    {
        return [
            'table' => $table,
            'rows_copied' => (int) $state->rows_copied,
            'estimated_rows' => $estimatedRows,
            'progress_percentage' => $this->progressPercentage((int) $state->rows_copied, $estimatedRows),
            'status' => (string) $state->status,
            'last_cursor' => $state->last_cursor,
            'started_at' => $state->started_at?->toIso8601String(),
            'completed_at' => $state->completed_at?->toIso8601String(),
            'duration_seconds' => $state->started_at === null
                ? null
                : round(($state->completed_at ?? now())->diffInMilliseconds($state->started_at) / 1000, 3),
        ];
    }

    private function progressPercentage(int $rowsCopied, int $estimatedRows): float
    {
        if ($estimatedRows <= 0) {
            return 100.0;
        }

        return round(min(($rowsCopied / $estimatedRows) * 100, 100), 2);
    }

    /**
     * @param  (callable(array<string, mixed>): void)|null  $progress
     * @param  array<string, mixed>  $event
     */
    private function dispatchProgress(?callable $progress, array $event): void
    {
        if ($progress === null) {
            return;
        }

        $progress($event);
    }
}
