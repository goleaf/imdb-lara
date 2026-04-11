<?php

namespace App\Actions\Import;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use SplQueue;
use Throwable;

class ImportImdbCatalogGraphAction
{
    public function __construct(
        private readonly DownloadImdbInterestPayloadAction $downloadImdbInterestPayloadAction,
        private readonly DownloadImdbNamePayloadAction $downloadImdbNamePayloadAction,
        private readonly DownloadImdbStarMeterChartAction $downloadImdbStarMeterChartAction,
        private readonly DownloadImdbTitlePayloadAction $downloadImdbTitlePayloadAction,
        private readonly FetchImdbGraphqlAction $fetchImdbGraphqlAction,
        private readonly FetchImdbJsonAction $fetchImdbJsonAction,
        private readonly ImportImdbCatalogInterestPayloadAction $importImdbCatalogInterestPayloadAction,
        private readonly ImportImdbCatalogNamePayloadAction $importImdbCatalogNamePayloadAction,
        private readonly ImportImdbCatalogTitlePayloadAction $importImdbCatalogTitlePayloadAction,
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    /**
     * @return array{
     *     failed_nodes: int,
     *     frontier_pages: int,
     *     frontier_titles: int,
     *     seed_titles: list<string>,
     *     processed_nodes: int,
     *     by_type: array<string, int>,
     *     interest_frontier_status: string,
     *     resume_store: string,
     *     starmeter_frontier_status: string,
     *     title_frontier_status: string
     * }
     */
    public function handle(?callable $progress = null): array
    {
        $seedTitles = $this->seedTitles();

        $queue = new SplQueue;
        $enqueued = [];
        $visited = [];
        $processedByType = [
            'title' => 0,
            'name' => 0,
            'interest' => 0,
        ];
        $failedNodes = 0;

        foreach ($seedTitles as $seedTitle) {
            $this->enqueueNode($queue, $enqueued, [
                'type' => 'title',
                'imdb_id' => $seedTitle,
            ]);
        }

        $titleFrontierReport = $this->bootstrapTitleFrontier($queue, $enqueued);

        if ($progress !== null) {
            $progress([
                'event' => 'frontier',
                'type' => 'titles',
                'report' => $titleFrontierReport,
            ]);
        }

        $interestFrontierReport = $this->bootstrapInterestFrontier($queue, $enqueued);

        if ($progress !== null) {
            $progress([
                'event' => 'frontier',
                'type' => 'interests',
                'report' => $interestFrontierReport,
            ]);
        }

        $starMeterFrontierReport = $this->bootstrapStarMeterFrontier($queue, $enqueued);

        if ($progress !== null) {
            $progress([
                'event' => 'frontier',
                'type' => 'starmeter',
                'report' => $starMeterFrontierReport,
            ]);
        }

        while (! $queue->isEmpty()) {
            /** @var array{type: string, imdb_id: string} $node */
            $node = $queue->dequeue();
            $nodeKey = $this->nodeKey($node['type'], $node['imdb_id']);

            if (isset($visited[$nodeKey])) {
                continue;
            }

            $visited[$nodeKey] = true;

            if ($progress !== null) {
                $progress([
                    'event' => 'node_start',
                    'node' => $node,
                    'queue_size' => $queue->count(),
                    'visited_nodes' => count($visited),
                ]);
            }

            try {
                $discoveredNodes = match ($node['type']) {
                    'title' => $this->processTitleNode($node['imdb_id']),
                    'name' => $this->processNameNode($node['imdb_id']),
                    'interest' => $this->processInterestNode($node['imdb_id']),
                    default => [],
                };

                $processedByType[$node['type']] = ($processedByType[$node['type']] ?? 0) + 1;

                $newDiscoveredCount = $this->enqueueNodes($queue, $enqueued, $discoveredNodes);

                if ($progress !== null) {
                    $progress([
                        'event' => 'node',
                        'node' => $node,
                        'discovered_count' => $newDiscoveredCount,
                        'queue_size' => $queue->count(),
                        'processed_by_type' => $processedByType,
                    ]);
                }
            } catch (Throwable $throwable) {
                $failedNodes++;
                logger()->warning(sprintf(
                    'IMDb catalog graph import failed for [%s:%s]. %s',
                    $node['type'],
                    $node['imdb_id'],
                    $throwable->getMessage(),
                ));

                if ($progress !== null) {
                    $progress([
                        'event' => 'node_failed',
                        'node' => $node,
                        'error' => $throwable->getMessage(),
                        'queue_size' => $queue->count(),
                        'failed_nodes' => $failedNodes,
                    ]);
                }
            }
        }

        return [
            'seed_titles' => $seedTitles,
            'frontier_pages' => (int) data_get($titleFrontierReport, 'pages_processed', 0),
            'frontier_titles' => (int) data_get($titleFrontierReport, 'titles_queued', 0),
            'processed_nodes' => array_sum($processedByType),
            'failed_nodes' => $failedNodes,
            'by_type' => $processedByType,
            'title_frontier_status' => (string) data_get($titleFrontierReport, 'status', 'unknown'),
            'interest_frontier_status' => (string) data_get($interestFrontierReport, 'status', 'unknown'),
            'starmeter_frontier_status' => (string) data_get($starMeterFrontierReport, 'status', 'unknown'),
            'resume_store' => (string) data_get($titleFrontierReport, 'resume_store', $this->titleFrontierResumeStoreLabel()),
        ];
    }

    /**
     * @return list<array{type: string, imdb_id: string}>
     */
    private function processTitleNode(string $imdbId): array
    {
        $download = $this->downloadImdbTitlePayloadAction->handle(
            $imdbId,
            null,
            '/titles/{titleId}',
            false,
        );

        $this->importImdbCatalogTitlePayloadAction->handle($download['payload']);

        return $this->discoverNodesFromTitlePayload($download['payload']);
    }

    /**
     * @return list<array{type: string, imdb_id: string}>
     */
    private function processNameNode(string $imdbId): array
    {
        $download = $this->downloadImdbNamePayloadAction->handle(
            $imdbId,
            null,
            false,
        );

        $this->importImdbCatalogNamePayloadAction->handle($download['payload']);

        return $this->discoverNodesFromNamePayload($download['payload']);
    }

    /**
     * @return list<array{type: string, imdb_id: string}>
     */
    private function processInterestNode(string $imdbId): array
    {
        $download = $this->downloadImdbInterestPayloadAction->handle(
            $imdbId,
            null,
            false,
        );

        $this->importImdbCatalogInterestPayloadAction->handle($download['payload']);

        return $this->discoverNodesFromInterestPayload($download['payload']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, imdb_id: string}>
     */
    private function discoverNodesFromTitlePayload(array $payload): array
    {
        $nodes = [];
        $titleType = $this->nullableString(data_get($payload, 'title.type'));

        foreach (['directors', 'writers', 'stars'] as $key) {
            foreach ($this->normalizeObjectList(data_get($payload, 'title.'.$key)) as $personPayload) {
                $personId = $this->nullableString(data_get($personPayload, 'id'));

                if ($personId !== null) {
                    $nodes[] = ['type' => 'name', 'imdb_id' => $personId];
                }
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'credits.credits')) as $creditPayload) {
            $personId = $this->nullableString(data_get($creditPayload, 'name.id'));

            if ($personId !== null) {
                $nodes[] = ['type' => 'name', 'imdb_id' => $personId];
            }
        }

        if ($titleType !== 'movie') {
            foreach ($this->normalizeObjectList(data_get($payload, 'episodes.episodes')) as $episodePayload) {
                $episodeId = $this->nullableString(data_get($episodePayload, 'id'));

                if ($episodeId !== null) {
                    $nodes[] = ['type' => 'title', 'imdb_id' => $episodeId];
                }
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'awardNominations.awardNominations')) as $awardPayload) {
            foreach ($this->normalizeObjectList(data_get($awardPayload, 'nominees')) as $nomineePayload) {
                $personId = $this->nullableString(data_get($nomineePayload, 'id'));

                if ($personId !== null) {
                    $nodes[] = ['type' => 'name', 'imdb_id' => $personId];
                }
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'title.interests')) as $interestPayload) {
            $interestId = $this->nullableString(data_get($interestPayload, 'id'));

            if ($interestId !== null) {
                $nodes[] = ['type' => 'interest', 'imdb_id' => $interestId];
            }
        }

        return $this->uniqueNodes($nodes);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, imdb_id: string}>
     */
    private function discoverNodesFromNamePayload(array $payload): array
    {
        $nodes = [];

        foreach ($this->normalizeObjectList(data_get($payload, 'filmography.credits')) as $creditPayload) {
            $titleId = $this->nullableString(data_get($creditPayload, 'title.id'));

            if ($titleId !== null) {
                $nodes[] = ['type' => 'title', 'imdb_id' => $titleId];
            }
        }

        foreach ($this->normalizeObjectList(data_get($payload, 'relationships.relationships')) as $relationshipPayload) {
            $nameId = $this->nullableString(data_get($relationshipPayload, 'name.id'));

            if ($nameId !== null) {
                $nodes[] = ['type' => 'name', 'imdb_id' => $nameId];
            }
        }

        return $this->uniqueNodes($nodes);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{type: string, imdb_id: string}>
     */
    private function discoverNodesFromInterestPayload(array $payload): array
    {
        $nodes = [];

        foreach ($this->normalizeObjectList(data_get($payload, 'interest.similarInterests')) as $similarInterestPayload) {
            $interestId = $this->nullableString(data_get($similarInterestPayload, 'id'));

            if ($interestId !== null) {
                $nodes[] = ['type' => 'interest', 'imdb_id' => $interestId];
            }
        }

        return $this->uniqueNodes($nodes);
    }

    /**
     * @return array{pages_processed: int, titles_queued: int, resume_store: string, start_url: string, status: string}
     */
    private function bootstrapTitleFrontier(SplQueue $queue, array &$enqueued): array
    {
        $startUrl = $this->resolveImdbApiUrlAction->handle($this->resolveImdbApiUrlAction->endpoint('titles.frontier'));
        $state = $this->loadTitleFrontierState();
        $resumeStore = $this->titleFrontierResumeStoreLabel();

        if (($state['exhausted'] ?? false) === true) {
            return [
                'pages_processed' => 0,
                'titles_queued' => 0,
                'start_url' => $startUrl,
                'status' => 'exhausted',
                'resume_store' => $resumeStore,
            ];
        }

        $pagesProcessed = 0;
        $titlesQueued = 0;

        try {
            while (($state['exhausted'] ?? false) !== true) {
                if ($pagesProcessed >= 250) {
                    throw new RuntimeException('IMDb titles frontier exceeded the safe page limit.');
                }

                $page = (int) ($state['page'] ?? 0) + 1;
                $payload = $this->fetchImdbJsonAction->get(
                    $startUrl,
                    $this->pageTokenQuery($state['next_page_token'] ?? null),
                ) ?? ['titles' => []];
                $frontierTitleIdsSeen = [];
                $newFrontierTitleIds = [];

                foreach ($this->normalizeObjectList(data_get($payload, 'titles')) as $titlePayload) {
                    $titleId = $this->nullableString(data_get($titlePayload, 'id'));

                    if ($titleId === null) {
                        continue;
                    }

                    if (isset($frontierTitleIdsSeen[$titleId])) {
                        continue;
                    }

                    $frontierTitleIdsSeen[$titleId] = true;

                    if ($this->enqueueNode($queue, $enqueued, ['type' => 'title', 'imdb_id' => $titleId])) {
                        $titlesQueued++;
                        $newFrontierTitleIds[] = $titleId;
                    }
                }

                if ($newFrontierTitleIds !== [] && $this->fetchImdbGraphqlAction->enabled()) {
                    $this->fetchImdbGraphqlAction->preloadTitleCores($newFrontierTitleIds);
                }

                $nextPageToken = $this->nextPageToken($payload);
                $seenPageTokens = is_array($state['seen_page_tokens'] ?? null) ? $state['seen_page_tokens'] : [];

                if ($nextPageToken !== null && array_key_exists($nextPageToken, $seenPageTokens)) {
                    logger()->warning(sprintf(
                        'IMDb titles frontier repeated page token [%s]; stopping frontier early.',
                        $nextPageToken,
                    ));

                    $nextPageToken = null;
                } elseif ($nextPageToken !== null) {
                    $seenPageTokens[$nextPageToken] = true;
                }

                $state = [
                    'page' => $page,
                    'next_page_token' => $nextPageToken,
                    'seen_page_tokens' => $seenPageTokens,
                    'exhausted' => $nextPageToken === null,
                    'last_synced_at' => now()->toIso8601String(),
                ];

                $this->storeTitleFrontierState($state);
                $pagesProcessed++;
            }
        } catch (Throwable $throwable) {
            logger()->warning(sprintf('IMDb titles frontier bootstrap failed. %s', $throwable->getMessage()));

            return [
                'pages_processed' => $pagesProcessed,
                'titles_queued' => $titlesQueued,
                'start_url' => $startUrl,
                'status' => 'stopped',
                'resume_store' => $resumeStore,
            ];
        }

        return [
            'pages_processed' => $pagesProcessed,
            'titles_queued' => $titlesQueued,
            'start_url' => $startUrl,
            'status' => 'completed',
            'resume_store' => $resumeStore,
        ];
    }

    /**
     * @return array{bridges_imported: int, interests_queued: int, status: string}
     */
    private function bootstrapInterestFrontier(SplQueue $queue, array &$enqueued): array
    {
        try {
            $payload = $this->fetchImdbJsonAction->get(
                $this->resolveImdbApiUrlAction->handle($this->resolveImdbApiUrlAction->endpoint('interests.frontier')),
            ) ?? ['categories' => []];
        } catch (Throwable $throwable) {
            logger()->warning(sprintf('IMDb interests frontier bootstrap failed. %s', $throwable->getMessage()));

            return [
                'bridges_imported' => 0,
                'interests_queued' => 0,
                'status' => 'stopped',
            ];
        }

        $bridgesImported = $this->importImdbCatalogInterestPayloadAction->handleFrontier($payload);
        $interestsQueued = 0;

        foreach ($this->normalizeObjectList(data_get($payload, 'categories')) as $categoryPayload) {
            foreach ($this->normalizeObjectList(data_get($categoryPayload, 'interests')) as $interestPayload) {
                $interestId = $this->nullableString(data_get($interestPayload, 'id'));

                if ($interestId === null) {
                    continue;
                }

                if ($this->enqueueNode($queue, $enqueued, ['type' => 'interest', 'imdb_id' => $interestId])) {
                    $interestsQueued++;
                }
            }
        }

        return [
            'bridges_imported' => $bridgesImported,
            'interests_queued' => $interestsQueued,
            'status' => 'completed',
        ];
    }

    /**
     * @return array{names_imported: int, status: string}
     */
    private function bootstrapStarMeterFrontier(SplQueue $queue, array &$enqueued): array
    {
        try {
            $chart = $this->downloadImdbStarMeterChartAction->handle();
        } catch (Throwable $throwable) {
            logger()->warning(sprintf('IMDb star meter bootstrap failed. %s', $throwable->getMessage()));

            return [
                'names_imported' => 0,
                'status' => 'stopped',
            ];
        }

        $namesImported = 0;

        foreach ($chart['names'] as $nameArtifact) {
            $this->importImdbCatalogNamePayloadAction->handle($nameArtifact['payload']);

            if ($this->enqueueNode($queue, $enqueued, ['type' => 'name', 'imdb_id' => $nameArtifact['imdb_id']])) {
                $namesImported++;
            }
        }

        return [
            'names_imported' => $namesImported,
            'status' => 'completed',
        ];
    }

    /**
     * @param  list<array{type: string, imdb_id: string}>  $nodes
     * @return list<array{type: string, imdb_id: string}>
     */
    private function uniqueNodes(array $nodes): array
    {
        $unique = [];

        foreach ($nodes as $node) {
            $unique[$this->nodeKey($node['type'], $node['imdb_id'])] = $node;
        }

        return array_values($unique);
    }

    /**
     * @param  array{type: string, imdb_id: string}  $node
     * @param  array<string, bool>  $enqueued
     */
    private function enqueueNode(SplQueue $queue, array &$enqueued, array $node): bool
    {
        $nodeKey = $this->nodeKey($node['type'], $node['imdb_id']);

        if (isset($enqueued[$nodeKey])) {
            return false;
        }

        $queue->enqueue($node);
        $enqueued[$nodeKey] = true;

        return true;
    }

    /**
     * @param  list<array{type: string, imdb_id: string}>  $nodes
     * @param  array<string, bool>  $enqueued
     */
    private function enqueueNodes(SplQueue $queue, array &$enqueued, array $nodes): int
    {
        $enqueuedCount = 0;

        foreach ($nodes as $node) {
            if ($this->enqueueNode($queue, $enqueued, $node)) {
                $enqueuedCount++;
            }
        }

        return $enqueuedCount;
    }

    /**
     * @return list<string>
     */
    private function seedTitles(): array
    {
        $seedTitles = config('services.imdb.catalog_import.seed_titles', []);

        if (! is_array($seedTitles)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null,
            $seedTitles,
        ))));
    }

    private function nodeKey(string $type, string $imdbId): string
    {
        return $type.':'.$imdbId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return array_values($items);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{page: int, next_page_token: string|null, seen_page_tokens: array<string, bool>, exhausted: bool}
     */
    private function loadTitleFrontierState(): array
    {
        $decoded = $this->titleFrontierStateStore()->get($this->titleFrontierStateCacheKey());

        if (! is_array($decoded)) {
            return [
                'page' => 0,
                'next_page_token' => null,
                'seen_page_tokens' => [],
                'exhausted' => false,
            ];
        }

        $seenPageTokens = [];

        foreach (($decoded['seen_page_tokens'] ?? []) as $token => $seen) {
            if (is_string($token) && $token !== '' && $seen) {
                $seenPageTokens[$token] = true;
            }
        }

        return [
            'page' => is_numeric($decoded['page'] ?? null) ? (int) $decoded['page'] : 0,
            'next_page_token' => $this->nullableString($decoded['next_page_token'] ?? null),
            'seen_page_tokens' => $seenPageTokens,
            'exhausted' => (bool) ($decoded['exhausted'] ?? false),
        ];
    }

    /**
     * @param  array{page: int, next_page_token: string|null, seen_page_tokens: array<string, bool>, exhausted: bool, last_synced_at?: string}  $state
     */
    private function storeTitleFrontierState(array $state): void
    {
        $this->titleFrontierStateStore()->forever($this->titleFrontierStateCacheKey(), $state);
    }

    /**
     * @return array<string, string>
     */
    private function pageTokenQuery(?string $pageToken): array
    {
        return $pageToken === null ? [] : ['pageToken' => $pageToken];
    }

    private function nextPageToken(array $payload): ?string
    {
        return $this->nullableString(data_get($payload, 'nextPageToken'));
    }

    private function titleFrontierStateStore(): CacheRepository
    {
        if ($this->databaseCacheTableExists()) {
            return Cache::store('database');
        }

        return Cache::store('array');
    }

    private function titleFrontierResumeStoreLabel(): string
    {
        return $this->databaseCacheTableExists()
            ? 'database-cache'
            : 'array-cache';
    }

    private function titleFrontierStateCacheKey(): string
    {
        $namespaceSeed = implode('|', [
            (string) config('app.env', 'production'),
            (string) config('services.imdb.base_url', ''),
            (string) config('services.imdb.storage_root', 'catalog-frontier'),
        ]);

        return 'imdb:catalog:frontier:titles:'.sha1($namespaceSeed);
    }

    private function databaseCacheTableExists(): bool
    {
        $cacheTable = (string) config('cache.stores.database.table', 'cache');
        $connection = config('cache.stores.database.connection');

        if ($cacheTable === '') {
            return false;
        }

        return Schema::connection(is_string($connection) && $connection !== '' ? $connection : null)
            ->hasTable($cacheTable);
    }
}
