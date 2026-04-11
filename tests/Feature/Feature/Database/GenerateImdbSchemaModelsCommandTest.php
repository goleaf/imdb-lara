<?php

namespace Tests\Feature\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class GenerateImdbSchemaModelsCommandTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('framework/testing/generated-imdb-models'));

        parent::tearDown();
    }

    public function test_it_generates_models_for_selected_tables_with_casts_and_relations(): void
    {
        Schema::create('title_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('movies', function (Blueprint $table): void {
            $table->id();
            $table->string('tconst')->unique();
            $table->string('primarytitle')->nullable();
            $table->boolean('isadult')->default(false);
            $table->unsignedInteger('runtimeSeconds')->nullable();
            $table->foreignId('title_type_id')->nullable()->constrained('title_types');
        });

        Schema::create('movie_ratings', function (Blueprint $table): void {
            $table->foreignId('movie_id')->primary()->constrained('movies');
            $table->decimal('aggregate_rating', 4, 2)->nullable();
            $table->unsignedInteger('vote_count')->nullable();
        });

        Schema::create('genres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('movie_genres', function (Blueprint $table): void {
            $table->foreignId('movie_id')->constrained('movies');
            $table->foreignId('genre_id')->constrained('genres');
            $table->unsignedInteger('position')->default(0);
            $table->primary(['movie_id', 'genre_id']);
        });

        Schema::create('movie_seasons', function (Blueprint $table): void {
            $table->foreignId('movie_id')->constrained('movies');
            $table->string('season');
            $table->unsignedInteger('episode_count')->default(0);
            $table->primary(['movie_id', 'season']);
        });

        Schema::create('movie_videos', function (Blueprint $table): void {
            $table->string('imdb_id')->primary();
            $table->foreignId('movie_id')->constrained('movies');
            $table->string('name')->nullable();
        });

        $outputPath = storage_path('framework/testing/generated-imdb-models');

        $this->artisan('imdb:generate-schema-models', [
            '--connection' => 'sqlite',
            '--namespace' => 'App\\Models\\Generated',
            '--output-path' => $outputPath,
            '--table' => ['title_types', 'movies', 'movie_ratings', 'genres', 'movie_genres', 'movie_seasons', 'movie_videos'],
            '--force' => true,
        ])->assertExitCode(0);

        $movieModel = File::get($outputPath.'/Movie.php');
        $genreModel = File::get($outputPath.'/Genre.php');
        $movieGenreModel = File::get($outputPath.'/MovieGenre.php');
        $movieRatingModel = File::get($outputPath.'/MovieRating.php');
        $movieSeasonModel = File::get($outputPath.'/MovieSeason.php');
        $movieVideoModel = File::get($outputPath.'/MovieVideo.php');

        $this->assertStringContainsString('namespace App\\Models\\Generated;', $movieModel);
        $this->assertStringContainsString('use App\\Models\\ImdbModel;', $movieModel);
        $this->assertStringContainsString("protected \$table = 'movies';", $movieModel);
        $this->assertStringContainsString("'isadult' => 'boolean',", $movieModel);
        $this->assertStringContainsString("'runtimeSeconds' => 'integer',", $movieModel);
        $this->assertStringContainsString('public function titleType(): BelongsTo', $movieModel);
        $this->assertStringContainsString('public function movieRating(): HasOne', $movieModel);
        $this->assertStringContainsString('public function genres(): BelongsToMany', $movieModel);
        $this->assertStringContainsString('public function movieSeasons(): HasMany', $movieModel);
        $this->assertStringContainsString('public function movieVideos(): HasMany', $movieModel);

        $this->assertStringContainsString('public function movies(): BelongsToMany', $genreModel);

        $this->assertStringContainsString('use App\\Models\\Concerns\\HasCompositePrimaryKey;', $movieGenreModel);
        $this->assertStringContainsString("protected array \$compositeKey = ['movie_id', 'genre_id'];", $movieGenreModel);
        $this->assertStringContainsString('public function genre(): BelongsTo', $movieGenreModel);
        $this->assertStringContainsString('public function movie(): BelongsTo', $movieGenreModel);

        $this->assertStringContainsString("protected \$primaryKey = 'movie_id';", $movieRatingModel);
        $this->assertStringContainsString('public function movie(): BelongsTo', $movieRatingModel);

        $this->assertStringContainsString('use App\\Models\\Concerns\\HasCompositePrimaryKey;', $movieSeasonModel);
        $this->assertStringContainsString("protected array \$compositeKey = ['movie_id', 'season'];", $movieSeasonModel);
        $this->assertStringContainsString('public function movie(): BelongsTo', $movieSeasonModel);

        $this->assertStringContainsString("protected \$primaryKey = 'imdb_id';", $movieVideoModel);
        $this->assertStringContainsString('public $incrementing = false;', $movieVideoModel);
        $this->assertStringContainsString("protected \$keyType = 'string';", $movieVideoModel);
        $this->assertStringContainsString('public function movie(): BelongsTo', $movieVideoModel);
    }
}
