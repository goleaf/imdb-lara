<?php

namespace App\Console\Commands;

use App\Actions\Import\CrawlImdbGraphAction;
use App\Actions\Import\EnsureLegacyImportPipelineIsEnabledAction;
use App\Models\Person;
use App\Models\Title;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ImdbImportTitlesFrontierCommand extends Command
{
    private const MAX_SYNC_PASSES = 5;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imdb:import-titles-frontier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically import the IMDb titles frontier, interests frontier, and star meter graph into the database with detailed live output.';

    private bool $traceEnabled = true;

    /**
     * @var array{type: string, imdb_id: string, source: string|null}|null
     */
    private ?array $activeTraceNode = null;

    private int $activeNodeWriteQueryCount = 0;

    /**
     * @var array<string, int>
     */
    private array $activeNodeWriteTables = [];

    public function __construct(
        private readonly CrawlImdbGraphAction $crawlImdbGraphAction,
        private readonly EnsureLegacyImportPipelineIsEnabledAction $ensureLegacyImportPipelineIsEnabledAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->ensureLegacyImportPipelineIsEnabledAction->handle();
        } catch (RuntimeException $runtimeException) {
            $this->error($runtimeException->getMessage());

            return self::FAILURE;
        }

        $this->traceEnabled = true;
        $this->bootSqlTraceListener();

        $this->newLine();
        $this->line('<fg=cyan;options=bold>IMDb automatic recursive import</>');
        $this->line('Bootstrapping titles, interests, and star meter frontiers with live SQL, endpoint, and verification diagnostics.');
        $this->renderTraceSessionContext();

        $result = null;
        $passesRun = 0;

        for ($pass = 1; $pass <= self::MAX_SYNC_PASSES; $pass++) {
            $passesRun = $pass;
            $this->resetLiveTraceState();
            $this->newLine();
            $this->line(sprintf('<fg=magenta;options=bold>Pass %d of %d</>', $pass, self::MAX_SYNC_PASSES));

            $result = $this->crawlImdbGraphAction->handle(
                [],
                [
                    'bootstrap_titles' => true,
                    'bootstrap_interests' => true,
                    'bootstrap_star_meter' => true,
                    'fill_missing_only' => true,
                    'max_nodes' => 0,
                ],
                fn (array $event): mixed => $this->renderProgressEvent($event),
            );

            $this->renderRunSummary($result, sprintf('Pass %d summary', $pass));

            if ($this->syncHasStabilized($result)) {
                $this->line(sprintf('<fg=green>Import stabilized after pass %d.</>', $pass));

                break;
            }
        }

        if (! is_array($result)) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>Overall summary</>');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Passes run', (string) $passesRun],
                ['Stable sync', $this->syncHasStabilized($result) ? '<fg=green>yes</>' : '<fg=red>no</>'],
                ['Last pass report', $this->displayPath((string) data_get($result, 'report_path', 'n/a'))],
            ],
        );

        return (int) data_get($result, 'summary.failed_nodes', 0) > 0 || ! $this->syncHasStabilized($result)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function renderRunSummary(array $result, string $headline): void
    {
        $this->newLine();
        $this->line(sprintf('<fg=green;options=bold>%s</>', $headline));
        $this->table(
            ['Metric', 'Value'],
            [
                ['Frontier pages', (string) data_get($result, 'summary.frontier_pages', 0)],
                ['Visited nodes', (string) data_get($result, 'summary.visited_nodes', 0)],
                ['Processed nodes', (string) data_get($result, 'summary.processed_nodes', 0)],
                ['Failed nodes', $this->colorizeCount((int) data_get($result, 'summary.failed_nodes', 0), 'red')],
                ['New records', $this->colorizeCount((int) data_get($result, 'summary.new_records', 0))],
                ['Filled fields', $this->colorizeCount((int) data_get($result, 'summary.filled_fields', 0))],
                ['New relations', $this->colorizeCount((int) data_get($result, 'summary.new_relations', 0))],
                ['Total DB additions', $this->colorizeCount((int) data_get($result, 'summary.db_total_added', 0))],
                ['Run report', $this->displayPath((string) data_get($result, 'report_path', 'n/a'))],
            ],
        );

        $typeRows = collect(data_get($result, 'summary.by_type', []))
            ->map(fn (array $counts, string $type): array => [
                Str::headline($type),
                (string) data_get($counts, 'processed', 0),
                $this->colorizeCount((int) data_get($counts, 'failed', 0), 'red'),
            ])
            ->values()
            ->all();

        if ($typeRows !== []) {
            $this->table(['Node type', 'Processed', 'Failed'], $typeRows);
        }

        $dbCountRows = collect(data_get($result, 'summary.db_counts', []))
            ->map(fn (int $count, string $key): array => [
                $this->dbCountLabel($key),
                $this->colorizeCount($count),
            ])
            ->sortBy(fn (array $row): string => $row[0])
            ->values()
            ->all();

        if ($dbCountRows !== []) {
            $this->table(['DB target', 'Added'], $dbCountRows);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function syncHasStabilized(array $result): bool
    {
        return (int) data_get($result, 'summary.db_total_added', 0) === 0
            && (int) data_get($result, 'summary.failed_nodes', 0) === 0;
    }

    private function resetLiveTraceState(): void
    {
        $this->activeTraceNode = null;
        $this->activeNodeWriteQueryCount = 0;
        $this->activeNodeWriteTables = [];
    }

    private function renderProgressEvent(array $event): void
    {
        $eventType = data_get($event, 'event');

        if (! is_string($eventType)) {
            return;
        }

        if ($eventType === 'node_start') {
            $node = data_get($event, 'node');

            if (is_array($node)) {
                $this->renderLiveNodeStart(
                    $node,
                    (int) data_get($event, 'queue_size', 0),
                    (int) data_get($event, 'visited_nodes', 0),
                );
            }

            if ($this->traceEnabled && is_array($node)) {
                $this->renderTraceNodeStart(
                    $node,
                    (int) data_get($event, 'queue_size', 0),
                    (int) data_get($event, 'visited_nodes', 0),
                );
            }

            return;
        }

        $report = data_get($event, 'report');

        if (! is_array($report)) {
            return;
        }

        if ($eventType === 'frontier') {
            $this->renderFrontierReport($report);

            return;
        }

        $this->renderNodeReport($report);
    }

    private function renderNodeReport(array $report): void
    {
        $status = (string) data_get($report, 'status', 'processed');
        $nodeType = Str::of((string) data_get($report, 'node_type'))->upper()->toString();
        $headline = sprintf(
            '[%s] %s %s',
            $nodeType,
            (string) data_get($report, 'imdb_id', 'unknown'),
            (string) data_get($report, 'label', ''),
        );

        if ($status !== 'processed') {
            $this->newLine();
            $this->error(trim($headline));
            $this->table(
                ['Problem', 'Value'],
                [
                    ['Status', '<fg=red>failed</>'],
                    ['Source', (string) data_get($report, 'source', 'n/a')],
                    ['Error', (string) data_get($report, 'error', 'Unknown failure.')],
                ],
            );

            return;
        }

        $isNewRecord = (bool) data_get($report, 'import.new_record', false);
        $this->newLine();
        $this->line($isNewRecord
            ? '<fg=green;options=bold>+ '.trim($headline).' [new]</>'
            : '<fg=yellow;options=bold>= '.trim($headline).' [known]</>');
        $this->renderLiveNodeSummary($report);

        $this->table(
            ['Step', 'Value'],
            [
                ['Node type', Str::headline((string) data_get($report, 'node_type'))],
                ['Database record', $isNewRecord ? '<fg=green>created</>' : '<fg=yellow>updated / reused</>'],
                ['Download', (bool) data_get($report, 'download.downloaded', false) ? '<fg=green>fresh JSON</>' : '<fg=yellow>cached JSON</>'],
                ['Source', (string) data_get($report, 'source', 'n/a')],
                ['Queued next nodes', $this->colorizeCount(count(is_array(data_get($report, 'discovered_nodes')) ? data_get($report, 'discovered_nodes') : []), 'cyan')],
            ],
        );

        $existingFields = data_get($report, 'import.existing_fields', []);
        $filledFields = data_get($report, 'import.filled_fields', []);
        $newRelations = data_get($report, 'import.new_relations', []);
        $dbCounts = collect(data_get($report, 'import.db_counts', []))
            ->map(fn (int $count, string $key): array => [
                $this->dbCountLabel($key),
                $this->colorizeCount($count),
            ])
            ->sortBy(fn (array $row): string => $row[0])
            ->values()
            ->all();

        if ($dbCounts !== []) {
            $this->table(['DB target', 'Added'], $dbCounts);
        } else {
            $this->line('<comment>No new DB rows or fields were added for this node.</comment>');
        }

        $detailRows = [];

        if (is_array($existingFields) && $existingFields !== []) {
            $detailRows[] = ['Kept existing', $this->formatList($existingFields)];
        }

        if (is_array($filledFields) && $filledFields !== []) {
            $detailRows[] = ['Filled now', $this->formatList($filledFields)];
        }

        if ($detailRows !== []) {
            $this->table(['Field state', 'Details'], $detailRows);
        }

        $relationRows = collect(is_array($newRelations) ? $newRelations : [])
            ->filter(fn (mixed $values): bool => is_array($values) && $values !== [])
            ->map(fn (array $values, string $relation): array => [
                $this->relationLabel($relation),
                $this->colorizeCount(count($values)),
                $this->formatList($values),
            ])
            ->sortBy(fn (array $row): string => $row[0])
            ->values()
            ->all();

        if ($relationRows !== []) {
            $this->table(['Relation', 'Added', 'Examples'], $relationRows);
        }

        $this->renderEndpointDigest($report);
        $this->renderVerificationDigest($report);

        if ($this->traceEnabled) {
            $this->renderTraceNodeDiagnostics($report);
        }

        $discoveredNodes = data_get($report, 'discovered_nodes', []);

        if (is_array($discoveredNodes) && $discoveredNodes !== []) {
            $queuedRows = collect($discoveredNodes)
                ->groupBy(fn (array $node): string => (string) data_get($node, 'type', 'unknown'))
                ->map(fn ($nodes, string $type): array => [
                    Str::headline($type),
                    $this->colorizeCount($nodes->count(), 'cyan'),
                    $this->formatList($nodes->map(fn (array $node): string => (string) data_get($node, 'imdb_id'))->all()),
                ])
                ->values()
                ->all();

            $this->table(['Queued type', 'Count', 'IMDb IDs'], $queuedRows);
        }
    }

    private function renderTraceSessionContext(): void
    {
        $connection = (string) config('database.default', 'unknown');
        $database = $connection === 'sqlite'
            ? (string) config('database.connections.sqlite.database', '')
            : (string) DB::connection($connection)->getDatabaseName();

        $this->newLine();
        $this->line('<fg=magenta;options=bold>Trace mode enabled</>');
        $this->table(
            ['Trace context', 'Value'],
            [
                ['DB connection', $connection],
                ['Database target', $database !== '' ? $database : 'n/a'],
                ['Fill mode', 'fill only missing values'],
                ['Recursion limit', 'unlimited'],
                ['Bootstraps', 'titles + interests + star meter'],
            ],
        );
    }

    /**
     * @param  array{type: string, imdb_id: string, source: string|null}  $node
     */
    private function renderLiveNodeStart(array $node, int $queueSize, int $visitedNodes): void
    {
        $this->newLine();
        $this->line(sprintf(
            '<fg=blue;options=bold>Starting</> %s %s from %s | queue=%d | visited=%d',
            Str::headline((string) $node['type']),
            (string) $node['imdb_id'],
            (string) ($node['source'] ?? 'n/a'),
            $queueSize,
            $visitedNodes,
        ));
    }

    /**
     * @param  array{type: string, imdb_id: string, source: string|null}  $node
     */
    private function renderTraceNodeStart(array $node, int $queueSize, int $visitedNodes): void
    {
        $this->activeTraceNode = $node;
        $this->activeNodeWriteQueryCount = 0;
        $this->activeNodeWriteTables = [];

        $this->newLine();
        $this->line(sprintf(
            '<fg=magenta;options=bold>Trace start</> %s %s',
            Str::headline((string) $node['type']),
            (string) $node['imdb_id'],
        ));
        $this->table(
            ['Trace step', 'Value'],
            [
                ['Node', (string) $node['imdb_id']],
                ['Source', (string) ($node['source'] ?? 'n/a')],
                ['Queue remaining before processing', (string) $queueSize],
                ['Visited nodes including current', (string) $visitedNodes],
            ],
        );
    }

    private function renderLiveNodeSummary(array $report): void
    {
        $downloaded = (bool) data_get($report, 'download.downloaded', false);
        $dbTotalAdded = (int) data_get($report, 'import.db_total_added', 0);
        $filledFieldCount = count(is_array(data_get($report, 'import.filled_fields')) ? data_get($report, 'import.filled_fields') : []);
        $existingFieldCount = count(is_array(data_get($report, 'import.existing_fields')) ? data_get($report, 'import.existing_fields') : []);
        $newRelationCount = collect(is_array(data_get($report, 'import.new_relations')) ? data_get($report, 'import.new_relations') : [])
            ->sum(fn (mixed $values): int => is_array($values) ? count($values) : 0);

        $this->line(sprintf(
            '  Flow: source=%s | json=%s | db_added=%d | fields_filled=%d | existing_fields=%d | relation_additions=%d',
            (string) data_get($report, 'source', 'n/a'),
            $downloaded ? 'fresh' : 'cached',
            $dbTotalAdded,
            $filledFieldCount,
            $existingFieldCount,
            $newRelationCount,
        ));

        $storagePath = data_get($report, 'download.storage_path');

        if (is_string($storagePath) && trim($storagePath) !== '') {
            $this->line('  Bundle: '.$this->displayPath($storagePath));
        }

        $reportPath = data_get($report, 'report_path');

        if (is_string($reportPath) && trim($reportPath) !== '') {
            $this->line('  Node report: '.$this->displayPath($reportPath));
        }

        $verificationPath = data_get($report, 'verification_path');

        if (is_string($verificationPath) && trim($verificationPath) !== '') {
            $this->line('  Verification file: '.$this->displayPath($verificationPath));
        }
    }

    private function bootSqlTraceListener(): void
    {
        DB::listen(function (QueryExecuted $query): void {
            $sql = trim((string) $query->sql);

            if (! $this->isWriteQuery($sql)) {
                return;
            }

            $operation = $this->traceSqlOperation($sql);
            $table = $this->traceSqlTable($sql);

            $this->activeNodeWriteQueryCount++;

            if ($table !== null) {
                $this->activeNodeWriteTables[$table] = (int) ($this->activeNodeWriteTables[$table] ?? 0) + 1;
            }

            $this->line(sprintf(
                '<fg=magenta>SQL %s</> [%s] %.2f ms %s',
                Str::upper($operation),
                $query->connectionName,
                $query->time,
                $table !== null ? 'table='.$table : 'table=unknown',
            ));

            $columnBindings = $this->traceSqlColumnBindings($sql, $query->bindings);

            if ($columnBindings !== []) {
                foreach ($columnBindings as $column => $value) {
                    $qualifiedColumn = $table !== null ? $table.'.'.$column : $column;
                    $this->line(sprintf('  %s <- %s', $qualifiedColumn, $value));
                }

                return;
            }

            $bindingSummary = collect($query->bindings)
                ->map(fn (mixed $binding): string => $this->formatTraceValue($binding))
                ->implode(', ');

            $this->line('  SQL: '.$sql);

            if ($bindingSummary !== '') {
                $this->line('  Bindings: '.$bindingSummary);
            }
        });
    }

    private function renderTraceNodeDiagnostics(array $report): void
    {
        $artifactDirectory = $this->traceArtifactDirectory($report);
        $model = $this->traceModel(
            (string) data_get($report, 'node_type'),
            (string) data_get($report, 'imdb_id'),
        );

        $this->newLine();
        $this->line('<fg=magenta;options=bold>Trace diagnostics</>');
        $this->table(
            ['Trace artifact', 'Value'],
            [
                ['Source URL', (string) data_get($report, 'download.source_url', 'n/a')],
                ['Saved JSON', (string) data_get($report, 'download.storage_path', 'n/a')],
                ['Artifact directory', $artifactDirectory ?? 'n/a'],
                ['Node report', (string) data_get($report, 'report_path', 'n/a')],
                ['Verification report', (string) data_get($report, 'verification_path', 'n/a')],
                ['Write queries observed', (string) $this->activeNodeWriteQueryCount],
            ],
        );

        if ($this->activeNodeWriteTables !== []) {
            $writeRows = collect($this->activeNodeWriteTables)
                ->sortKeys()
                ->map(fn (int $count, string $table): array => [$table, (string) $count])
                ->values()
                ->all();

            $this->table(['Write table', 'Query count'], $writeRows);
        }

        if ($artifactDirectory !== null) {
            $this->renderTraceEndpointReports((string) data_get($report, 'node_type'), $artifactDirectory, $model);
            $this->renderTraceVerificationReport($artifactDirectory);
        }
    }

    private function renderTraceEndpointReports(string $nodeType, string $artifactDirectory, ?Model $model): void
    {
        $importsDirectory = $artifactDirectory.DIRECTORY_SEPARATOR.'imports';

        if (! File::isDirectory($importsDirectory)) {
            $this->line('<comment>No endpoint import reports were written for this node.</comment>');

            return;
        }

        $files = collect(File::files($importsDirectory))
            ->sortBy(fn (\SplFileInfo $file): string => $file->getFilename())
            ->values();

        foreach ($files as $file) {
            $report = $this->decodeJsonFile($file->getPathname());

            if ($report === null) {
                continue;
            }

            $endpoint = (string) data_get($report, 'endpoint', pathinfo($file->getFilename(), PATHINFO_FILENAME));
            $this->line(sprintf(
                '<fg=magenta>Endpoint %s</> -> %s',
                $endpoint,
                $this->traceTargetTables($nodeType, $endpoint),
            ));

            $addedFieldMap = data_get($report, 'added_field_map', []);

            if (is_array($addedFieldMap) && $addedFieldMap !== []) {
                foreach ($addedFieldMap as $field => $label) {
                    $this->line(sprintf(
                        '  field %s (%s) = %s',
                        $this->traceFieldTarget($nodeType, (string) $field),
                        (string) $label,
                        $this->traceFieldValue($model, (string) $field),
                    ));
                }
            } else {
                $this->line('  field additions: 0');
            }

            $addedRelations = data_get($report, 'added_relations', []);

            if (is_array($addedRelations) && $addedRelations !== []) {
                foreach ($addedRelations as $relation => $values) {
                    if (! is_array($values)) {
                        continue;
                    }

                    $this->line(sprintf(
                        '  relation %s -> %s | count=%d | values=%s',
                        $relation,
                        $this->traceRelationTargets($nodeType, $relation),
                        count($values),
                        $this->formatTraceList($values),
                    ));
                }
            }
        }
    }

    private function renderTraceVerificationReport(string $artifactDirectory): void
    {
        $path = $artifactDirectory.DIRECTORY_SEPARATOR.'verification.json';
        $verification = $this->decodeJsonFile($path);

        if ($verification === null) {
            $this->line('<comment>No verification report was written for this node.</comment>');

            return;
        }

        $this->line(sprintf(
            '<fg=magenta>Verification</> status=%s path=%s',
            $this->traceStatusLabel((string) data_get($verification, 'status', 'unknown')),
            $path,
        ));

        $rows = collect(data_get($verification, 'checks', []))
            ->map(function (array $check, string $name): array {
                return [
                    $name,
                    sprintf(
                        'source=%d downloaded=%d stored=%d normalized=%d integrity=%s result=%s',
                        (int) data_get($check, 'source_total_count', 0),
                        (int) data_get($check, 'downloaded_count', 0),
                        (int) data_get($check, 'stored_payload_count', 0),
                        (int) data_get($check, 'normalized_count', 0),
                        (bool) data_get($check, 'relation_integrity_ok', false) ? 'yes' : 'no',
                        (bool) data_get($check, 'ok', false) ? 'passed' : 'failed',
                    ),
                ];
            })
            ->values()
            ->all();

        if ($rows !== []) {
            $this->table(['Check', 'Verification'], $rows);
        }
    }

    private function renderEndpointDigest(array $report): void
    {
        $artifactDirectory = $this->traceArtifactDirectory($report);

        if ($artifactDirectory === null) {
            return;
        }

        $importsDirectory = $artifactDirectory.DIRECTORY_SEPARATOR.'imports';

        if (! File::isDirectory($importsDirectory)) {
            return;
        }

        $files = collect(File::files($importsDirectory))
            ->sortBy(fn (\SplFileInfo $file): string => $file->getFilename())
            ->values();

        if ($files->isEmpty()) {
            return;
        }

        $this->line('  Endpoint digest:');

        foreach ($files as $file) {
            $endpointReport = $this->decodeJsonFile($file->getPathname());

            if ($endpointReport === null) {
                continue;
            }

            $endpoint = (string) data_get($endpointReport, 'endpoint', pathinfo($file->getFilename(), PATHINFO_FILENAME));
            $addedFieldCount = count(is_array(data_get($endpointReport, 'added_fields')) ? data_get($endpointReport, 'added_fields') : []);
            $addedRelationCount = collect(is_array(data_get($endpointReport, 'added_relations')) ? data_get($endpointReport, 'added_relations') : [])
                ->sum(fn (mixed $values): int => is_array($values) ? count($values) : 0);

            $this->line(sprintf(
                '    - %s | payload=%s | fields +%d | relations +%d | target=%s',
                $endpoint,
                (bool) data_get($endpointReport, 'has_payload', false) ? 'yes' : 'no',
                $addedFieldCount,
                $addedRelationCount,
                $this->traceTargetTables((string) data_get($report, 'node_type'), $endpoint),
            ));
        }
    }

    private function renderVerificationDigest(array $report): void
    {
        $artifactDirectory = $this->traceArtifactDirectory($report);

        if ($artifactDirectory === null) {
            return;
        }

        $verification = $this->decodeJsonFile($artifactDirectory.DIRECTORY_SEPARATOR.'verification.json');

        if ($verification === null) {
            return;
        }

        $failedChecks = collect(is_array(data_get($verification, 'checks')) ? data_get($verification, 'checks') : [])
            ->filter(fn (mixed $check): bool => is_array($check) && ! (bool) data_get($check, 'ok', false))
            ->keys()
            ->values()
            ->all();

        $this->line(sprintf(
            '  Verification: %s%s',
            (string) data_get($verification, 'status', 'unknown'),
            $failedChecks !== [] ? ' | failed checks: '.$this->formatList($failedChecks) : '',
        ));
    }

    private function traceArtifactDirectory(array $report): ?string
    {
        $storagePath = data_get($report, 'download.storage_path');

        if (! is_string($storagePath) || trim($storagePath) === '') {
            return null;
        }

        if (! str_ends_with(str_replace('\\', '/', $storagePath), '.json')) {
            return rtrim($storagePath, DIRECTORY_SEPARATOR);
        }

        return pathinfo($storagePath, PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($storagePath, PATHINFO_FILENAME);
    }

    private function traceModel(string $nodeType, string $imdbId): ?Model
    {
        return match ($nodeType) {
            'title' => Title::query()
                ->withTrashed()
                ->with('statistic')
                ->where('imdb_id', $imdbId)
                ->first(),
            'name' => Person::query()
                ->withTrashed()
                ->where('imdb_id', $imdbId)
                ->first(),
            default => null,
        };
    }

    private function traceTargetTables(string $nodeType, string $endpoint): string
    {
        return match ($nodeType) {
            'title' => match ($endpoint) {
                'title' => 'titles, title_statistics, imdb_title_imports',
                'credits' => 'people, person_professions, credits',
                'releaseDates' => 'titles, imdb_title_imports.payload.releaseDates',
                'akas' => 'title_translations',
                'seasons' => 'seasons',
                'episodes' => 'titles, episodes, title_statistics, media_assets',
                'images', 'videos' => 'media_assets',
                'awardNominations' => 'awards, award_events, award_categories, award_nominations',
                'parentsGuide', 'certificates', 'boxOffice' => 'titles.imdb_payload',
                'companyCredits' => 'companies, company_title',
                default => 'unknown',
            },
            'name' => match ($endpoint) {
                'details' => 'people, person_professions, media_assets',
                'images' => 'media_assets, people.imdb_payload',
                'filmography' => 'credits, person_professions, people.imdb_payload',
                'relationships', 'trivia' => 'people.imdb_payload',
                default => 'unknown',
            },
            default => 'unknown',
        };
    }

    private function traceFieldTarget(string $nodeType, string $field): string
    {
        if ($nodeType === 'title' && str_starts_with($field, 'statistic.')) {
            return 'title_statistics.'.Str::after($field, 'statistic.');
        }

        return match ($nodeType) {
            'title' => 'titles.'.$field,
            'name' => 'people.'.$field,
            default => $field,
        };
    }

    private function traceRelationTargets(string $nodeType, string $relation): string
    {
        return match ($nodeType) {
            'title' => match ($relation) {
                'genres' => 'genres + genre_title',
                'translations' => 'title_translations',
                'seasons' => 'seasons',
                'episodes' => 'titles + episodes',
                'credits' => 'people + person_professions + credits',
                'companies' => 'companies + company_title',
                'awards' => 'awards + award_events + award_categories + award_nominations',
                'image_assets', 'video_assets', 'media_assets' => 'media_assets',
                'payload_sections' => 'titles.imdb_payload',
                default => $relation,
            },
            'name' => match ($relation) {
                'alternate_names' => 'people.imdb_alternative_names + people.alternate_names',
                'professions' => 'person_professions',
                'media_assets' => 'media_assets',
                'payload_sections' => 'people.imdb_payload',
                'credits' => 'credits',
                default => $relation,
            },
            default => $relation,
        };
    }

    private function traceFieldValue(?Model $model, string $field): string
    {
        if (! $model instanceof Model) {
            return 'n/a';
        }

        return $this->formatTraceValue(data_get($model, $field));
    }

    private function isWriteQuery(string $sql): bool
    {
        return preg_match('/^(insert|update|delete|replace)\s/i', $sql) === 1;
    }

    private function traceSqlOperation(string $sql): string
    {
        preg_match('/^(insert|update|delete|replace)\s/i', $sql, $matches);

        return Str::lower((string) ($matches[1] ?? 'write'));
    }

    private function traceSqlTable(string $sql): ?string
    {
        if (preg_match('/^insert\s+into\s+["`\[]?([^\s"`\]\(]+)["`\]]?/i', $sql, $matches) === 1) {
            return $this->stripIdentifierWrapping((string) $matches[1]);
        }

        if (preg_match('/^update\s+["`\[]?([^\s"`\]]+)["`\]]?/i', $sql, $matches) === 1) {
            return $this->stripIdentifierWrapping((string) $matches[1]);
        }

        if (preg_match('/^delete\s+from\s+["`\[]?([^\s"`\]]+)["`\]]?/i', $sql, $matches) === 1) {
            return $this->stripIdentifierWrapping((string) $matches[1]);
        }

        return null;
    }

    /**
     * @param  list<mixed>  $bindings
     * @return array<string, string>
     */
    private function traceSqlColumnBindings(string $sql, array $bindings): array
    {
        if (preg_match('/^insert\s+into\s+["`\[]?[^\s"`\]\(]+["`\]]?\s*\((.*?)\)\s*values\s*\((.*?)\)/i', $sql, $matches) === 1) {
            $columns = $this->splitSqlList((string) $matches[1]);
            $mapped = [];

            foreach ($columns as $index => $column) {
                if (! array_key_exists($index, $bindings)) {
                    continue;
                }

                $mapped[$column] = $this->formatTraceValue($bindings[$index]);
            }

            return $mapped;
        }

        if (preg_match('/^update\s+["`\[]?[^\s"`\]]+["`\]]?\s+set\s+(.*?)\s+where\s+/i', $sql, $matches) === 1) {
            $assignments = preg_split('/\s*,\s*/', (string) $matches[1]) ?: [];
            $mapped = [];
            $bindingIndex = 0;

            foreach ($assignments as $assignment) {
                if (preg_match('/^["`\[]?([^\s"`\]=]+)["`\]]?\s*=\s*\?/i', trim($assignment), $assignmentMatches) !== 1) {
                    continue;
                }

                if (! array_key_exists($bindingIndex, $bindings)) {
                    continue;
                }

                $mapped[$this->stripIdentifierWrapping((string) $assignmentMatches[1])] = $this->formatTraceValue($bindings[$bindingIndex]);
                $bindingIndex++;
            }

            return $mapped;
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function splitSqlList(string $segment): array
    {
        return collect(preg_split('/\s*,\s*/', trim($segment)) ?: [])
            ->map(fn (string $part): string => $this->stripIdentifierWrapping($part))
            ->filter()
            ->values()
            ->all();
    }

    private function stripIdentifierWrapping(string $value): string
    {
        return trim(str_replace(['"', '`', '[', ']'], '', trim($value)));
    }

    private function formatTraceValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            $keys = array_keys($value);

            return sprintf(
                'array(count=%d keys=%s)',
                count($value),
                implode(', ', array_slice(array_map(static fn (mixed $key): string => (string) $key, $keys), 0, 5)),
            );
        }

        if (is_string($value)) {
            $decoded = null;

            if ($value !== '' && in_array($value[0], ['{', '['], true)) {
                $decoded = json_decode($value, true);
            }

            if (is_array($decoded)) {
                $keys = array_keys($decoded);

                return sprintf(
                    'json(len=%d keys=%s)',
                    strlen($value),
                    implode(', ', array_slice(array_map(static fn (mixed $key): string => (string) $key, $keys), 0, 5)),
                );
            }

            return '"'.Str::limit($value, 140).'"';
        }

        return (string) $value;
    }

    /**
     * @param  list<mixed>  $values
     */
    private function formatTraceList(array $values): string
    {
        $items = collect($values)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->values();

        if ($items->count() <= 10) {
            return $items->implode(', ');
        }

        return $items->take(10)->implode(', ').sprintf(' (+%d more)', $items->count() - 10);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonFile(string $path): ?array
    {
        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function traceStatusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'passed',
            'failed' => 'failed',
            default => $status,
        };
    }

    /**
     * @param  list<mixed>  $values
     */
    private function formatList(array $values): string
    {
        $items = collect($values)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->values();

        if ($items->count() <= 5) {
            return $items->implode(', ');
        }

        return $items->take(5)->implode(', ').sprintf(' (+%d more)', $items->count() - 5);
    }

    private function relationLabel(string $relation): string
    {
        return match ($relation) {
            'alternate_names' => 'Alternate names',
            'awards' => 'Awards',
            'companies' => 'Companies',
            'credits' => 'Credits',
            'episodes' => 'Episodes',
            'genres' => 'Genres',
            'media_assets', 'mediaAssets' => 'Media assets',
            'payload_sections' => 'Payload sections',
            'professions' => 'Professions',
            'similar_interests' => 'Similar interests',
            'translations' => 'Translations',
            default => Str::of((string) $relation)->replace('_', ' ')->headline()->toString(),
        };
    }

    private function renderFrontierReport(array $report): void
    {
        $this->newLine();
        $this->line(sprintf(
            '<fg=cyan;options=bold>Frontier %s page %d</>',
            Str::headline((string) data_get($report, 'type', 'unknown')),
            (int) data_get($report, 'page', 1),
        ));
        $this->line(sprintf(
            '  Frontier flow: discovered=%d | queue_after=%d | next_page=%s%s',
            (int) data_get($report, 'discovered_count', 0),
            (int) data_get($report, 'queued_total', 0),
            (bool) data_get($report, 'next_page_available', false) ? 'yes' : 'no',
            data_get($report, 'path') ? ' | artifact='.$this->displayPath((string) data_get($report, 'path')) : '',
        ));

        if (is_string(data_get($report, 'error')) && trim((string) data_get($report, 'error')) !== '') {
            $this->line('  Frontier warning: '.(string) data_get($report, 'error'));
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Discovered nodes', $this->colorizeCount((int) data_get($report, 'discovered_count', 0), 'cyan')],
                ['Queue size after page', $this->colorizeCount((int) data_get($report, 'queued_total', 0), 'cyan')],
                ['Next page available', (bool) data_get($report, 'next_page_available', false) ? '<fg=green>yes</>' : '<comment>no</comment>'],
                ['Saved JSON', basename((string) data_get($report, 'path', ''))],
            ],
        );
    }

    private function dbCountLabel(string $key): string
    {
        return match ($key) {
            'titles' => 'Titles',
            'title_fields' => 'Title fields',
            'people' => 'People',
            'person_fields' => 'Person fields',
            'alternate_names' => 'Alternate names',
            'awards' => 'Awards',
            'companies' => 'Companies',
            'credits' => 'Credits',
            'episodes' => 'Episodes',
            'genres' => 'Genres',
            'media_assets' => 'Media assets',
            'payload_sections' => 'Payload sections',
            'professions' => 'Professions',
            'translations' => 'Translations',
            default => Str::headline($key),
        };
    }

    private function colorizeCount(int $count, string $color = 'green'): string
    {
        if ($count <= 0) {
            return '<comment>0</comment>';
        }

        return sprintf('<fg=%s>%d</>', $color, $count);
    }

    private function displayPath(string $path): string
    {
        $normalizedBasePath = str_replace('\\', '/', base_path());
        $normalizedPath = str_replace('\\', '/', $path);

        if (Str::startsWith($normalizedPath, $normalizedBasePath.'/')) {
            return Str::after($normalizedPath, $normalizedBasePath.'/');
        }

        return $normalizedPath;
    }
}
