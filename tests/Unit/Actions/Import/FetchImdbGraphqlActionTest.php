<?php

namespace Tests\Unit\Actions\Import;

use App\Actions\Import\FetchImdbGraphqlAction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchImdbGraphqlActionTest extends TestCase
{
    public function test_it_preloads_multiple_title_cores_in_one_graphql_request(): void
    {
        config()->set('services.imdb.graphql.enabled', true);
        config()->set('services.imdb.graphql.url', 'https://graph.imdbapi.dev/v1');
        config()->set('services.imdb.title_batch_concurrency', 5);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $this->assertSame('https://graph.imdbapi.dev/v1', $request->url());

            $query = (string) data_get($request->data(), 'query', '');

            $this->assertStringContainsString('title_0: title(id: "tt0133093")', $query);
            $this->assertStringContainsString('title_1: title(id: "tt0234215")', $query);

            return Http::response([
                'data' => [
                    'title_0' => [
                        'credits' => [
                            [
                                'category' => 'actor',
                                'characters' => ['Neo'],
                                'name' => [
                                    'id' => 'nm0000206',
                                    'display_name' => 'Keanu Reeves',
                                    'avatars' => [],
                                ],
                            ],
                        ],
                        'certificates' => [
                            [
                                'rating' => 'R',
                                'country' => [
                                    'code' => 'US',
                                    'name' => 'United States',
                                ],
                            ],
                        ],
                    ],
                    'title_1' => [
                        'credits' => [
                            [
                                'category' => 'actor',
                                'characters' => ['Neo'],
                                'name' => [
                                    'id' => 'nm0000401',
                                    'display_name' => 'Carrie-Anne Moss',
                                    'avatars' => [],
                                ],
                            ],
                        ],
                        'certificates' => [
                            [
                                'rating' => 'R',
                                'country' => [
                                    'code' => 'US',
                                    'name' => 'United States',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200);
        });

        $action = app(FetchImdbGraphqlAction::class);

        $action->preloadTitleCores(['tt0133093', 'tt0234215']);

        $firstPayload = $action->fetchTitleCore('tt0133093');
        $secondPayload = $action->fetchTitleCore('tt0234215');

        $this->assertSame('Keanu Reeves', data_get($firstPayload, 'credits.credits.0.name.displayName'));
        $this->assertSame('Carrie-Anne Moss', data_get($secondPayload, 'credits.credits.0.name.displayName'));
        Http::assertSentCount(1);
    }
}
