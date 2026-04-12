<?php

namespace App\Console\Commands;

use App\Actions\Database\MigrateSourceDatabaseAction;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class ImdbMigrateSourceDatabaseCommand extends Command implements Isolatable
{
    private const PROGRESS_BAR_FORMAT = ' %current%/%max% [%bar%] %percent:3s%% | elapsed: %elapsed:6s% | ETA: %remaining:6s% | %message%';

    private ?ProgressBar $progressBar = null;

    protected $signature = 'imdb:migrate-source-database
        {--credentials-file=/Volumes/Video/web/imdb/database_logins.txt : Path to the source database credentials file}
        {--source-driver=mysql : Source database driver (mysql, mariadb, sqlite)}
        {--source-port=3306 : Source database port for MySQL-compatible drivers}
        {--table=* : Restrict migration to one or more shared tables}
        {--batch-size=1000 : Number of rows to copy per batch}
        {--retry-attempts=3 : Retry attempts for retryable target write failures}
        {--reset-progress : Clear saved resume state for the selected tables before migrating}';

    protected $description = 'Copy shared tables from a source database into the current target database using resumable cursor checkpoints.';

    public function __construct(
        private readonly MigrateSourceDatabaseAction $migrateSourceDatabaseAction,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $retryAttempts = (int) $this->option('retry-attempts');

        try {
            $this->info('Starting IMDb source database migration...');

            $result = $this->migrateSourceDatabaseAction->handle(
                credentialsFile: (string) $this->option('credentials-file'),
                sourceDriver: (string) $this->option('source-driver'),
                sourcePort: (int) $this->option('source-port'),
                tables: $this->option('table'),
                batchSize: $batchSize,
                retryAttempts: $retryAttempts,
                resetProgress: (bool) $this->option('reset-progress'),
                progress: fn (array $event): mixed => $this->renderProgressEvent($event),
            );

            $this->newLine();
            $this->line(sprintf(
                'Migrated %d table(s) from [%s] into [%s].',
                count($result['tables']),
                $result['source_connection'],
                $result['target_connection'],
            ));

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Source connection', $result['source_connection']],
                    ['Target connection', $result['target_connection']],
                    ['Started at', $this->formatTimestamp($result['started_at'])],
                    ['Finished at', $this->formatTimestamp($result['finished_at'])],
                    ['Duration', $this->formatDuration($result['duration_seconds'])],
                    ['Tables done / estimated', $this->formatPair($result['completed_tables'], $result['total_tables'])],
                    ['Rows copied / estimated', $this->formatPair($result['rows_copied'], $result['estimated_rows'])],
                    ['Average throughput', $this->formatThroughput($result['rows_copied'], $result['duration_seconds'])],
                    ['Batch size', $this->formatInteger($batchSize)],
                    ['Retry attempts', $this->formatInteger($retryAttempts)],
                ],
            );

            $this->table(
                ['Table', 'Done / Estimated', 'Progress', 'Status', 'Duration', 'Last cursor'],
                collect($result['tables'])
                    ->map(fn (array $report): array => [
                        $report['table'],
                        $this->formatPair($report['rows_copied'], $report['estimated_rows']),
                        $this->formatPercentage($report['progress_percentage']),
                        $report['status'],
                        $this->formatDuration($report['duration_seconds']),
                        $this->formatCursor($report['last_cursor']),
                    ])
                    ->all(),
            );

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            report($throwable);

            if ($this->progressBar !== null) {
                $this->progressBar->clear();
                $this->progressBar = null;
                $this->newLine();
            }

            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderProgressEvent(array $event): void
    {
        match ((string) ($event['event'] ?? '')) {
            'start' => $this->renderStartEvent($event),
            'table_start' => $this->renderTableStartEvent($event),
            'batch_processed' => $this->renderBatchProcessedEvent($event),
            'table_completed' => $this->renderTableCompletedEvent($event),
            'finish' => $this->renderFinishEvent($event),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderStartEvent(array $event): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Source connection', (string) $event['source_connection']],
                ['Target connection', (string) $event['target_connection']],
                ['Started at', $this->formatTimestamp((string) $event['started_at'])],
                ['Tables done / estimated', $this->formatPair((int) $event['completed_tables'], (int) $event['total_tables'])],
                ['Rows copied / estimated', $this->formatPair((int) $event['rows_copied'], (int) $event['estimated_rows'])],
                ['Batch size', $this->formatInteger((int) $event['batch_size'])],
                ['Retry attempts', $this->formatInteger((int) $event['retry_attempts'])],
            ],
        );

        if ((int) $event['estimated_rows'] <= 0) {
            return;
        }

        ProgressBar::setFormatDefinition('imdb-migration', self::PROGRESS_BAR_FORMAT);

        $this->progressBar = $this->output->createProgressBar((int) $event['estimated_rows']);
        $this->progressBar->setFormat('imdb-migration');
        $this->progressBar->setMessage('waiting for first batch');
        $this->progressBar->start();
        $this->progressBar->setProgress(min((int) $event['rows_copied'], (int) $event['estimated_rows']));
        $this->progressBar->display();
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderTableStartEvent(array $event): void
    {
        $this->writeProgressLine(sprintf(
            'Table %d/%d: %s | resumed %s rows | status=%s',
            (int) $event['table_position'],
            (int) $event['total_tables'],
            (string) $event['table'],
            $this->formatPair((int) $event['rows_copied'], (int) $event['estimated_rows']),
            (string) $event['status'],
        ));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderBatchProcessedEvent(array $event): void
    {
        if ($this->progressBar === null) {
            $this->writeProgressLine(sprintf(
                'Progress %s | table %s',
                $this->formatPercentage((float) $event['overall_progress_percentage']),
                (string) $event['table'],
            ));

            return;
        }

        $this->progressBar->setMessage(sprintf(
            'table %d/%d %s | table rows %s (%s) | tables %s done | batch +%s | cursor %s',
            (int) $event['table_position'],
            (int) $event['total_tables'],
            (string) $event['table'],
            $this->formatPair((int) $event['rows_copied'], (int) $event['estimated_rows']),
            $this->formatPercentage((float) $event['progress_percentage']),
            $this->formatPair((int) $event['completed_tables'], (int) $event['total_tables']),
            $this->formatInteger((int) $event['batch_rows']),
            $this->formatCursor($event['last_cursor']),
        ));
        $this->progressBar->setProgress(min(
            (int) $event['overall_rows_copied'],
            (int) $event['overall_estimated_rows'],
        ));
        $this->progressBar->display();
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderTableCompletedEvent(array $event): void
    {
        $this->writeProgressLine(sprintf(
            'Completed table %d/%d: %s | rows %s | progress %s | duration %s',
            (int) $event['table_position'],
            (int) $event['total_tables'],
            (string) $event['table'],
            $this->formatPair((int) $event['rows_copied'], (int) $event['estimated_rows']),
            $this->formatPercentage((float) $event['progress_percentage']),
            $this->formatDuration($event['duration_seconds']),
        ));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderFinishEvent(array $event): void
    {
        if ($this->progressBar !== null) {
            $this->progressBar->setMessage(sprintf(
                'complete | tables %s | rows %s',
                $this->formatPair((int) $event['completed_tables'], (int) $event['total_tables']),
                $this->formatPair((int) $event['rows_copied'], (int) $event['estimated_rows']),
            ));
            $this->progressBar->setProgress(min(
                (int) $event['rows_copied'],
                (int) $event['estimated_rows'],
            ));
            $this->progressBar->finish();
            $this->progressBar = null;
            $this->newLine();
        }
    }

    private function writeProgressLine(string $message): void
    {
        if ($this->progressBar !== null) {
            $this->progressBar->clear();
        }

        $this->line($message);

        if ($this->progressBar !== null) {
            $this->progressBar->display();
            $this->newLine();
        }
    }

    private function formatPair(int $done, int $estimated): string
    {
        return sprintf('%s / %s', $this->formatInteger($done), $this->formatInteger($estimated));
    }

    private function formatInteger(int $value): string
    {
        return number_format($value);
    }

    private function formatPercentage(float $value): string
    {
        return number_format($value, 2).'%';
    }

    private function formatCursor(mixed $cursor): string
    {
        if (! is_array($cursor) || $cursor === []) {
            return 'n/a';
        }

        return (string) json_encode($cursor, JSON_THROW_ON_ERROR);
    }

    private function formatDuration(mixed $seconds): string
    {
        if (! is_numeric($seconds)) {
            return 'n/a';
        }

        $totalSeconds = max((int) round((float) $seconds), 0);
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $remainingSeconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    private function formatTimestamp(?string $timestamp): string
    {
        if ($timestamp === null || $timestamp === '') {
            return 'n/a';
        }

        return Carbon::parse($timestamp)->toDateTimeString();
    }

    private function formatThroughput(int $rowsCopied, float $durationSeconds): string
    {
        if ($durationSeconds <= 0.0) {
            return 'n/a';
        }

        return number_format($rowsCopied / $durationSeconds, 2).' rows/s';
    }
}
