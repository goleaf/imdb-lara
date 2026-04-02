<?php

namespace Tests\Feature\Feature\Feature\Import;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImdbTitleDownloadCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_downloads_bundle_payloads_and_tracks_raw_imports(): void
    {
        $directory = storage_path('framework/testing/imdb-download');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake([
            'api.imdbapi.dev/titles/tt0133093' => Http::response($this->titlePayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/credits*' => Http::response($this->creditsPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/releaseDates*' => Http::response($this->releaseDatesPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/akas*' => Http::response($this->akasPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/images*' => Http::response($this->imagesPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/videos*' => Http::response($this->videosPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/awardNominations*' => Http::response($this->awardPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/parentsGuide*' => Http::response(['advisories' => []], 200),
            'api.imdbapi.dev/titles/tt0133093/certificates*' => Http::response($this->certificatesPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/companyCredits*' => Http::response($this->companyCreditsPayload(), 200),
            'api.imdbapi.dev/titles/tt0133093/boxOffice*' => Http::response($this->boxOfficePayload(), 200),
        ]);

        $this->downloadImdbTitlePayload('tt0133093', $directory);

        $path = $directory.DIRECTORY_SEPARATOR.'tt0133093.json';
        $savedPayload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFileExists($path);
        $this->assertSame(3, $savedPayload['schemaVersion']);
        $this->assertSame('The Matrix', $savedPayload['title']['primaryTitle']);
        $this->assertSame('Theatrical Trailer', $savedPayload['videos']['videos'][0]['name']);
        $this->assertSame([], $savedPayload['names']);
        $this->assertSame([], $savedPayload['interests']);
        $this->assertNull($savedPayload['seasons']);
        $this->assertNull($savedPayload['episodes']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'title.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'credits.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'release-dates.json');
        $this->assertFileDoesNotExist($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'seasons.json');
        $this->assertFileDoesNotExist($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'episodes.json');
        $this->assertFileDoesNotExist($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'names'.DIRECTORY_SEPARATOR.'nm0000206'.DIRECTORY_SEPARATOR.'details.json');
        $this->assertFileDoesNotExist($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'interests'.DIRECTORY_SEPARATOR.'in0000001.json');
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'manifest.json');
        Http::assertSentCount(11);
        $this->assertDatabaseHas('imdb_title_imports', [
            'imdb_id' => 'tt0133093',
        ]);
    }

    public function test_command_uses_graphql_to_replace_rest_credits_and_certificates_requests(): void
    {
        $directory = storage_path('framework/testing/imdb-download-graphql');
        File::deleteDirectory($directory);

        config()->set('services.imdb.graphql.enabled', true);
        config()->set('services.imdb.graphql.url', 'https://graph.imdbapi.dev/v1');

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            if ($url === 'https://graph.imdbapi.dev/v1') {
                $this->assertSame('tt0133093', data_get($request->data(), 'variables.id'));

                return Http::response([
                    'data' => [
                        'title' => [
                            'credits' => [
                                [
                                    'category' => 'actor',
                                    'characters' => ['Neo'],
                                    'name' => [
                                        'id' => 'nm0000206',
                                        'display_name' => 'Keanu Reeves',
                                        'avatars' => [
                                            [
                                                'url' => 'https://example.com/keanu-avatar.jpg',
                                                'width' => 800,
                                                'height' => 1200,
                                            ],
                                        ],
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
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/credits') {
                $this->fail('REST credits endpoint should not be called when GraphQL optimization is enabled.');
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/certificates') {
                $this->fail('REST certificates endpoint should not be called when GraphQL optimization is enabled.');
            }

            return match ($url) {
                'https://api.imdbapi.dev/titles/tt0133093' => Http::response($this->titlePayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/releaseDates' => Http::response($this->releaseDatesPayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/akas' => Http::response($this->akasPayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/images' => Http::response($this->imagesPayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/videos' => Http::response($this->videosPayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/awardNominations' => Http::response($this->awardPayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/parentsGuide' => Http::response(['advisories' => []], 200),
                'https://api.imdbapi.dev/titles/tt0133093/companyCredits' => Http::response($this->companyCreditsPayload(), 200),
                'https://api.imdbapi.dev/titles/tt0133093/boxOffice' => Http::response($this->boxOfficePayload(), 200),
                default => $this->fail('Unexpected HTTP request: '.$url),
            };
        });

        $this->downloadImdbTitlePayload('tt0133093', $directory);

        $savedPayload = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt0133093.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Keanu Reeves', data_get($savedPayload, 'credits.credits.0.name.displayName'));
        $this->assertSame('R', data_get($savedPayload, 'certificates.certificates.0.rating'));
        $this->assertSame('https://graph.imdbapi.dev/v1', data_get($savedPayload, 'artifacts.credits.url'));
        $this->assertSame('https://graph.imdbapi.dev/v1', data_get($savedPayload, 'artifacts.certificates.url'));
        Http::assertSentCount(10);
    }

    public function test_command_follows_next_page_token_by_sending_it_back_as_page_token_query_param(): void
    {
        $directory = storage_path('framework/testing/imdb-download-paginated');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093') {
                return Http::response($this->titlePayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/credits') {
                $this->assertSame([], $request->data());

                return Http::response([
                    'credits' => [
                        [
                            'name' => [
                                'id' => 'nm0000206',
                                'displayName' => 'Keanu Reeves',
                                'primaryProfessions' => ['actor'],
                            ],
                            'category' => 'actor',
                            'characters' => ['Neo'],
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'credits-page-2',
                ], 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/credits?pageToken=credits-page-2') {
                $this->assertSame(['pageToken' => 'credits-page-2'], $request->data());

                return Http::response([
                    'credits' => [
                        [
                            'name' => [
                                'id' => 'nm0005251',
                                'displayName' => 'Carrie-Anne Moss',
                                'primaryProfessions' => ['actress'],
                            ],
                            'category' => 'actress',
                            'characters' => ['Trinity'],
                        ],
                    ],
                    'totalCount' => 2,
                ], 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/releaseDates') {
                return Http::response($this->releaseDatesPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/akas') {
                return Http::response($this->akasPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/images') {
                return Http::response($this->imagesPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/videos') {
                return Http::response($this->videosPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/awardNominations') {
                return Http::response($this->awardPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/parentsGuide') {
                return Http::response(['advisories' => []], 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/certificates') {
                return Http::response($this->certificatesPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/companyCredits') {
                return Http::response($this->companyCreditsPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/boxOffice') {
                return Http::response($this->boxOfficePayload(), 200);
            }

            $this->fail('Unexpected HTTP request: '.$url);
        });

        $this->downloadImdbTitlePayload('tt0133093', $directory);

        $savedPayload = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt0133093.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(2, $savedPayload['credits']['credits']);
        $this->assertSame('Carrie-Anne Moss', $savedPayload['credits']['credits'][1]['name']['displayName']);
        $this->assertArrayNotHasKey('nextPageToken', $savedPayload['credits']);
        $this->assertFileExists($directory.DIRECTORY_SEPARATOR.'tt0133093'.DIRECTORY_SEPARATOR.'credits.json');
    }

    public function test_command_continues_when_optional_title_endpoints_fail_after_partial_download(): void
    {
        $directory = storage_path('framework/testing/imdb-download-optional-endpoint-failure');
        File::deleteDirectory($directory);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = $request->url();

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093') {
                return Http::response($this->titlePayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/credits') {
                return Http::response($this->creditsPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/releaseDates') {
                return Http::response($this->releaseDatesPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/akas') {
                return Http::response($this->akasPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/images') {
                return Http::response([
                    'images' => [
                        [
                            'url' => 'https://example.com/matrix-still-1.jpg',
                            'width' => 1280,
                            'height' => 720,
                        ],
                    ],
                    'totalCount' => 2,
                    'nextPageToken' => 'title-images-page-2',
                ], 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/images?pageToken=title-images-page-2') {
                return Http::response([
                    'code' => 13,
                    'message' => 'upstream pagination timeout',
                ], 500);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/videos') {
                return Http::response($this->videosPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/awardNominations') {
                return Http::response($this->awardPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/parentsGuide') {
                return Http::response(['advisories' => []], 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/certificates') {
                return Http::response($this->certificatesPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/companyCredits') {
                return Http::response($this->companyCreditsPayload(), 200);
            }

            if ($url === 'https://api.imdbapi.dev/titles/tt0133093/boxOffice') {
                return Http::response($this->boxOfficePayload(), 200);
            }

            $this->fail('Unexpected HTTP request: '.$url);
        });

        $this->downloadImdbTitlePayload('tt0133093', $directory);

        $savedPayload = json_decode(
            File::get($directory.DIRECTORY_SEPARATOR.'tt0133093.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(1, data_get($savedPayload, 'images.images', []));
        $this->assertArrayNotHasKey('nextPageToken', $savedPayload['images']);
        $this->assertSame([], $savedPayload['names']);
        $this->assertSame([], $savedPayload['interests']);
        $this->assertArrayNotHasKey('relationships', data_get($savedPayload, 'names.nm0000206', []));
    }

    /**
     * @return array<string, mixed>
     */
    private function titlePayload(): array
    {
        return [
            'id' => 'tt0133093',
            'type' => 'movie',
            'primaryTitle' => 'The Matrix',
            'primaryImage' => [
                'url' => 'https://example.com/matrix.jpg',
                'width' => 2100,
                'height' => 3156,
            ],
            'startYear' => 1999,
            'runtimeSeconds' => 8160,
            'genres' => ['Action', 'Sci-Fi'],
            'rating' => [
                'aggregateRating' => 8.7,
                'voteCount' => 2237344,
            ],
            'metacritic' => [
                'score' => 73,
                'reviewCount' => 36,
            ],
            'plot' => 'A computer hacker learns the world is a simulation.',
            'stars' => [
                [
                    'id' => 'nm0000206',
                    'displayName' => 'Keanu Reeves',
                    'primaryProfessions' => ['actor'],
                ],
            ],
            'originCountries' => [
                ['code' => 'US', 'name' => 'United States'],
            ],
            'spokenLanguages' => [
                ['code' => 'eng', 'name' => 'English'],
            ],
            'interests' => [
                ['id' => 'in0000001', 'name' => 'Action'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditsPayload(): array
    {
        return [
            'credits' => [
                [
                    'name' => [
                        'id' => 'nm0000206',
                        'displayName' => 'Keanu Reeves',
                        'primaryProfessions' => ['actor'],
                    ],
                    'category' => 'actor',
                    'characters' => ['Neo'],
                ],
            ],
            'totalCount' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseDatesPayload(): array
    {
        return [
            'releaseDates' => [
                [
                    'country' => ['code' => 'US', 'name' => 'United States'],
                    'releaseDate' => ['year' => 1999, 'month' => 3, 'day' => 31],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function akasPayload(): array
    {
        return [
            'akas' => [
                [
                    'text' => 'Матриця',
                    'country' => ['code' => 'UA', 'name' => 'Ukraine'],
                    'language' => ['code' => 'uk', 'name' => 'Ukrainian'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function imagesPayload(): array
    {
        return [
            'images' => [
                ['url' => 'https://example.com/matrix-still.jpg', 'width' => 1920, 'height' => 1080, 'type' => 'still_frame'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function videosPayload(): array
    {
        return [
            'videos' => [
                [
                    'id' => 'vi1032782617',
                    'type' => 'trailer',
                    'name' => 'Theatrical Trailer',
                    'description' => 'Official trailer.',
                    'width' => 640,
                    'height' => 480,
                    'runtimeSeconds' => 146,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function awardPayload(): array
    {
        return [
            'stats' => ['nominationCount' => 1, 'winCount' => 1],
            'awardNominations' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function certificatesPayload(): array
    {
        return [
            'certificates' => [
                ['rating' => 'R', 'country' => ['code' => 'US', 'name' => 'United States']],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyCreditsPayload(): array
    {
        return [
            'companyCredits' => [
                [
                    'company' => ['id' => 'co0002663', 'name' => 'Warner Bros.'],
                    'category' => 'distribution',
                    'countries' => [['code' => 'US', 'name' => 'United States']],
                    'attributes' => ['theatrical'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function boxOfficePayload(): array
    {
        return [
            'worldwideGross' => [
                'amount' => '473328167',
                'currency' => 'USD',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function interestPayload(): array
    {
        return [
            'id' => 'in0000001',
            'name' => 'Action',
            'description' => 'Action Epic',
            'isSubgenre' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nameDetailsPayload(): array
    {
        return [
            'id' => 'nm0000206',
            'displayName' => 'Keanu Reeves',
            'primaryImage' => [
                'url' => 'https://example.com/keanu.jpg',
                'width' => 1800,
                'height' => 2700,
            ],
            'primaryProfessions' => ['actor'],
            'biography' => 'Keanu Reeves is an actor.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nameImagesPayload(): array
    {
        return [
            'images' => [
                ['url' => 'https://example.com/keanu-gallery.jpg', 'width' => 900, 'height' => 1400, 'type' => 'poster'],
            ],
        ];
    }
}
