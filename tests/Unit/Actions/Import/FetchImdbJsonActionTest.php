<?php

namespace Tests\Unit\Actions\Import;

use App\Actions\Import\FetchImdbJsonAction;
use Carbon\CarbonInterval;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class FetchImdbJsonActionTest extends TestCase
{
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
}
