<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

trait BootstrapsImdbMysqlSqlite
{
    private string $imdbMysqlSqlitePath;

    protected function setUpImdbMysqlSqliteDatabase(): void
    {
        $this->imdbMysqlSqlitePath = storage_path(sprintf(
            'framework/testing/imdb-mysql-catalog-%s.sqlite',
            md5(static::class.'-'.microtime(true)),
        ));

        File::delete($this->imdbMysqlSqlitePath);
        File::ensureDirectoryExists(dirname($this->imdbMysqlSqlitePath));
        File::put($this->imdbMysqlSqlitePath, '');

        Config::set('database.connections.imdb_mysql', [
            'driver' => 'sqlite',
            'database' => $this->imdbMysqlSqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('imdb_mysql');

        Schema::connection('imdb_mysql')->create('movies', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('tconst')->unique();
            $table->string('titletype')->nullable();
            $table->string('primarytitle')->nullable();
            $table->string('originaltitle')->nullable();
            $table->integer('isadult')->default(0);
            $table->integer('startyear')->nullable();
            $table->integer('endyear')->nullable();
            $table->integer('runtimeminutes')->nullable();
            $table->text('genres')->nullable();
            $table->integer('title_type_id')->nullable();
            $table->string('imdb_id')->nullable();
            $table->integer('runtimeSeconds')->nullable();
        });

        Schema::connection('imdb_mysql')->create('genres', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('aka_attributes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 128)->unique();
        });

        Schema::connection('imdb_mysql')->create('aka_types', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 128)->unique();
        });

        Schema::connection('imdb_mysql')->create('award_categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('award_events', function (Blueprint $table): void {
            $table->string('imdb_id')->primary();
            $table->string('name')->nullable();
        });

        Schema::connection('imdb_mysql')->create('title_types', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('movie_genres', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('genre_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_aka_attributes', function (Blueprint $table): void {
            $table->unsignedInteger('movie_aka_id');
            $table->unsignedInteger('aka_attribute_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_aka_types', function (Blueprint $table): void {
            $table->unsignedInteger('movie_aka_id');
            $table->unsignedInteger('aka_type_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_ratings', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id')->primary();
            $table->decimal('aggregate_rating', 4, 2)->nullable();
            $table->unsignedInteger('vote_count')->nullable();
            $table->text('rating_distribution')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_primary_images', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id')->primary();
            $table->text('url')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('type')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_images', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('position')->nullable();
            $table->text('url');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('type')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_plots', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id')->primary();
            $table->text('plot')->nullable();
        });

        Schema::connection('imdb_mysql')->create('name_basics', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nconst')->unique();
            $table->string('primaryname')->nullable();
            $table->integer('birthyear')->nullable();
            $table->integer('deathyear')->nullable();
            $table->text('primaryprofession')->nullable();
            $table->text('knownfortitles')->nullable();
            $table->text('alternativeNames')->nullable();
            $table->text('biography')->nullable();
            $table->unsignedTinyInteger('birthDate_day')->nullable();
            $table->unsignedTinyInteger('birthDate_month')->nullable();
            $table->smallInteger('birthDate_year')->nullable();
            $table->string('birthLocation')->nullable();
            $table->string('birthName')->nullable();
            $table->unsignedTinyInteger('deathDate_day')->nullable();
            $table->unsignedTinyInteger('deathDate_month')->nullable();
            $table->smallInteger('deathDate_year')->nullable();
            $table->string('deathLocation')->nullable();
            $table->string('deathReason')->nullable();
            $table->string('displayName')->nullable();
            $table->unsignedSmallInteger('heightCm')->nullable();
            $table->string('imdb_id')->nullable();
            $table->unsignedInteger('primaryImage_height')->nullable();
            $table->text('primaryImage_url')->nullable();
            $table->unsignedInteger('primaryImage_width')->nullable();
            $table->text('primaryProfessions')->nullable();
        });

        Schema::connection('imdb_mysql')->create('professions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
        });

        Schema::connection('imdb_mysql')->create('name_basic_professions', function (Blueprint $table): void {
            $table->unsignedInteger('name_basic_id');
            $table->unsignedInteger('profession_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('person_professions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->string('department')->nullable();
            $table->string('profession')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::connection('imdb_mysql')->create('name_credits', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('name_basic_id');
            $table->unsignedInteger('movie_id');
            $table->string('category')->nullable();
            $table->unsignedInteger('episode_count')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->unique(
                ['name_basic_id', 'movie_id', 'category'],
                'ux_name_credits_name_basic_id_movie_id_category',
            );
        });

        Schema::connection('imdb_mysql')->create('name_credit_characters', function (Blueprint $table): void {
            $table->unsignedInteger('name_credit_id');
            $table->unsignedSmallInteger('position');
            $table->string('character_name');
        });

        Schema::connection('imdb_mysql')->create('name_credit_summaries', function (Blueprint $table): void {
            $table->unsignedInteger('name_basic_id')->primary();
            $table->unsignedInteger('total_count')->nullable();
            $table->string('next_page_token')->nullable();
        });

        Schema::connection('imdb_mysql')->create('name_basic_known_for_titles', function (Blueprint $table): void {
            $table->unsignedInteger('name_basic_id');
            $table->unsignedInteger('title_basic_id');
            $table->unsignedSmallInteger('position')->nullable();
            $table->primary(
                ['name_basic_id', 'title_basic_id'],
                'pk_name_basic_known_for_titles',
            );
        });

        Schema::connection('imdb_mysql')->create('name_basic_meter_rankings', function (Blueprint $table): void {
            $table->unsignedInteger('name_basic_id')->primary();
            $table->unsignedInteger('current_rank')->nullable();
            $table->string('change_direction')->nullable();
            $table->integer('difference')->nullable();
        });

        Schema::connection('imdb_mysql')->create('name_images', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('name_basic_id');
            $table->unsignedInteger('position')->nullable();
            $table->text('url');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('type')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_directors', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('name_basic_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_writers', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('name_basic_id');
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_stars', function (Blueprint $table): void {
            $table->unsignedInteger('movie_id');
            $table->unsignedInteger('name_basic_id');
            $table->unsignedSmallInteger('ordering')->nullable();
            $table->string('category')->nullable();
            $table->string('job')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_award_nominations', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('movie_id')->nullable();
            $table->string('event_imdb_id')->nullable();
            $table->unsignedInteger('award_category_id')->nullable();
            $table->unsignedSmallInteger('award_year')->nullable();
            $table->text('text')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->unsignedSmallInteger('winner_rank')->nullable();
            $table->unsignedSmallInteger('position')->nullable();
        });

        Schema::connection('imdb_mysql')->create('movie_award_nomination_nominees', function (Blueprint $table): void {
            $table->unsignedInteger('movie_award_nomination_id');
            $table->unsignedInteger('name_basic_id');
            $table->unsignedSmallInteger('position')->nullable();
        });
    }
}
