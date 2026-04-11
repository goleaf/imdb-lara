<?php

namespace App\Console\Commands;

use App\Actions\Import\ImportImdbCatalogGraphAction;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;

class ImdbImportTitlesFrontierCommand extends Command implements Isolatable
{
    protected $signature = 'imdb:import-titles-frontier';

    protected $description = 'Import the IMDb /titles frontier into the current catalog schema and recursively expand linked names and interests.';

    public function __construct(
        private readonly ImportImdbCatalogGraphAction $importImdbCatalogGraphAction,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting IMDb catalog frontier import...');

        $result = $this->importImdbCatalogGraphAction->handle(
            fn (array $event): mixed => $this->renderProgressEvent($event),
        );

        $this->newLine();
        $this->line('<fg=cyan;options=bold>IMDb catalog import complete.</>');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Seed titles', implode(', ', data_get($result, 'seed_titles', []))],
                ['Frontier pages', (string) data_get($result, 'frontier_pages', 0)],
                ['Frontier titles queued', (string) data_get($result, 'frontier_titles', 0)],
                ['Processed nodes', (string) data_get($result, 'processed_nodes', 0)],
                ['Processed titles', (string) data_get($result, 'by_type.title', 0)],
                ['Processed names', (string) data_get($result, 'by_type.name', 0)],
                ['Processed interests', (string) data_get($result, 'by_type.interest', 0)],
                ['Failed nodes', (string) data_get($result, 'failed_nodes', 0)],
                ['Title frontier', (string) data_get($result, 'title_frontier_status', 'n/a')],
                ['Interest frontier', (string) data_get($result, 'interest_frontier_status', 'n/a')],
                ['Star meter frontier', (string) data_get($result, 'starmeter_frontier_status', 'n/a')],
                ['Resume store', (string) data_get($result, 'resume_store', 'n/a')],
            ],
        );

        return (int) data_get($result, 'failed_nodes', 0) === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderProgressEvent(array $event): void
    {
        match ((string) ($event['event'] ?? '')) {
            'frontier' => $this->renderFrontierProgress($event),
            'node_start' => $this->renderNodeStartProgress($event),
            'node' => $this->renderNodeProgress($event),
            'node_failed' => $this->renderNodeFailureProgress($event),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderFrontierProgress(array $event): void
    {
        $type = (string) ($event['type'] ?? 'unknown');
        $report = Arr::wrap($event['report'] ?? []);

        $details = array_filter([
            'status='.$this->progressScalar($report['status'] ?? null),
            'pages='.$this->progressScalar($report['pages_processed'] ?? null),
            'queued='.$this->progressScalar($report['titles_queued'] ?? $report['interests_queued'] ?? $report['names_imported'] ?? null),
            'resume='.$this->progressScalar($report['resume_store'] ?? null),
        ], fn (?string $value): bool => $value !== null);

        $this->comment(sprintf('[frontier:%s] %s', $type, implode(' | ', $details)));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderNodeStartProgress(array $event): void
    {
        $node = Arr::wrap($event['node'] ?? []);

        $this->line(sprintf(
            'Starting %s %s (queue=%d, visited=%d)',
            ucfirst((string) ($node['type'] ?? 'node')),
            (string) ($node['imdb_id'] ?? 'unknown'),
            (int) ($event['queue_size'] ?? 0),
            (int) ($event['visited_nodes'] ?? 0),
        ));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderNodeProgress(array $event): void
    {
        $node = Arr::wrap($event['node'] ?? []);

        $this->info(sprintf(
            'Processed %s %s (+%d discovered, queue=%d)',
            ucfirst((string) ($node['type'] ?? 'node')),
            (string) ($node['imdb_id'] ?? 'unknown'),
            (int) ($event['discovered_count'] ?? 0),
            (int) ($event['queue_size'] ?? 0),
        ));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function renderNodeFailureProgress(array $event): void
    {
        $node = Arr::wrap($event['node'] ?? []);

        $this->warn(sprintf(
            'Failed %s %s: %s',
            ucfirst((string) ($node['type'] ?? 'node')),
            (string) ($node['imdb_id'] ?? 'unknown'),
            (string) ($event['error'] ?? 'Unknown error'),
        ));
    }

    private function progressScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }
}
