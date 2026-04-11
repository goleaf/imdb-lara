<?php

namespace Tests\Unit\Actions\Import;

use App\Actions\Import\FetchImdbJsonAction;
use Carbon\CarbonInterval;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class FetchImdbJsonActionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_waits_one_second_between_consecutive_requests(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 1_000_000);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => Http::response([
            'url' => $request->url(),
        ], 200));

        $action = app(FetchImdbJsonAction::class);

        $firstPayload = $action->get('https://api.imdbapi.dev/titles/tt0133093');
        $secondPayload = $action->get('https://api.imdbapi.dev/titles/tt0234215');
        $thirdPayload = $action->get('https://api.imdbapi.dev/titles/tt0242653');

        $this->assertSame('https://api.imdbapi.dev/titles/tt0133093', $firstPayload['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0234215', $secondPayload['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0242653', $thirdPayload['url']);

        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
        Sleep::assertSlept(
            fn (CarbonInterval $duration): bool => $duration->totalMicroseconds >= 900000
                && $duration->totalMicroseconds <= CarbonInterval::second()->totalMicroseconds,
            2,
        );
    }

    public function test_it_skips_inter_request_sleep_when_delay_is_disabled_in_config(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 0);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => Http::response([
            'url' => $request->url(),
        ], 200));

        $action = app(FetchImdbJsonAction::class);

        $action->get('https://api.imdbapi.dev/titles/tt0133093');
        $action->get('https://api.imdbapi.dev/titles/tt0234215');
        $action->get('https://api.imdbapi.dev/titles/tt0242653');

        Http::assertSentCount(3);
        Sleep::assertNeverSlept();
    }

    public function test_it_fetches_requests_concurrently_without_inter_request_sleep_inside_a_batch(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 1_000_000);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => Http::response([
            'url' => $request->url(),
        ], 200));

        $action = app(FetchImdbJsonAction::class);

        $payloads = $action->getConcurrent([
            ['key' => 'one', 'url' => 'https://api.imdbapi.dev/titles/tt0133093'],
            ['key' => 'two', 'url' => 'https://api.imdbapi.dev/titles/tt0234215'],
            ['key' => 'three', 'url' => 'https://api.imdbapi.dev/titles/tt0242653'],
            ['key' => 'four', 'url' => 'https://api.imdbapi.dev/titles/tt0245574'],
            ['key' => 'five', 'url' => 'https://api.imdbapi.dev/titles/tt0365467'],
            ['key' => 'six', 'url' => 'https://api.imdbapi.dev/titles/tt0434409'],
        ]);

        $this->assertSame('https://api.imdbapi.dev/titles/tt0133093', $payloads['one']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0234215', $payloads['two']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0242653', $payloads['three']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0245574', $payloads['four']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0365467', $payloads['five']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0434409', $payloads['six']['url']);

        Http::assertSentCount(6);
        Sleep::assertNeverSlept();
    }

    public function test_it_retries_after_one_second_when_response_body_says_too_many_requests(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 0);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/titles/tt0133093' => Http::sequence()
                ->push('Too Many Requests', 200)
                ->push([
                    'url' => 'https://api.imdbapi.dev/titles/tt0133093',
                ], 200),
        ]);

        $action = app(FetchImdbJsonAction::class);

        $payload = $action->get('https://api.imdbapi.dev/titles/tt0133093');

        $this->assertSame('https://api.imdbapi.dev/titles/tt0133093', $payload['url']);
        Http::assertSentCount(2);
        Sleep::assertSlept(
            fn (CarbonInterval $duration): bool => (int) $duration->totalMicroseconds === 1_000_000,
            1,
        );
    }

    public function test_it_retries_connection_failures_five_times_before_succeeding(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 0);
        config()->set('services.imdb.retry_attempts', 5);
        config()->set('services.imdb.retry_delay_milliseconds', 250);

        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$attempts) {
            $attempts++;

            if ($attempts < 5) {
                throw new ConnectionException('cURL error 2: getaddrinfo() thread failed to start');
            }

            return Http::response([
                'url' => $request->url(),
            ], 200);
        });

        $action = app(FetchImdbJsonAction::class);

        $payload = $action->get('https://api.imdbapi.dev/names/nm3142672');

        $this->assertSame('https://api.imdbapi.dev/names/nm3142672', $payload['url']);
        $this->assertSame(5, $attempts);
        Sleep::assertSleptTimes(4);
        Sleep::assertSlept(
            fn (CarbonInterval $duration): bool => (int) $duration->totalMicroseconds === 250_000,
            4,
        );
    }

    public function test_it_retries_failed_responses_five_times_before_succeeding(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 0);
        config()->set('services.imdb.retry_attempts', 5);
        config()->set('services.imdb.retry_delay_milliseconds', 250);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/names/nm3142672' => Http::sequence()
                ->push(['error' => 'temporary'], 500)
                ->push(['error' => 'temporary'], 500)
                ->push(['error' => 'temporary'], 500)
                ->push(['error' => 'temporary'], 500)
                ->push([
                    'url' => 'https://api.imdbapi.dev/names/nm3142672',
                ], 200),
        ]);

        $action = app(FetchImdbJsonAction::class);

        $payload = $action->get('https://api.imdbapi.dev/names/nm3142672');

        $this->assertSame('https://api.imdbapi.dev/names/nm3142672', $payload['url']);
        Http::assertSentCount(5);
        Sleep::assertSleptTimes(4);
        Sleep::assertSlept(
            fn (CarbonInterval $duration): bool => (int) $duration->totalMicroseconds === 250_000,
            4,
        );
    }

    public function test_it_retries_concurrent_requests_after_one_second_when_response_says_too_many_requests(): void
    {
        config()->set('services.imdb.inter_request_delay_microseconds', 0);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/titles/tt0133093' => Http::sequence()
                ->push('Too Many Requests', 200)
                ->push([
                    'url' => 'https://api.imdbapi.dev/titles/tt0133093',
                ], 200),
            'api.imdbapi.dev/titles/tt0234215' => Http::response([
                'url' => 'https://api.imdbapi.dev/titles/tt0234215',
            ], 200),
        ]);

        $action = app(FetchImdbJsonAction::class);

        $payloads = $action->getConcurrent([
            ['key' => 'first', 'url' => 'https://api.imdbapi.dev/titles/tt0133093'],
            ['key' => 'second', 'url' => 'https://api.imdbapi.dev/titles/tt0234215'],
        ], 2);

        $this->assertSame('https://api.imdbapi.dev/titles/tt0133093', $payloads['first']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0234215', $payloads['second']['url']);
        Http::assertSentCount(3);
        Sleep::assertSlept(
            fn (CarbonInterval $duration): bool => (int) $duration->totalMicroseconds === 1_000_000,
            1,
        );
    }

    public function test_it_reuses_cached_get_payloads_without_sending_duplicate_requests(): void
    {
        config()->set('cache.default', 'array');
        config()->set('services.imdb.http_cache.enabled', true);
        config()->set('services.imdb.http_cache.ttl_seconds', 3600);
        config()->set('services.imdb.inter_request_delay_microseconds', 0);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => Http::response([
            'url' => $request->url(),
        ], 200));

        $action = app(FetchImdbJsonAction::class);

        $firstPayload = $action->get('https://api.imdbapi.dev/titles/tt0133093');
        $secondPayload = $action->get('https://api.imdbapi.dev/titles/tt0133093');

        $this->assertSame($firstPayload, $secondPayload);
        Http::assertSentCount(1);
    }

    public function test_it_skips_cached_requests_inside_concurrent_batches(): void
    {
        config()->set('cache.default', 'array');
        config()->set('services.imdb.http_cache.enabled', true);
        config()->set('services.imdb.http_cache.ttl_seconds', 3600);
        config()->set('services.imdb.inter_request_delay_microseconds', 0);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => Http::response([
            'url' => $request->url(),
        ], 200));

        $action = app(FetchImdbJsonAction::class);

        $action->get('https://api.imdbapi.dev/titles/tt0133093');

        $payloads = $action->getConcurrent([
            ['key' => 'cached', 'url' => 'https://api.imdbapi.dev/titles/tt0133093'],
            ['key' => 'fresh', 'url' => 'https://api.imdbapi.dev/titles/tt0234215'],
        ], 2);

        $this->assertSame('https://api.imdbapi.dev/titles/tt0133093', $payloads['cached']['url']);
        $this->assertSame('https://api.imdbapi.dev/titles/tt0234215', $payloads['fresh']['url']);
        Http::assertSentCount(2);
    }

    public function test_it_reuses_cached_post_payloads_without_sending_duplicate_requests(): void
    {
        config()->set('cache.default', 'array');
        config()->set('services.imdb.http_cache.enabled', true);
        config()->set('services.imdb.http_cache.ttl_seconds', 3600);
        config()->set('services.imdb.inter_request_delay_microseconds', 0);

        Http::preventStrayRequests();
        Http::fake(fn () => Http::response([
            'data' => [
                'ping' => true,
            ],
        ], 200));

        $action = app(FetchImdbJsonAction::class);

        $firstPayload = $action->post('https://graph.imdbapi.dev/v1', [
            'query' => 'query { ping }',
            'variables' => [],
        ]);
        $secondPayload = $action->post('https://graph.imdbapi.dev/v1', [
            'query' => 'query { ping }',
            'variables' => [],
        ]);

        $this->assertSame($firstPayload, $secondPayload);
        Http::assertSentCount(1);
    }
}
