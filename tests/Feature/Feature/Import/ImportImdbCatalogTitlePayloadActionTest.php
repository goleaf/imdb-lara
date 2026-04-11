<?php

namespace Tests\Feature\Feature\Import;

use App\Actions\Import\ImportImdbCatalogTitlePayloadAction;
use App\Console\Commands\ImdbImportTitlesFrontierCommand;
use App\Models\CertificateAttribute;
use App\Models\CertificateRating;
use App\Models\Country;
use App\Models\Movie;
use App\Models\MovieCertificate;
use App\Models\MovieCertificateAttribute;
use App\Models\MovieParentsGuideSection;
use App\Models\MovieParentsGuideSeverityBreakdown;
use App\Models\MovieReleaseDate;
use App\Models\MovieReleaseDateAttribute;
use App\Models\NameCredit;
use App\Models\ReleaseDateAttribute;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ImportImdbCatalogTitlePayloadActionTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
        Schema::connection('imdb_mysql')->enableForeignKeyConstraints();
        $this->setUpParentsGuideTables();
        $this->setUpReleaseDateTables();
        $this->setUpCertificateTables();
    }

    public function test_sync_parents_guide_defaults_missing_severity_vote_counts_to_zero(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt0423294',
            'imdb_id' => 'tt0423294',
            'titletype' => 'movie',
            'primarytitle' => 'Rogue Parents Guide',
            'originaltitle' => 'Rogue Parents Guide',
            'isadult' => 0,
            'startyear' => 2006,
        ]);

        $parentsGuidePayload = [
            'parentsGuide' => [
                [
                    'category' => 'violence',
                    'severityBreakdowns' => [
                        [
                            'severityLevel' => 'mild',
                            'voteCount' => 12,
                        ],
                        [
                            'severityLevel' => 'moderate',
                        ],
                    ],
                    'reviews' => [
                        [
                            'text' => 'Punches and falls.',
                        ],
                    ],
                ],
            ],
        ];

        $action = app(ImportImdbCatalogTitlePayloadAction::class);

        $syncParentsGuide = \Closure::bind(
            function (Movie $movie, array $parentsGuidePayload): void {
                $this->syncParentsGuide($movie, $parentsGuidePayload);
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        $syncParentsGuide($movie, $parentsGuidePayload);

        $section = MovieParentsGuideSection::query()
            ->where('movie_id', $movie->getKey())
            ->firstOrFail();

        $breakdowns = MovieParentsGuideSeverityBreakdown::query()
            ->where('movie_parents_guide_section_id', $section->getKey())
            ->orderBy('position')
            ->get();

        $this->assertCount(2, $breakdowns);
        $this->assertSame([12, 0], $breakdowns->pluck('vote_count')->all());
    }

    public function test_locked_import_uses_a_title_scoped_cache_lock_key(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt7777776',
            'imdb_id' => 'tt7777776',
            'titletype' => 'movie',
            'primarytitle' => 'Locked Import',
            'originaltitle' => 'Locked Import',
            'isadult' => 0,
            'startyear' => 2026,
        ]);

        Cache::shouldReceive('lock')
            ->once()
            ->with('imdb-catalog-import:title:tt7777776', 300)
            ->andReturn(new class
            {
                public function block(int $seconds, callable $callback): mixed
                {
                    return $callback();
                }
            });

        $action = app(ImportImdbCatalogTitlePayloadAction::class);

        $runLockedImport = \Closure::bind(
            function (string $resourceType, string $imdbId, callable $callback): Movie {
                /** @var Movie $movie */
                $movie = $this->runLockedImport($resourceType, $imdbId, $callback);

                return $movie;
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        $result = $runLockedImport('title', 'tt7777776', fn (): Movie => $movie);

        $this->assertSame($movie->getKey(), $result->getKey());
    }

    public function test_import_transaction_retries_lock_wait_timeouts_and_completes_the_import(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt7777777',
            'imdb_id' => 'tt7777777',
            'titletype' => 'movie',
            'primarytitle' => 'Retry Import',
            'originaltitle' => 'Retry Import',
            'isadult' => 0,
            'startyear' => 2026,
        ]);

        $action = app(ImportImdbCatalogTitlePayloadAction::class);
        $attempts = 0;

        $runImportTransaction = \Closure::bind(
            function (callable $callback): Movie {
                return $this->runImportTransaction($callback);
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        $result = $runImportTransaction(function () use (&$attempts, $movie): Movie {
            $attempts++;

            if ($attempts === 1) {
                throw $this->makeQueryException(1205, 'Lock wait timeout exceeded; try restarting transaction');
            }

            return $movie;
        });

        $this->assertSame(2, $attempts);
        $this->assertSame($movie->getKey(), $result->getKey());
    }

    public function test_sync_release_dates_replaces_existing_rows_without_leaving_stale_attribute_links(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt3890160',
            'imdb_id' => 'tt3890160',
            'titletype' => 'movie',
            'primarytitle' => 'Rollback Release Dates',
            'originaltitle' => 'Rollback Release Dates',
            'isadult' => 0,
            'startyear' => 2016,
        ]);

        $country = Country::query()->create([
            'code' => 'US',
            'name' => 'United States',
        ]);

        $oldAttribute = ReleaseDateAttribute::query()->create([
            'name' => 'Festival premiere',
        ]);

        $existingReleaseDate = MovieReleaseDate::query()->create([
            'movie_id' => $movie->getKey(),
            'country_code' => $country->getKey(),
            'release_year' => 2016,
            'release_month' => 9,
            'release_day' => 12,
            'position' => 1,
        ]);

        MovieReleaseDateAttribute::query()->create([
            'movie_release_date_id' => $existingReleaseDate->getKey(),
            'release_date_attribute_id' => $oldAttribute->getKey(),
            'position' => 1,
        ]);

        $action = app(ImportImdbCatalogTitlePayloadAction::class);

        $syncReleaseDates = \Closure::bind(
            function (Movie $movie, array $releaseDatesPayload): void {
                $this->syncReleaseDates($movie, $releaseDatesPayload);
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        $syncReleaseDates($movie, [
            'releaseDates' => [
                [
                    'country' => [
                        'code' => 'US',
                        'name' => 'United States',
                    ],
                    'releaseDate' => [
                        'year' => 2018,
                        'month' => 12,
                        'day' => 14,
                    ],
                    'attributes' => [
                        'Wide release',
                    ],
                ],
            ],
        ]);

        $releaseDates = MovieReleaseDate::query()
            ->where('movie_id', $movie->getKey())
            ->get();

        $this->assertCount(1, $releaseDates);
        $this->assertSame(2018, $releaseDates->first()->release_year);

        $attributes = MovieReleaseDateAttribute::query()->get();

        $this->assertCount(1, $attributes);
        $this->assertSame(
            $releaseDates->first()->getKey(),
            $attributes->first()->movie_release_date_id,
        );
        $this->assertDatabaseMissing('movie_release_date_attributes', [
            'movie_release_date_id' => $existingReleaseDate->getKey(),
            'release_date_attribute_id' => $oldAttribute->getKey(),
        ], 'imdb_mysql');
    }

    public function test_sync_genres_resolves_genre_existence_checks_in_batch_queries(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt0319729',
            'imdb_id' => 'tt0319729',
            'titletype' => 'movie',
            'primarytitle' => 'Batch Genres',
            'originaltitle' => 'Batch Genres',
            'isadult' => 0,
            'startyear' => 2026,
        ]);

        $action = app(ImportImdbCatalogTitlePayloadAction::class);

        $syncGenres = \Closure::bind(
            function (Movie $movie, array $genreNames): void {
                $this->syncGenres($movie, $genreNames);
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        DB::connection('imdb_mysql')->flushQueryLog();
        DB::connection('imdb_mysql')->enableQueryLog();

        $syncGenres($movie, ['Action', 'Thriller', 'Mystery']);

        $genreQueries = collect(DB::connection('imdb_mysql')->getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'into "genres"') || str_contains($query, 'from "genres"'));

        $this->assertLessThanOrEqual(3, $genreQueries->count());
        $this->assertCount(3, Movie::query()->findOrFail($movie->getKey())->movieGenres()->get());
    }

    public function test_sync_certificates_skips_entries_without_a_rating(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt33244668',
            'imdb_id' => 'tt33244668',
            'titletype' => 'movie',
            'primarytitle' => 'Missing Certificate Rating',
            'originaltitle' => 'Missing Certificate Rating',
            'isadult' => 0,
            'startyear' => 2026,
        ]);

        $action = app(ImportImdbCatalogTitlePayloadAction::class);

        $syncCertificates = \Closure::bind(
            function (Movie $movie, array $certificatesPayload): void {
                $this->syncCertificates($movie, $certificatesPayload);
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        $syncCertificates($movie, [
            'certificates' => [
                [
                    'country' => [
                        'code' => 'US',
                        'name' => 'United States',
                    ],
                    'attributes' => ['language'],
                ],
                [
                    'rating' => 'PG-13',
                    'country' => [
                        'code' => 'IL',
                        'name' => 'Israel',
                    ],
                    'attributes' => ['violence'],
                ],
            ],
        ]);

        $certificates = MovieCertificate::query()
            ->where('movie_id', $movie->getKey())
            ->orderBy('position')
            ->get();

        $this->assertCount(1, $certificates);
        $this->assertSame('IL', $certificates->first()->country_code);
        $this->assertSame('PG-13', CertificateRating::query()->findOrFail($certificates->first()->certificate_rating_id)->name);
        $this->assertCount(1, MovieCertificateAttribute::query()->where('movie_certificate_id', $certificates->first()->getKey())->get());
        $this->assertSame(['violence'], CertificateAttribute::query()->orderBy('name')->pluck('name')->all());
    }

    public function test_sync_credits_merges_duplicate_unique_name_credit_rows_before_writing(): void
    {
        $movie = Movie::query()->create([
            'tconst' => 'tt31938062',
            'imdb_id' => 'tt31938062',
            'titletype' => 'movie',
            'primarytitle' => 'Duplicate Credits',
            'originaltitle' => 'Duplicate Credits',
            'isadult' => 0,
            'startyear' => 2026,
        ]);

        $action = app(ImportImdbCatalogTitlePayloadAction::class);

        $syncCredits = \Closure::bind(
            function (Movie $movie, array $credits, array $directors, array $writers, array $stars): void {
                $this->syncCredits($movie, $credits, $directors, $writers, $stars);
            },
            $action,
            ImportImdbCatalogTitlePayloadAction::class,
        );

        $syncCredits($movie, [
            [
                'name' => [
                    'id' => 'nm3142672',
                    'displayName' => 'Duplicate Actor',
                ],
                'category' => 'actor',
                'episodeCount' => 1,
                'characters' => ['First Alias'],
            ],
            [
                'name' => [
                    'id' => 'nm3142672',
                    'displayName' => 'Duplicate Actor',
                ],
                'category' => 'actor',
                'episodeCount' => 2,
                'characters' => ['Second Alias', 'First Alias'],
            ],
        ], [], [], []);

        $credits = NameCredit::query()->where('movie_id', $movie->getKey())->get();

        $this->assertCount(1, $credits);
        $this->assertSame(2, $credits->first()->episode_count);
        $this->assertSame(1, $credits->first()->position);
        $this->assertSame(
            ['First Alias', 'Second Alias'],
            $credits->first()->nameCreditCharacters()->orderBy('position')->pluck('character_name')->all(),
        );
    }

    public function test_frontier_command_is_isolatable(): void
    {
        $this->assertInstanceOf(Isolatable::class, $this->app->make(ImdbImportTitlesFrontierCommand::class));
    }

    private function setUpParentsGuideTables(): void
    {
        Schema::connection('imdb_mysql')->create('parents_guide_categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code')->unique();
        });

        Schema::connection('imdb_mysql')->create('movie_parents_guide_sections', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('parents_guide_category_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('parents_guide_severity_levels', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('movie_parents_guide_severity_breakdowns', function (Blueprint $table): void {
            $table->unsignedInteger('movie_parents_guide_section_id');
            $table->unsignedInteger('parents_guide_severity_level_id');
            $table->unsignedInteger('vote_count');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_parents_guide_reviews', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('movie_parents_guide_section_id');
            $table->text('text');
            $table->boolean('is_spoiler')->default(false);
            $table->unsignedSmallInteger('position')->nullable();
        });
    }

    private function setUpReleaseDateTables(): void
    {
        Schema::connection('imdb_mysql')->create('countries', function (Blueprint $table): void {
            $table->string('code', 8)->primary();
            $table->string('name')->nullable();
        });

        Schema::connection('imdb_mysql')->create('release_date_attributes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('movie_release_dates', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('movie_id');
            $table->string('country_code', 8);
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->unsignedTinyInteger('release_month')->nullable();
            $table->unsignedTinyInteger('release_day')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->foreign('movie_id')->references('id')->on('movies')->cascadeOnDelete();
            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::connection('imdb_mysql')->create('movie_release_date_attributes', function (Blueprint $table): void {
            $table->unsignedInteger('movie_release_date_id');
            $table->unsignedInteger('release_date_attribute_id');
            $table->unsignedSmallInteger('position');
            $table->primary(['movie_release_date_id', 'release_date_attribute_id']);
            $table->foreign('movie_release_date_id')->references('id')->on('movie_release_dates')->cascadeOnDelete();
            $table->foreign('release_date_attribute_id')->references('id')->on('release_date_attributes')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    private function setUpCertificateTables(): void
    {
        Schema::connection('imdb_mysql')->create('certificate_ratings', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('certificate_attributes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('movie_certificates', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('certificate_rating_id');
            $table->string('country_code', 8)->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->foreign('movie_id')->references('id')->on('movies')->cascadeOnDelete();
            $table->foreign('certificate_rating_id')->references('id')->on('certificate_ratings')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::connection('imdb_mysql')->create('movie_certificate_attributes', function (Blueprint $table): void {
            $table->unsignedInteger('movie_certificate_id');
            $table->unsignedInteger('certificate_attribute_id');
            $table->unsignedSmallInteger('position');
            $table->primary(['movie_certificate_id', 'certificate_attribute_id']);
            $table->foreign('movie_certificate_id')->references('id')->on('movie_certificates')->cascadeOnDelete();
            $table->foreign('certificate_attribute_id')->references('id')->on('certificate_attributes')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    private function makeQueryException(int $driverErrorCode, string $message): QueryException
    {
        $previous = new PDOException($message);
        $previous->errorInfo = ['HY000', $driverErrorCode, $message];

        return new QueryException('imdb_mysql', 'select 1', [], $previous);
    }
}
