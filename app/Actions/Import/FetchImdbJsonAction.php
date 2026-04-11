<?php

namespace App\Actions\Import;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FetchImdbJsonAction
{
    private const DEFAULT_INTER_REQUEST_DELAY_MICROSECONDS = 1_000_000;

    private const DEFAULT_BATCH_CONCURRENCY = 5;

    private const DEFAULT_HTTP_CACHE_TTL_SECONDS = 86400;

    private const DEFAULT_RETRY_ATTEMPTS = 5;

    private const DEFAULT_RETRY_DELAY_MILLISECONDS = 1000;

    private static ?int $lastRequestFinishedAtMicroseconds = null;

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>|null
     */
    public function get(string $url, array $query = [], bool $nullable = false): ?array
    {
        $query = array_filter($query, fn (mixed $value): bool => $value !== null);
        $cacheKey = $this->httpCacheKey('GET', $url, [
            'query' => $query,
            'nullable' => $nullable,
        ]);
        $cachedPayload = $this->getCachedHttpPayload($cacheKey);

        if ($cachedPayload['hit']) {
            return $cachedPayload['payload'];
        }

        $response = null;
        $lastException = null;
        $maxAttempts = $this->requestRetryAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->pauseBeforeRequest();

            try {
                $response = $this->newRequest()->get($url, $query);
                $lastException = null;
            } catch (Throwable $exception) {
                $response = null;
                $lastException = $exception;
            } finally {
                $this->markRequestAsFinished();
            }

            if ($nullable && $response?->status() === 404) {
                $this->rememberHttpPayload($cacheKey, null);

                return null;
            }

            if ($attempt < $maxAttempts && $this->shouldRetryRequest($response, $lastException)) {
                $this->sleepBeforeRetry();

                continue;
            }

            if ($lastException instanceof Throwable) {
                throw $lastException;
            }

            if (! $response instanceof Response) {
                throw new RuntimeException(sprintf('IMDb API returned no response for [%s].', $url));
            }

            $response->throw();

            $payload = $response->json();

            if (! is_array($payload)) {
                throw new RuntimeException(sprintf('IMDb API returned a non-object payload for [%s].', $url));
            }

            $this->rememberHttpPayload($cacheKey, $payload);

            return $payload;
        }

        throw new RuntimeException(sprintf('IMDb API returned no response for [%s].', $url));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $url, array $payload): array
    {
        $cacheKey = $this->httpCacheKey('POST', $url, $payload);
        $cachedPayload = $this->getCachedHttpPayload($cacheKey);

        if ($cachedPayload['hit'] && is_array($cachedPayload['payload'])) {
            return $cachedPayload['payload'];
        }

        $response = null;
        $lastException = null;
        $maxAttempts = $this->requestRetryAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->pauseBeforeRequest();

            try {
                $response = $this->newRequest()->post($url, $payload);
                $lastException = null;
            } catch (Throwable $exception) {
                $response = null;
                $lastException = $exception;
            } finally {
                $this->markRequestAsFinished();
            }

            if ($attempt < $maxAttempts && $this->shouldRetryRequest($response, $lastException)) {
                $this->sleepBeforeRetry();

                continue;
            }

            if ($lastException instanceof Throwable) {
                throw $lastException;
            }

            if (! $response instanceof Response) {
                throw new RuntimeException(sprintf('IMDb API returned no response for [%s].', $url));
            }

            $response->throw();

            $decodedPayload = $response->json();

            if (! is_array($decodedPayload)) {
                throw new RuntimeException(sprintf('IMDb API returned a non-object payload for [%s].', $url));
            }

            $this->rememberHttpPayload($cacheKey, $decodedPayload);

            return $decodedPayload;
        }

        throw new RuntimeException(sprintf('IMDb API returned no response for [%s].', $url));
    }

    /**
     * @param  list<array{
     *     key: string,
     *     nullable?: bool,
     *     query?: array<string, scalar|null>,
     *     url: string
     * }>  $requests
     * @return array<string, array<string, mixed>|null>
     */
    public function getConcurrent(array $requests, ?int $concurrency = null): array
    {
        if ($requests === []) {
            return [];
        }

        $concurrency = max(1, $concurrency ?? $this->defaultBatchConcurrency());

        $pendingRequests = collect($requests)
            ->mapWithKeys(function (array $request): array {
                $query = array_filter(
                    is_array($request['query'] ?? null) ? $request['query'] : [],
                    fn (mixed $value): bool => $value !== null,
                );

                return [
                    $request['key'] => [
                        'url' => $request['url'],
                        'query' => $query,
                        'nullable' => (bool) ($request['nullable'] ?? false),
                        'cache_key' => $this->httpCacheKey('GET', $request['url'], [
                            'query' => $query,
                            'nullable' => (bool) ($request['nullable'] ?? false),
                        ]),
                    ],
                ];
            })
            ->all();

        $responses = [];
        $attempts = array_fill_keys(array_keys($pendingRequests), 0);
        $batchStarted = false;

        foreach ($pendingRequests as $key => $request) {
            $cachedPayload = $this->getCachedHttpPayload($request['cache_key']);

            if (! $cachedPayload['hit']) {
                continue;
            }

            $responses[$key] = $cachedPayload['payload'];
            unset($pendingRequests[$key]);
        }

        while ($pendingRequests !== []) {
            if (! $batchStarted) {
                $this->pauseBeforeRequest();
                $batchStarted = true;
            }

            $roundResponses = [];

            try {
                $roundResponses = Http::pool(function (Pool $pool) use ($pendingRequests): array {
                    return collect($pendingRequests)
                        ->map(function (array $request, string $key) use ($pool): mixed {
                            return $pool->as($key)
                                ->acceptJson()
                                ->connectTimeout(10)
                                ->timeout(1)
                                ->get(
                                    $request['url'],
                                    array_filter($request['query'], fn (mixed $value): bool => $value !== null),
                                );
                        })
                        ->all();
                }, concurrency: max(1, $concurrency));
            } catch (Throwable $exception) {
                foreach ($pendingRequests as $key => $request) {
                    $attempts[$key]++;

                    if ($attempts[$key] >= $this->requestRetryAttempts()) {
                        throw $exception;
                    }
                }

                $this->sleepBeforeRetry();

                continue;
            } finally {
                $this->markRequestAsFinished();
            }

            $retryRequests = [];

            foreach ($pendingRequests as $key => $request) {
                $attempts[$key]++;

                $response = $roundResponses[$key] ?? null;

                if (! $response instanceof Response) {
                    throw new RuntimeException(sprintf('IMDb API returned no response for [%s].', $request['url']));
                }

                if ($request['nullable'] && $response->status() === 404) {
                    $this->rememberHttpPayload($request['cache_key'], null);
                    $responses[$key] = null;

                    continue;
                }

                if (
                    $attempts[$key] < $this->requestRetryAttempts()
                    && $this->shouldRetryRequest($response)
                ) {
                    $retryRequests[$key] = $request;

                    continue;
                }

                if ($request['nullable'] && $response->failed()) {
                    logger()->warning(sprintf(
                        'IMDb optional concurrent endpoint failed for [%s]; continuing without this artifact. HTTP %s.',
                        $request['url'],
                        $response->status(),
                    ));

                    $this->rememberHttpPayload($request['cache_key'], null);
                    $responses[$key] = null;

                    continue;
                }

                $response->throw();

                $payload = $response->json();

                if (! is_array($payload)) {
                    throw new RuntimeException(sprintf('IMDb API returned a non-object payload for [%s].', $request['url']));
                }

                $this->rememberHttpPayload($request['cache_key'], $payload);
                $responses[$key] = $payload;
            }

            if ($retryRequests === []) {
                break;
            }

            $this->sleepBeforeRetry();

            $pendingRequests = $retryRequests;
        }

        return $responses;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function paginate(
        string $url,
        string $itemsKey,
        bool $nullable = false,
        bool $allowPartialOnFailure = false,
    ): ?array {
        $initialPayload = $this->get($url, nullable: $nullable);

        if ($initialPayload === null) {
            return null;
        }

        return $this->completePagination($url, $itemsKey, $initialPayload, $allowPartialOnFailure);
    }

    /**
     * @param  array<string, mixed>  $initialPayload
     * @return array<string, mixed>
     */
    public function completePagination(
        string $url,
        string $itemsKey,
        array $initialPayload,
        bool $allowPartialOnFailure = false,
    ): array {
        $basePayload = $initialPayload;
        $items = $this->normalizeObjectList(data_get($initialPayload, $itemsKey));
        $pageToken = $this->normalizeNextPageToken($initialPayload);
        $seenPageTokens = $pageToken === null ? [] : [$pageToken => true];
        $pagesFetched = 1;

        while ($pageToken !== null) {
            try {
                $payload = $this->get($url, $this->pageTokenQuery($pageToken));
            } catch (Throwable $exception) {
                if (! $allowPartialOnFailure) {
                    throw $exception;
                }

                logger()->warning(sprintf(
                    'IMDb pagination failed for [%s] at page token [%s]; stopping pagination early. %s',
                    $url,
                    $pageToken,
                    $exception->getMessage(),
                ));

                break;
            }

            if ($payload === null) {
                break;
            }

            $pagesFetched++;

            if ($pagesFetched > 250) {
                throw new RuntimeException(sprintf('IMDb pagination exceeded the safe page limit for [%s].', $url));
            }

            foreach ($this->normalizeObjectList(data_get($payload, $itemsKey)) as $item) {
                $items[] = $item;
            }

            $pageToken = $this->normalizeNextPageToken($payload);

            if ($pageToken !== null && array_key_exists($pageToken, $seenPageTokens)) {
                logger()->warning(sprintf(
                    'IMDb pagination repeated page token [%s] for [%s]; stopping pagination early.',
                    $pageToken,
                    $url,
                ));
                $pageToken = null;
            } elseif ($pageToken !== null) {
                $seenPageTokens[$pageToken] = true;
            }
        }

        $basePayload[$itemsKey] = $items;
        unset($basePayload['nextPageToken']);

        return $basePayload;
    }

    private function pauseBeforeRequest(): void
    {
        if (self::$lastRequestFinishedAtMicroseconds === null) {
            return;
        }

        $elapsedMicroseconds = $this->currentTimeMicroseconds() - self::$lastRequestFinishedAtMicroseconds;
        $remainingMicroseconds = $this->interRequestDelayMicroseconds() - $elapsedMicroseconds;

        if ($remainingMicroseconds > 0) {
            Sleep::for($remainingMicroseconds)
                ->microseconds()
                ->then(static function (): void {});
        }
    }

    private function markRequestAsFinished(): void
    {
        self::$lastRequestFinishedAtMicroseconds = $this->currentTimeMicroseconds();
    }

    private function newRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(30);
    }

    private function shouldRetryTooManyRequests(Response $response): bool
    {
        if ($response->tooManyRequests()) {
            return true;
        }

        return str_contains(
            Str::lower(trim($response->body())),
            'too many requests',
        );
    }

    private function shouldRetryRequest(?Response $response, ?Throwable $exception = null): bool
    {
        if ($exception instanceof Throwable) {
            return true;
        }

        if (! $response instanceof Response) {
            return false;
        }

        if ($this->shouldRetryTooManyRequests($response)) {
            return true;
        }

        return $response->failed();
    }

    private function currentTimeMicroseconds(): int
    {
        return intdiv(hrtime(true), 1000);
    }

    private function interRequestDelayMicroseconds(): int
    {
        return max(
            0,
            (int) config(
                'services.imdb.inter_request_delay_microseconds',
                self::DEFAULT_INTER_REQUEST_DELAY_MICROSECONDS,
            ),
        );
    }

    private function defaultBatchConcurrency(): int
    {
        return max(
            1,
            (int) config(
                'services.imdb.default_batch_concurrency',
                self::DEFAULT_BATCH_CONCURRENCY,
            ),
        );
    }

    private function requestRetryAttempts(): int
    {
        return max(
            1,
            (int) config('services.imdb.retry_attempts', self::DEFAULT_RETRY_ATTEMPTS),
        );
    }

    private function sleepBeforeRetry(): void
    {
        Sleep::for($this->requestRetryDelayMilliseconds())
            ->milliseconds()
            ->then(static function (): void {});
    }

    private function requestRetryDelayMilliseconds(): int
    {
        return max(
            0,
            (int) config(
                'services.imdb.retry_delay_milliseconds',
                self::DEFAULT_RETRY_DELAY_MILLISECONDS,
            ),
        );
    }

    private function httpCacheEnabled(): bool
    {
        return (bool) config('services.imdb.http_cache.enabled', false);
    }

    private function httpCacheTtlSeconds(): int
    {
        return max(
            0,
            (int) config(
                'services.imdb.http_cache.ttl_seconds',
                self::DEFAULT_HTTP_CACHE_TTL_SECONDS,
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private function pageTokenQuery(?string $pageToken): array
    {
        if ($pageToken === null) {
            return [];
        }

        return ['pageToken' => $pageToken];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function normalizeNextPageToken(array $payload): ?string
    {
        $nextPageToken = data_get($payload, 'nextPageToken');

        if (! is_string($nextPageToken)) {
            return null;
        }

        $nextPageToken = trim($nextPageToken);

        return $nextPageToken === '' ? null : $nextPageToken;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $values): array
    {
        $items = [];

        foreach (is_iterable($values) ? $values : [] as $value) {
            if (is_array($value)) {
                $items[] = $value;
            }
        }

        return array_values($items);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function rememberHttpPayload(string $cacheKey, ?array $payload): void
    {
        if (! $this->httpCacheEnabled()) {
            return;
        }

        $ttlSeconds = $this->httpCacheTtlSeconds();

        if ($ttlSeconds <= 0) {
            return;
        }

        Cache::put($cacheKey, [
            'cached' => true,
            'payload' => $payload,
        ], now()->addSeconds($ttlSeconds));
    }

    /**
     * @return array{hit: bool, payload: array<string, mixed>|null}
     */
    private function getCachedHttpPayload(string $cacheKey): array
    {
        if (! $this->httpCacheEnabled()) {
            return ['hit' => false, 'payload' => null];
        }

        $cached = Cache::get($cacheKey);

        if (! is_array($cached) || ! array_key_exists('cached', $cached)) {
            return ['hit' => false, 'payload' => null];
        }

        $payload = $cached['payload'] ?? null;

        return [
            'hit' => true,
            'payload' => is_array($payload) ? $payload : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function httpCacheKey(string $method, string $url, array $payload = []): string
    {
        $normalizedPayload = $this->normalizeCachePayload($payload);

        return 'imdb:http:'.hash('sha256', json_encode([
            'method' => $method,
            'url' => $url,
            'payload' => $normalizedPayload,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeCachePayload(array $payload): array
    {
        $normalized = $payload;
        ksort($normalized);

        foreach ($normalized as $key => $value) {
            if (is_array($value) && ! array_is_list($value)) {
                $normalized[$key] = $this->normalizeCachePayload($value);
            }
        }

        return $normalized;
    }
}
