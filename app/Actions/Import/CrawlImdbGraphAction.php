<?php

namespace App\Actions\Import;

use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplQueue;
use Throwable;

class CrawlImdbGraphAction
{
    public function __construct(
        private readonly DownloadImdbTitlePayloadAction $downloadImdbTitlePayloadAction,
        private readonly DownloadImdbNamePayloadAction $downloadImdbNamePayloadAction,
        private readonly DownloadImdbInterestPayloadAction $downloadImdbInterestPayloadAction,
        private readonly FetchImdbGraphqlAction $fetchImdbGraphqlAction,
        private readonly ImportImdbTitlePayloadAction $importImdbTitlePayloadAction,
        private readonly ImportImdbNamePayloadAction $importImdbNamePayloadAction,
        private readonly FetchImdbJsonAction $fetchImdbJsonAction,
        private readonly WriteImdbEndpointImportReportAction $writeImdbEndpointImportReportAction,
        private readonly WriteImdbTitleVerificationReportAction $writeImdbTitleVerificationReportAction,
        private readonly WriteImdbNameVerificationReportAction $writeImdbNameVerificationReportAction,
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    /**
     * @param  list<string>  $seeds
     * @param  array{
     *     bootstrap_interests?: bool,
     *     bootstrap_star_meter?: bool,
     *     bootstrap_titles?: bool,
     *     fill_missing_only?: bool,
     *     force?: bool,
     *     max_nodes?: int,
     *     storage_root?: string,
     *     url_template?: string
     * }  $options
     * @return array{
     *     finished_at: string,
     *     frontiers: list<array<string, mixed>>,
     *     nodes: list<array<string, mixed>>,
     *     report_path: string,
     *     started_at: string,
     *     summary: array<string, mixed>
     * }
     */
    public function handle(array $seeds = [], array $options = [], ?callable $progress = null): array
    {
        $storageRoot = $this->resolveStorageRoot($options['storage_root'] ?? null);
        $urlTemplate = is_string($options['url_template'] ?? null) && trim((string) $options['url_template']) !== ''
            ? trim((string) $options['url_template'])
            : $this->resolveImdbApiUrlAction->endpoint('title');
        $bootstrapTitles = (bool) ($options['bootstrap_titles'] ?? true);
        $bootstrapInterests = (bool) ($options['bootstrap_interests'] ?? true);
        $bootstrapStarMeter = (bool) ($options['bootstrap_star_meter'] ?? true);
        $force = (bool) ($options['force'] ?? false);
        $fillMissingOnly = (bool) ($options['fill_missing_only'] ?? true);
        $maxNodes = max(0, (int) ($options['max_nodes'] ?? 0));
        $queue = new SplQueue;
        $enqueued = [];
        $visited = [];
        $frontiers = [];
        $nodeReports = [];
        $titleFrontierState = null;
        $interestFrontierPending = false;
        $starMeterFrontierPending = false;
        $processedNodesSinceFrontierAdvance = 0;

        File::ensureDirectoryExists($storageRoot);

        foreach ($this->normalizeSeedNodes($seeds) as $seedNode) {
            $this->enqueueNode($queue, $enqueued, $seedNode);
        }

        if ($queue->isEmpty()) {
            if ($bootstrapTitles) {
                $titleFrontierState = $this->initializeTitleFrontierState($storageRoot);
            }

            $interestFrontierPending = $bootstrapInterests;
            $starMeterFrontierPending = $bootstrapStarMeter;
        }

        $startedAt = now()->toIso8601String();
        $stoppedDueToLimit = false;

        while (true) {
            if (
                $this->hasPendingTitleFrontier($titleFrontierState)
                && $this->shouldAdvancePendingFrontier($queue, $processedNodesSinceFrontierAdvance)
            ) {
                $frontierReport = $this->advanceTitleFrontier($queue, $enqueued, $titleFrontierState);
                $frontiers[] = $frontierReport;
                $processedNodesSinceFrontierAdvance = 0;

                if ($progress !== null) {
                    $progress([
                        'event' => 'frontier',
                        'report' => $frontierReport,
                    ]);
                }
            }

            if (
                ! $this->hasPendingTitleFrontier($titleFrontierState)
                && $interestFrontierPending
                && $this->shouldAdvancePendingFrontier($queue, $processedNodesSinceFrontierAdvance)
            ) {
                $this->bootstrapInterestFrontier($queue, $enqueued, $frontiers, $storageRoot, $progress);
                $interestFrontierPending = false;
                $processedNodesSinceFrontierAdvance = 0;
            }

            if (
                ! $this->hasPendingTitleFrontier($titleFrontierState)
                && ! $interestFrontierPending
                && $starMeterFrontierPending
                && $this->shouldAdvancePendingFrontier($queue, $processedNodesSinceFrontierAdvance)
            ) {
                $this->bootstrapStarMeterFrontier($queue, $enqueued, $frontiers, $storageRoot, $progress);
                $starMeterFrontierPending = false;
                $processedNodesSinceFrontierAdvance = 0;
            }

            if ($queue->isEmpty()) {
                break;
            }

            if ($maxNodes > 0 && count($visited) >= $maxNodes) {
                $stoppedDueToLimit = true;

                break;
            }

            /** @var array{type: string, imdb_id: string, source: string|null} $node */
            $node = $queue->dequeue();
            $nodeKey = $this->nodeKey($node['type'], $node['imdb_id']);

            if (array_key_exists($nodeKey, $visited)) {
                continue;
            }

            if ($progress !== null) {
                $progress([
                    'event' => 'node_start',
                    'node' => $node,
                    'queue_size' => $queue->count(),
                    'visited_nodes' => count($visited),
                ]);
            }

            $visited[$nodeKey] = true;
            $nodeReport = $this->processNode(
                $node,
                $queue,
                $enqueued,
                $storageRoot,
                $urlTemplate,
                $force,
                $fillMissingOnly,
            );
            $nodeReports[] = $nodeReport;

            if ($progress !== null) {
                $progress([
                    'event' => 'node',
                    'report' => $nodeReport,
                ]);
            }

            $processedNodesSinceFrontierAdvance++;
        }

        $finishedAt = now()->toIso8601String();
        $summary = $this->summarizeReports($frontiers, $nodeReports, $visited, $stoppedDueToLimit);
        $reportPath = $this->writeRunReport($storageRoot, [
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'frontiers' => $frontiers,
            'nodes' => $nodeReports,
            'summary' => $summary,
        ]);

        return [
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'frontiers' => $frontiers,
            'nodes' => $nodeReports,
            'summary' => $summary,
            'report_path' => $reportPath,
        ];
    }

    private function shouldAdvancePendingFrontier(SplQueue $queue, int $processedNodesSinceFrontierAdvance): bool
    {
        return $queue->isEmpty() || $processedNodesSinceFrontierAdvance > 0;
    }

    private function processNode(
        array $node,
        SplQueue $queue,
        array &$enqueued,
        string $storageRoot,
        string $urlTemplate,
        bool $force,
        bool $fillMissingOnly,
    ): array {
        return match ($node['type']) {
            'title' => $this->processTitleNode($node, $queue, $enqueued, $storageRoot, $urlTemplate, $force, $fillMissingOnly),
            'name' => $this->processNameNode($node, $queue, $enqueued, $storageRoot, $force, $fillMissingOnly),
            'interest' => $this->processInterestNode($node, $queue, $enqueued, $storageRoot, $force),
            default => [
                'node_type' => $node['type'],
                'imdb_id' => $node['imdb_id'],
                'status' => 'failed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'error' => 'Unsupported node type.',
                'discovered_nodes' => [],
            ],
        };
    }

    private function processTitleNode(
        array $node,
        SplQueue $queue,
        array &$enqueued,
        string $storageRoot,
        string $urlTemplate,
        bool $force,
        bool $fillMissingOnly,
    ): array {
        $imdbId = $node['imdb_id'];
        $beforeSnapshot = $this->snapshotTitle($imdbId);

        try {
            $download = $this->downloadImdbTitlePayloadAction->handle(
                $imdbId,
                $storageRoot.DIRECTORY_SEPARATOR.'titles',
                $urlTemplate,
                $force,
            );
            $title = $this->importImdbTitlePayloadAction->handle(
                $download['payload'],
                $download['storage_path'],
                ['fill_missing_only' => $fillMissingOnly],
            );
            $this->writeImdbTitleVerificationReportAction->handle(
                $title,
                $download['payload'],
                pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME),
            );
            $afterSnapshot = $this->snapshotTitle($imdbId);
            $discoveredNodes = $this->discoverNodesFromTitlePayload($download['payload']);
            $this->warmGraphqlTitleArtifacts($discoveredNodes, 'title discovery');

            foreach ($discoveredNodes as $discoveredNode) {
                $this->enqueueNode($queue, $enqueued, $discoveredNode);
            }

            $report = [
                'node_type' => 'title',
                'imdb_id' => $imdbId,
                'label' => $title->name,
                'status' => 'processed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'download' => [
                    'downloaded' => $download['downloaded'],
                    'source_url' => $download['source_url'],
                    'storage_path' => $download['storage_path'],
                ],
                'import' => $this->augmentImportDiff('title', $this->diffSnapshots($beforeSnapshot, $afterSnapshot)),
                'discovered_nodes' => $discoveredNodes,
                'verification_path' => pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME).DIRECTORY_SEPARATOR.'verification.json',
            ];

            $report['report_path'] = $this->writeNodeReport(
                pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME),
                $report,
            );

            return $report;
        } catch (Throwable $exception) {
            return [
                'node_type' => 'title',
                'imdb_id' => $imdbId,
                'status' => 'failed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'error' => $exception->getMessage(),
                'import' => [
                    'new_record' => false,
                    'existing_fields' => array_values($beforeSnapshot['filled_fields']),
                    'filled_fields' => [],
                    'new_relations' => [],
                    'db_counts' => [],
                    'db_total_added' => 0,
                ],
                'discovered_nodes' => [],
            ];
        }
    }

    private function processNameNode(
        array $node,
        SplQueue $queue,
        array &$enqueued,
        string $storageRoot,
        bool $force,
        bool $fillMissingOnly,
    ): array {
        $imdbId = $node['imdb_id'];
        $beforeSnapshot = $this->snapshotPerson($imdbId);

        try {
            $download = $this->downloadImdbNamePayloadAction->handle(
                $imdbId,
                $storageRoot.DIRECTORY_SEPARATOR.'names',
                $force,
            );
            $person = $this->importImdbNamePayloadAction->handle(
                $download['payload'],
                $download['storage_path'],
                ['fill_missing_only' => $fillMissingOnly],
            );
            $this->writeImdbNameVerificationReportAction->handle(
                $person,
                $download['payload'],
                pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME),
            );
            $afterSnapshot = $this->snapshotPerson($imdbId);
            $discoveredNodes = $this->discoverNodesFromNamePayload($download['payload']);
            $this->warmGraphqlTitleArtifacts($discoveredNodes, 'name discovery');

            foreach ($discoveredNodes as $discoveredNode) {
                $this->enqueueNode($queue, $enqueued, $discoveredNode);
            }

            $report = [
                'node_type' => 'name',
                'imdb_id' => $imdbId,
                'label' => $person->name,
                'status' => 'processed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'download' => [
                    'downloaded' => $download['downloaded'],
                    'source_url' => $download['source_url'],
                    'storage_path' => $download['storage_path'],
                ],
                'import' => $this->augmentImportDiff('name', $this->diffSnapshots($beforeSnapshot, $afterSnapshot)),
                'discovered_nodes' => $discoveredNodes,
                'verification_path' => pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME).DIRECTORY_SEPARATOR.'verification.json',
            ];

            $report['report_path'] = $this->writeNodeReport(
                pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME),
                $report,
            );

            return $report;
        } catch (Throwable $exception) {
            return [
                'node_type' => 'name',
                'imdb_id' => $imdbId,
                'status' => 'failed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'error' => $exception->getMessage(),
                'import' => [
                    'new_record' => false,
                    'existing_fields' => array_values($beforeSnapshot['filled_fields']),
                    'filled_fields' => [],
                    'new_relations' => [],
                    'db_counts' => [],
                    'db_total_added' => 0,
                ],
                'discovered_nodes' => [],
            ];
        }
    }

    private function processInterestNode(
        array $node,
        SplQueue $queue,
        array &$enqueued,
        string $storageRoot,
        bool $force,
    ): array {
        $imdbId = $node['imdb_id'];

        try {
            $download = $this->downloadImdbInterestPayloadAction->handle(
                $imdbId,
                $storageRoot.DIRECTORY_SEPARATOR.'interests',
                $force,
            );
            $discoveredNodes = $this->discoverNodesFromInterestPayload($download['payload']);

            foreach ($discoveredNodes as $discoveredNode) {
                $this->enqueueNode($queue, $enqueued, $discoveredNode);
            }

            $report = [
                'node_type' => 'interest',
                'imdb_id' => $imdbId,
                'label' => data_get($download['payload'], 'interest.name'),
                'status' => 'processed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'download' => [
                    'downloaded' => $download['downloaded'],
                    'source_url' => $download['source_url'],
                    'storage_path' => $download['storage_path'],
                ],
                'import' => [
                    'new_record' => false,
                    'existing_fields' => [],
                    'filled_fields' => [],
                    'new_relations' => [
                        'similar_interests' => collect($discoveredNodes)
                            ->where('type', 'interest')
                            ->map(fn (array $discoveredNode): string => $discoveredNode['imdb_id'])
                            ->values()
                            ->all(),
                    ],
                    'db_counts' => [],
                    'db_total_added' => 0,
                ],
                'discovered_nodes' => $discoveredNodes,
            ];

            $artifactDirectory = pathinfo($download['storage_path'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($download['storage_path'], PATHINFO_FILENAME);
            $this->writeImdbEndpointImportReportAction->handle($artifactDirectory, 'interest', [
                'endpoint' => 'interest',
                'processed_at' => now()->toIso8601String(),
                'has_payload' => is_array(data_get($download['payload'], 'interest')),
                'new_record' => false,
                'existing_fields' => [],
                'added_fields' => [],
                'existing_relations' => [],
                'added_relations' => [
                    'payload_sections' => [
                        'Interest',
                    ],
                    'similar_interests' => collect($discoveredNodes)
                        ->where('type', 'interest')
                        ->map(fn (array $discoveredNode): string => $discoveredNode['imdb_id'])
                        ->values()
                        ->all(),
                ],
                'artifact_path' => 'interest.json',
                'imdb_id' => $imdbId,
            ]);
            $report['report_path'] = $this->writeNodeReport($artifactDirectory, $report);

            return $report;
        } catch (Throwable $exception) {
            return [
                'node_type' => 'interest',
                'imdb_id' => $imdbId,
                'status' => 'failed',
                'processed_at' => now()->toIso8601String(),
                'source' => $node['source'],
                'error' => $exception->getMessage(),
                'import' => [
                    'new_record' => false,
                    'existing_fields' => [],
                    'filled_fields' => [],
                    'new_relations' => [],
                    'db_counts' => [],
                    'db_total_added' => 0,
                ],
                'discovered_nodes' => [],
            ];
        }
    }

    /**
     * @return array{
     *     directory: string,
     *     exhausted: bool,
     *     next_page_token: string|null,
     *     page: int,
     *     seen_page_tokens: array<string, bool>
     * }
     */
    private function initializeTitleFrontierState(string $storageRoot): array
    {
        $directory = $storageRoot.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'titles';
        File::ensureDirectoryExists($directory);

        return [
            'directory' => $directory,
            'page' => 1,
            'next_page_token' => null,
            'seen_page_tokens' => [],
            'exhausted' => false,
        ];
    }

    /**
     * @param  array{
     *     directory: string,
     *     exhausted: bool,
     *     next_page_token: string|null,
     *     page: int,
     *     seen_page_tokens: array<string, bool>
     * }|null  $titleFrontierState
     */
    private function hasPendingTitleFrontier(?array $titleFrontierState): bool
    {
        return is_array($titleFrontierState) && ! ($titleFrontierState['exhausted'] ?? true);
    }

    /**
     * @param  array{
     *     directory: string,
     *     exhausted: bool,
     *     next_page_token: string|null,
     *     page: int,
     *     seen_page_tokens: array<string, bool>
     * }  $titleFrontierState
     * @return array{
     *     type: string,
     *     page: int,
     *     path: string,
     *     discovered_count: int,
     *     queued_total: int,
     *     next_page_available: bool
     * }
     */
    private function advanceTitleFrontier(
        SplQueue $queue,
        array &$enqueued,
        array &$titleFrontierState,
    ): array {
        $page = (int) $titleFrontierState['page'];

        try {
            $payload = $this->fetchImdbJsonAction->get(
                $this->resolveImdbApiUrlAction->handle($this->resolveImdbApiUrlAction->endpoint('titles.frontier')),
                $titleFrontierState['next_page_token'] === null ? [] : ['pageToken' => $titleFrontierState['next_page_token']],
            );
        } catch (Throwable $exception) {
            logger()->warning(sprintf(
                'IMDb titles frontier failed on page [%d]; stopping frontier early. %s',
                $page,
                $exception->getMessage(),
            ));

            $titleFrontierState['page'] = $page + 1;
            $titleFrontierState['next_page_token'] = null;
            $titleFrontierState['exhausted'] = true;

            return [
                'type' => 'titles',
                'page' => $page,
                'path' => '',
                'status' => 'stopped',
                'error' => $exception->getMessage(),
                'discovered_count' => 0,
                'queued_total' => $queue->count(),
                'next_page_available' => false,
            ];
        }

        $path = $titleFrontierState['directory'].DIRECTORY_SEPARATOR.sprintf('page-%04d.json', $titleFrontierState['page']);
        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        $pageTitleIds = collect($this->normalizeObjectList(data_get($payload, 'titles')))
            ->map(fn (array $titlePayload): ?string => $this->nullableString(data_get($titlePayload, 'id')))
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match('/^tt\d+$/', $value) === 1)
            ->values()
            ->all();

        $this->warmGraphqlTitleArtifacts(
            collect($pageTitleIds)
                ->map(fn (string $titleId): array => [
                    'type' => 'title',
                    'imdb_id' => $titleId,
                    'source' => 'frontier:titles',
                ])
                ->all(),
            sprintf('titles frontier page %d', $page),
        );

        foreach ($pageTitleIds as $titleId) {
            $this->enqueueNode($queue, $enqueued, [
                'type' => 'title',
                'imdb_id' => $titleId,
                'source' => 'frontier:titles',
            ]);
        }

        $nextPageToken = $this->nextPageToken($payload);

        if ($nextPageToken !== null) {
            if (array_key_exists($nextPageToken, $titleFrontierState['seen_page_tokens'])) {
                logger()->warning(sprintf(
                    'IMDb titles frontier repeated page token [%s]; stopping frontier early.',
                    $nextPageToken,
                ));
                $nextPageToken = null;
            } else {
                $titleFrontierState['seen_page_tokens'][$nextPageToken] = true;
            }
        }

        $frontierReport = [
            'type' => 'titles',
            'page' => $titleFrontierState['page'],
            'path' => $path,
            'discovered_count' => count($pageTitleIds),
            'queued_total' => $queue->count(),
            'next_page_available' => $nextPageToken !== null,
        ];

        $titleFrontierState['page']++;
        $titleFrontierState['next_page_token'] = $nextPageToken;
        $titleFrontierState['exhausted'] = $nextPageToken === null;

        return $frontierReport;
    }

    private function bootstrapInterestFrontier(
        SplQueue $queue,
        array &$enqueued,
        array &$frontiers,
        string $storageRoot,
        ?callable $progress,
    ): void {
        $directory = $storageRoot.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'interests';
        File::ensureDirectoryExists($directory);

        $payload = $this->fetchImdbJsonAction->get(
            $this->resolveImdbApiUrlAction->handle($this->resolveImdbApiUrlAction->endpoint('interests.frontier')),
        );
        $path = $directory.DIRECTORY_SEPARATOR.'categories.json';
        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        $interestIds = collect($this->normalizeObjectList(data_get($payload, 'categories')))
            ->flatMap(fn (array $category): Collection => collect($this->normalizeObjectList(data_get($category, 'interests'))))
            ->map(fn (array $interest): ?string => $this->nullableString(data_get($interest, 'id')))
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match('/^in[\w-]+$/', $value) === 1)
            ->unique()
            ->values()
            ->all();

        foreach ($interestIds as $interestId) {
            $this->enqueueNode($queue, $enqueued, [
                'type' => 'interest',
                'imdb_id' => $interestId,
                'source' => 'frontier:interests',
            ]);
        }

        $frontierReport = [
            'type' => 'interests',
            'page' => 1,
            'path' => $path,
            'discovered_count' => count($interestIds),
            'queued_total' => $queue->count(),
            'next_page_available' => false,
        ];
        $frontiers[] = $frontierReport;

        if ($progress !== null) {
            $progress([
                'event' => 'frontier',
                'report' => $frontierReport,
            ]);
        }
    }

    private function bootstrapStarMeterFrontier(
        SplQueue $queue,
        array &$enqueued,
        array &$frontiers,
        string $storageRoot,
        ?callable $progress,
    ): void {
        $directory = $storageRoot.DIRECTORY_SEPARATOR.'frontiers'.DIRECTORY_SEPARATOR.'starmeter';
        File::ensureDirectoryExists($directory);

        try {
            $payload = $this->fetchImdbJsonAction->paginate(
                $this->resolveImdbApiUrlAction->handle($this->resolveImdbApiUrlAction->endpoint('chart.starmeter')),
                'names',
            ) ?? ['names' => []];
        } catch (Throwable $exception) {
            $frontierReport = [
                'type' => 'starmeter',
                'page' => 1,
                'path' => '',
                'status' => 'stopped',
                'error' => $exception->getMessage(),
                'discovered_count' => 0,
                'queued_total' => $queue->count(),
                'next_page_available' => false,
            ];
            $frontiers[] = $frontierReport;

            if ($progress !== null) {
                $progress([
                    'event' => 'frontier',
                    'report' => $frontierReport,
                ]);
            }

            return;
        }

        $path = $directory.DIRECTORY_SEPARATOR.'chart.json';
        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        $nameIds = collect($this->normalizeObjectList(data_get($payload, 'names')))
            ->map(fn (array $name): ?string => $this->nullableString(data_get($name, 'id')))
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match('/^nm\d+$/', $value) === 1)
            ->unique()
            ->values()
            ->all();

        foreach ($nameIds as $nameId) {
            $this->enqueueNode($queue, $enqueued, [
                'type' => 'name',
                'imdb_id' => $nameId,
                'source' => 'frontier:starmeter',
            ]);
        }

        $frontierReport = [
            'type' => 'starmeter',
            'page' => 1,
            'path' => $path,
            'discovered_count' => count($nameIds),
            'queued_total' => $queue->count(),
            'next_page_available' => false,
        ];
        $frontiers[] = $frontierReport;

        if ($progress !== null) {
            $progress([
                'event' => 'frontier',
                'report' => $frontierReport,
            ]);
        }
    }

    /**
     * @param  list<string>  $seeds
     * @return list<array{type: string, imdb_id: string, source: string|null}>
     */
    private function normalizeSeedNodes(array $seeds): array
    {
        return collect($seeds)
            ->map(function (mixed $seed): ?array {
                if (! is_string($seed) || trim($seed) === '') {
                    return null;
                }

                $value = trim($seed);

                if (preg_match('/tt\d+/', $value, $titleMatches) === 1) {
                    return [
                        'type' => 'title',
                        'imdb_id' => $titleMatches[0],
                        'source' => 'seed',
                    ];
                }

                if (preg_match('/nm\d+/', $value, $nameMatches) === 1) {
                    return [
                        'type' => 'name',
                        'imdb_id' => $nameMatches[0],
                        'source' => 'seed',
                    ];
                }

                if (preg_match('/in[\w-]+/', $value, $interestMatches) === 1) {
                    return [
                        'type' => 'interest',
                        'imdb_id' => $interestMatches[0],
                        'source' => 'seed',
                    ];
                }

                return null;
            })
            ->filter()
            ->unique(fn (array $node): string => $this->nodeKey($node['type'], $node['imdb_id']))
            ->values()
            ->all();
    }

    private function enqueueNode(SplQueue $queue, array &$enqueued, array $node): void
    {
        $nodeKey = $this->nodeKey($node['type'], $node['imdb_id']);

        if (array_key_exists($nodeKey, $enqueued)) {
            return;
        }

        $enqueued[$nodeKey] = true;
        $queue->enqueue($node);
    }

    private function nodeKey(string $type, string $imdbId): string
    {
        return $type.':'.$imdbId;
    }

    /**
     * @param  list<array{type: string, imdb_id: string, source: string|null}>  $nodes
     */
    private function warmGraphqlTitleArtifacts(array $nodes, string $context): void
    {
        if (! $this->fetchImdbGraphqlAction->enabled()) {
            return;
        }

        $titleIds = collect($nodes)
            ->where('type', 'title')
            ->map(fn (array $node): string => $node['imdb_id'])
            ->unique()
            ->values()
            ->all();

        if ($titleIds === []) {
            return;
        }

        try {
            $this->fetchImdbGraphqlAction->preloadTitleCores($titleIds);
        } catch (Throwable $exception) {
            logger()->warning(sprintf(
                'IMDb GraphQL batch title preload failed during [%s]; falling back to per-title fetches. %s',
                $context,
                $exception->getMessage(),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, imdb_id: string, source: string|null}>
     */
    private function discoverNodesFromTitlePayload(array $payload): array
    {
        $nodes = [];

        foreach (['directors', 'writers', 'stars'] as $key) {
            foreach ($this->normalizeObjectList(data_get($payload, 'title.'.$key)) as $personPayload) {
                $personId = $this->nullableString(data_get($personPayload, 'id'));

                if ($personId !== null && preg_match('/^nm\d+$/', $personId) === 1) {
                    $nodes[] = ['type' => 'name', 'imdb_id' => $personId, 'source' => 'title:'.$key];
                }
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'credits.credits')) as $creditPayload) {
            $personId = $this->nullableString(data_get($creditPayload, 'name.id'));

            if ($personId !== null && preg_match('/^nm\d+$/', $personId) === 1) {
                $nodes[] = ['type' => 'name', 'imdb_id' => $personId, 'source' => 'title:credits'];
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'episodes.episodes')) as $episodePayload) {
            $titleId = $this->nullableString(data_get($episodePayload, 'id'));

            if ($titleId !== null && preg_match('/^tt\d+$/', $titleId) === 1) {
                $nodes[] = ['type' => 'title', 'imdb_id' => $titleId, 'source' => 'title:episodes'];
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'awardNominations.awardNominations')) as $awardPayload) {
            foreach ($this->normalizeObjectList(data_get($awardPayload, 'nominees')) as $nomineePayload) {
                $personId = $this->nullableString(data_get($nomineePayload, 'id'));

                if ($personId !== null && preg_match('/^nm\d+$/', $personId) === 1) {
                    $nodes[] = ['type' => 'name', 'imdb_id' => $personId, 'source' => 'title:awards'];
                }
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'title.interests')) as $interestPayload) {
            $interestId = $this->nullableString(data_get($interestPayload, 'id'));

            if ($interestId !== null) {
                $nodes[] = ['type' => 'interest', 'imdb_id' => $interestId, 'source' => 'title:interests'];
            }
        }

        return collect($nodes)
            ->unique(fn (array $discoveredNode): string => $this->nodeKey($discoveredNode['type'], $discoveredNode['imdb_id']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, imdb_id: string, source: string|null}>
     */
    private function discoverNodesFromNamePayload(array $payload): array
    {
        $nodes = [];

        foreach ($this->normalizeObjectList(data_get($payload, 'filmography.credits')) as $creditPayload) {
            $titleId = $this->nullableString(data_get($creditPayload, 'title.id'));

            if ($titleId !== null && preg_match('/^tt\d+$/', $titleId) === 1) {
                $nodes[] = ['type' => 'title', 'imdb_id' => $titleId, 'source' => 'name:filmography'];
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'relationships.relationships')) as $relationshipPayload) {
            $relatedNameId = $this->nullableString(data_get($relationshipPayload, 'name.id'));

            if ($relatedNameId !== null && preg_match('/^nm\d+$/', $relatedNameId) === 1) {
                $nodes[] = ['type' => 'name', 'imdb_id' => $relatedNameId, 'source' => 'name:relationships'];
            }
        }

        return collect($nodes)
            ->unique(fn (array $discoveredNode): string => $this->nodeKey($discoveredNode['type'], $discoveredNode['imdb_id']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, imdb_id: string, source: string|null}>
     */
    private function discoverNodesFromInterestPayload(array $payload): array
    {
        $nodes = [];

        foreach ($this->normalizeObjectList(data_get($payload, 'interest.similarInterests')) as $similarInterestPayload) {
            $interestId = $this->nullableString(data_get($similarInterestPayload, 'id'));

            if ($interestId !== null) {
                $nodes[] = ['type' => 'interest', 'imdb_id' => $interestId, 'source' => 'interest:similar'];
            }
        }

        return collect($nodes)
            ->unique(fn (array $discoveredNode): string => $this->nodeKey($discoveredNode['type'], $discoveredNode['imdb_id']))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     exists: bool,
     *     filled_fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }
     */
    private function snapshotTitle(string $imdbId): array
    {
        $title = Title::query()
            ->withTrashed()
            ->where('imdb_id', $imdbId)
            ->with([
                'genres:id,name,slug',
                'translations:id,title_id,locale,localized_title',
                'companies:id,name,slug',
                'credits:id,title_id,person_id,department,job,character_name,episode_id,imdb_source_group',
                'credits.person:id,imdb_id,name',
                'seriesEpisodes:id,series_id,title_id,season_number,episode_number',
                'seriesEpisodes.title:id,imdb_id,name',
                'awardNominations:id,award_event_id,award_category_id,title_id,person_id,credited_name',
                'awardNominations.awardEvent:id,award_id,name,year',
                'awardNominations.awardEvent.award:id,name',
                'awardNominations.awardCategory:id,name',
                'awardNominations.person:id,imdb_id,name',
                'mediaAssets:id,mediable_type,mediable_id,kind,url,caption,provider_key',
            ])
            ->first();

        if (! $title instanceof Title) {
            return [
                'exists' => false,
                'filled_fields' => [],
                'relations' => [],
            ];
        }

        return [
            'exists' => true,
            'filled_fields' => $this->filledFieldLabels($title, $this->titleFieldLabels()),
            'relations' => [
                'genres' => $title->genres
                    ->mapWithKeys(fn ($genre): array => [$genre->slug => $genre->name])
                    ->all(),
                'translations' => $title->translations
                    ->mapWithKeys(fn ($translation): array => [$translation->locale => $translation->locale.' · '.$translation->localized_title])
                    ->all(),
                'companies' => $title->companies
                    ->mapWithKeys(function ($company): array {
                        $relationship = (string) ($company->pivot?->relationship ?? 'company');

                        return [$company->slug.'|'.$relationship => $relationship.' · '.$company->name];
                    })
                    ->all(),
                'credits' => $title->credits
                    ->mapWithKeys(function (Credit $credit): array {
                        $personLabel = $credit->person?->name ?? $credit->credited_as ?? 'Unknown person';
                        $key = implode('|', array_filter([
                            $credit->person?->imdb_id,
                            $credit->department,
                            $credit->job,
                            $credit->episode_id,
                            $credit->imdb_source_group,
                        ], fn (mixed $value): bool => $value !== null && $value !== ''));
                        $character = filled($credit->character_name) ? ' · '.$credit->character_name : '';

                        return [$key => trim($personLabel.' · '.$credit->job.$character)];
                    })
                    ->all(),
                'episodes' => $title->seriesEpisodes
                    ->mapWithKeys(function ($episode): array {
                        $episodeLabel = collect([
                            $episode->season_number !== null ? 'S'.$episode->season_number : null,
                            $episode->episode_number !== null ? 'E'.$episode->episode_number : null,
                            $episode->title?->name,
                        ])->filter()->implode(' ');

                        return [($episode->title?->imdb_id ?? 'episode-'.$episode->id) => $episodeLabel];
                    })
                    ->all(),
                'awards' => $title->awardNominations
                    ->mapWithKeys(function (AwardNomination $awardNomination): array {
                        $label = collect([
                            $awardNomination->awardEvent?->award?->name ?? $awardNomination->awardEvent?->name,
                            $awardNomination->awardCategory?->name,
                            $awardNomination->person?->name ?? $awardNomination->credited_name,
                        ])->filter()->implode(' · ');
                        $key = implode('|', array_filter([
                            $awardNomination->award_event_id,
                            $awardNomination->award_category_id,
                            $awardNomination->person_id,
                            $awardNomination->credited_name,
                        ], fn (mixed $value): bool => $value !== null && $value !== ''));

                        return [$key => $label];
                    })
                    ->all(),
                'media_assets' => $title->mediaAssets
                    ->mapWithKeys(function (MediaAsset $mediaAsset): array {
                        $kind = $mediaAsset->kind?->value ?? $mediaAsset->getRawOriginal('kind');
                        $label = collect([$kind, $mediaAsset->caption, $mediaAsset->url])->filter()->implode(' · ');

                        return [$mediaAsset->provider_key => $label];
                    })
                    ->all(),
                'payload_sections' => collect(is_array($title->imdb_payload) ? $title->imdb_payload : [])
                    ->except(['storageVersion'])
                    ->mapWithKeys(fn (mixed $value, string $key): array => [$key => Str::headline($key)])
                    ->all(),
            ],
        ];
    }

    /**
     * @return array{
     *     exists: bool,
     *     filled_fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }
     */
    private function snapshotPerson(string $imdbId): array
    {
        $person = Person::query()
            ->withTrashed()
            ->where('imdb_id', $imdbId)
            ->with([
                'professions:id,person_id,department,profession,is_primary,sort_order',
                'mediaAssets:id,mediable_type,mediable_id,kind,url,caption,provider_key',
            ])
            ->first();

        if (! $person instanceof Person) {
            return [
                'exists' => false,
                'filled_fields' => [],
                'relations' => [],
            ];
        }

        return [
            'exists' => true,
            'filled_fields' => $this->filledFieldLabels($person, $this->personFieldLabels()),
            'relations' => [
                'alternate_names' => collect($person->resolvedAlternateNames())
                    ->mapWithKeys(fn (string $value): array => [Str::lower($value) => $value])
                    ->all(),
                'professions' => $person->professions
                    ->mapWithKeys(fn ($profession): array => [$profession->profession => $profession->profession.' · '.$profession->department])
                    ->all(),
                'media_assets' => $person->mediaAssets
                    ->mapWithKeys(function (MediaAsset $mediaAsset): array {
                        $kind = $mediaAsset->kind?->value ?? $mediaAsset->getRawOriginal('kind');
                        $label = collect([$kind, $mediaAsset->caption, $mediaAsset->url])->filter()->implode(' · ');

                        return [$mediaAsset->provider_key => $label];
                    })
                    ->all(),
                'payload_sections' => collect(is_array($person->imdb_payload) ? $person->imdb_payload : [])
                    ->except(['storageVersion'])
                    ->mapWithKeys(fn (mixed $value, string $key): array => [$key => Str::headline($key)])
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array{
     *     exists: bool,
     *     filled_fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }  $before
     * @param  array{
     *     exists: bool,
     *     filled_fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }  $after
     * @return array{
     *     new_record: bool,
     *     existing_fields: list<string>,
     *     filled_fields: list<string>,
     *     new_relations: array<string, list<string>>
     * }
     */
    private function diffSnapshots(array $before, array $after): array
    {
        $newRelations = [];

        foreach ($after['relations'] as $relationKey => $afterValues) {
            $beforeValues = $before['relations'][$relationKey] ?? [];
            $addedValues = array_values(array_diff_key($afterValues, $beforeValues));

            if ($addedValues !== []) {
                $newRelations[$relationKey] = $addedValues;
            }
        }

        return [
            'new_record' => ! $before['exists'] && $after['exists'],
            'existing_fields' => array_values($before['filled_fields']),
            'filled_fields' => array_values(array_diff_key($after['filled_fields'], $before['filled_fields'])),
            'new_relations' => $newRelations,
        ];
    }

    /**
     * @param  array{
     *     new_record: bool,
     *     existing_fields: list<string>,
     *     filled_fields: list<string>,
     *     new_relations: array<string, list<string>>
     * }  $diff
     * @return array{
     *     new_record: bool,
     *     existing_fields: list<string>,
     *     filled_fields: list<string>,
     *     new_relations: array<string, list<string>>,
     *     db_counts: array<string, int>,
     *     db_total_added: int
     * }
     */
    private function augmentImportDiff(string $nodeType, array $diff): array
    {
        $dbCounts = match ($nodeType) {
            'title' => [
                'titles' => $diff['new_record'] ? 1 : 0,
                'title_fields' => count($diff['filled_fields']),
                'genres' => count($diff['new_relations']['genres'] ?? []),
                'translations' => count($diff['new_relations']['translations'] ?? []),
                'companies' => count($diff['new_relations']['companies'] ?? []),
                'credits' => count($diff['new_relations']['credits'] ?? []),
                'episodes' => count($diff['new_relations']['episodes'] ?? []),
                'awards' => count($diff['new_relations']['awards'] ?? []),
                'media_assets' => count($diff['new_relations']['media_assets'] ?? []),
                'payload_sections' => count($diff['new_relations']['payload_sections'] ?? []),
            ],
            'name' => [
                'people' => $diff['new_record'] ? 1 : 0,
                'person_fields' => count($diff['filled_fields']),
                'alternate_names' => count($diff['new_relations']['alternate_names'] ?? []),
                'professions' => count($diff['new_relations']['professions'] ?? []),
                'media_assets' => count($diff['new_relations']['media_assets'] ?? []),
                'payload_sections' => count($diff['new_relations']['payload_sections'] ?? []),
            ],
            default => [],
        };

        $dbCounts = collect($dbCounts)
            ->filter(fn (int $count): bool => $count > 0)
            ->all();

        return [
            ...$diff,
            'db_counts' => $dbCounts,
            'db_total_added' => array_sum($dbCounts),
        ];
    }

    /**
     * @param  array<string, string>  $fieldLabels
     * @return array<string, string>
     */
    private function filledFieldLabels(object $model, array $fieldLabels): array
    {
        return collect($fieldLabels)
            ->filter(function (string $label, string $field) use ($model): bool {
                $value = data_get($model, $field);

                if ($value === null) {
                    return false;
                }

                if (is_string($value)) {
                    return trim($value) !== '';
                }

                return true;
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function titleFieldLabels(): array
    {
        return [
            'name' => 'Name',
            'original_name' => 'Original name',
            'title_type' => 'Title type',
            'imdb_type' => 'IMDb type',
            'release_year' => 'Release year',
            'end_year' => 'End year',
            'release_date' => 'Release date',
            'runtime_minutes' => 'Runtime minutes',
            'runtime_seconds' => 'Runtime seconds',
            'age_rating' => 'Age rating',
            'plot_outline' => 'Plot outline',
            'synopsis' => 'Synopsis',
            'tagline' => 'Tagline',
            'origin_country' => 'Origin country',
            'original_language' => 'Original language',
            'popularity_rank' => 'Popularity rank',
            'search_keywords' => 'Search keywords',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function personFieldLabels(): array
    {
        return [
            'name' => 'Name',
            'biography' => 'Biography',
            'short_biography' => 'Short biography',
            'known_for_department' => 'Known for department',
            'birth_date' => 'Birth date',
            'death_date' => 'Death date',
            'birth_place' => 'Birth place',
            'death_place' => 'Death place',
            'nationality' => 'Nationality',
            'popularity_rank' => 'Popularity rank',
            'search_keywords' => 'Search keywords',
        ];
    }

    private function summarizeReports(array $frontiers, array $nodeReports, array $visited, bool $stoppedDueToLimit): array
    {
        $processedReports = collect($nodeReports)->where('status', 'processed');
        $failedReports = collect($nodeReports)->where('status', 'failed');
        $dbCounts = $processedReports
            ->reduce(function (array $carry, array $report): array {
                foreach (data_get($report, 'import.db_counts', []) as $key => $count) {
                    $carry[$key] = (int) ($carry[$key] ?? 0) + (int) $count;
                }

                return $carry;
            }, []);

        return [
            'frontier_pages' => count($frontiers),
            'visited_nodes' => count($visited),
            'processed_nodes' => $processedReports->count(),
            'failed_nodes' => $failedReports->count(),
            'stopped_due_to_limit' => $stoppedDueToLimit,
            'by_type' => collect($nodeReports)
                ->groupBy('node_type')
                ->map(fn (Collection $reports): array => [
                    'processed' => $reports->where('status', 'processed')->count(),
                    'failed' => $reports->where('status', 'failed')->count(),
                ])
                ->all(),
            'new_records' => $processedReports
                ->filter(fn (array $report): bool => (bool) data_get($report, 'import.new_record'))
                ->count(),
            'filled_fields' => $processedReports
                ->sum(fn (array $report): int => count(data_get($report, 'import.filled_fields', []))),
            'new_relations' => $processedReports
                ->sum(function (array $report): int {
                    return collect(data_get($report, 'import.new_relations', []))
                        ->sum(fn (array $values): int => count($values));
                }),
            'db_counts' => $dbCounts,
            'db_total_added' => array_sum($dbCounts),
        ];
    }

    private function writeRunReport(string $storageRoot, array $payload): string
    {
        $directory = $storageRoot.DIRECTORY_SEPARATOR.'reports';
        File::ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.'crawl-'.now()->format('Ymd-His-u').'.json';

        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }

    private function writeNodeReport(string $artifactDirectory, array $payload): string
    {
        File::ensureDirectoryExists($artifactDirectory);
        $path = $artifactDirectory.DIRECTORY_SEPARATOR.'import-report.json';

        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }

    private function resolveStorageRoot(?string $storageRoot): string
    {
        if (! is_string($storageRoot) || trim($storageRoot) === '') {
            $storageRoot = (string) config('services.imdb.storage_root', 'storage/app/private/imdb-temp');
        }

        if (
            Str::startsWith($storageRoot, DIRECTORY_SEPARATOR)
            || preg_match('/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\)/', $storageRoot) === 1
        ) {
            return $storageRoot;
        }

        return base_path($storageRoot);
    }

    private function nextPageToken(array $payload): ?string
    {
        $pageToken = data_get($payload, 'nextPageToken');

        if (! is_string($pageToken)) {
            return null;
        }

        $pageToken = trim($pageToken);

        return $pageToken === '' ? null : $pageToken;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $values): array
    {
        return collect(is_iterable($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_array($value))
            ->values()
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
