<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Throwable;

class FetchImdbJsonAction
{
    private const DEFAULT_INTER_REQUEST_DELAY_MICROSECONDS = 1_000_000;

    private static ?int $lastRequestFinishedAtMicroseconds = null;

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>|null
     */
    public function get(string $url, array $query = [], bool $nullable = false): ?array
    {
        $response = null;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->pauseBeforeRequest();

            try {
                $response = Http::acceptJson()
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->retry([200, 500, 1000], throw: false)
                    ->get($url, array_filter($query, fn (mixed $value): bool => $value !== null));
            } finally {
                $this->markRequestAsFinished();
            }

            if ($response->status() !== 429 || $attempt === 5) {
                break;
            }

            Sleep::for($this->rateLimitDelayMilliseconds($attempt, $response))
                ->milliseconds()
                ->then(static function (): void {});
        }

        if ($response === null) {
            throw new RuntimeException(sprintf('IMDb API returned no response for [%s].', $url));
        }

        if ($nullable && $response->status() === 404) {
            return null;
        }

        $response->throw();

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('IMDb API returned a non-object payload for [%s].', $url));
        }

        return $payload;
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
        $basePayload = null;
        $items = [];
        $pageToken = null;
        $seenPageTokens = [];
        $pagesFetched = 0;

        do {
            try {
                $payload = $this->get(
                    $url,
                    $this->pageTokenQuery($pageToken),
                    $nullable && $basePayload === null,
                );
            } catch (Throwable $exception) {
                if (! $allowPartialOnFailure || ($basePayload === null && ! $nullable)) {
                    throw $exception;
                }

                logger()->warning(sprintf(
                    'IMDb pagination failed for [%s] at page token [%s]; stopping pagination early. %s',
                    $url,
                    $pageToken ?? 'initial',
                    $exception->getMessage(),
                ));

                if ($basePayload === null) {
                    return null;
                }

                break;
            }

            if ($payload === null) {
                return null;
            }

            $pagesFetched++;

            if ($pagesFetched > 250) {
                throw new RuntimeException(sprintf('IMDb pagination exceeded the safe page limit for [%s].', $url));
            }

            $basePayload ??= $payload;

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
        } while ($pageToken !== null);

        if ($basePayload === null) {
            return null;
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

    private function rateLimitDelayMilliseconds(int $attempt, mixed $response): int
    {
        $retryAfterHeader = method_exists($response, 'header')
            ? $response->header('Retry-After')
            : null;
        $retryAfterSeconds = is_numeric($retryAfterHeader) ? (int) $retryAfterHeader : 0;

        if ($retryAfterSeconds > 0) {
            return $retryAfterSeconds * 1000;
        }

        return min(15000, $attempt * 3000);
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
}
