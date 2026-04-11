<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Schema::connection('imdb_mysql')->withoutForeignKeyConstraints(function (): void {
            Schema::connection('imdb_mysql')->dropIfExists('title_aka_attributes');
            Schema::connection('imdb_mysql')->dropIfExists('title_aka_types');
            Schema::connection('imdb_mysql')->dropIfExists('title_crew_directors');
            Schema::connection('imdb_mysql')->dropIfExists('title_crew_writers');
            Schema::connection('imdb_mysql')->dropIfExists('title_principals');
            Schema::connection('imdb_mysql')->dropIfExists('title_ratings');
            Schema::connection('imdb_mysql')->dropIfExists('title_episode');
            Schema::connection('imdb_mysql')->dropIfExists('title_crew');
            Schema::connection('imdb_mysql')->dropIfExists('title_akas');
        });
    }

    public function down(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Schema::connection('imdb_mysql')->create('title_akas', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('titleid', 16)->index();
            $table->integer('ordering')->nullable();
            $table->longText('title')->nullable();
            $table->string('region', 8)->nullable();
            $table->string('language', 8)->nullable();
            $table->longText('types')->nullable();
            $table->longText('attributes')->nullable();
            $table->integer('isoriginaltitle')->nullable();
        });

        Schema::connection('imdb_mysql')->create('title_crew', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('tconst', 16)->index();
            $table->longText('directors')->nullable();
            $table->longText('writers')->nullable();
        });

        Schema::connection('imdb_mysql')->create('title_episode', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('tconst', 16)->index();
            $table->string('parenttconst', 16)->nullable()->index();
            $table->integer('seasonnumber')->nullable();
            $table->integer('episodenumber')->nullable();
        });

        Schema::connection('imdb_mysql')->create('title_principals', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('tconst', 16)->index();
            $table->integer('ordering')->nullable();
            $table->string('nconst', 16)->nullable()->index();
            $table->string('category', 32)->nullable();
            $table->string('job', 512)->nullable();
        });

        Schema::connection('imdb_mysql')->create('title_ratings', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('tconst', 16)->index();
            $table->double('averagerating')->nullable();
            $table->integer('numvotes')->nullable();
        });

        Schema::connection('imdb_mysql')->create('title_aka_attributes', function (Blueprint $table): void {
            $table->integer('title_aka_id');
            $table->integer('aka_attribute_id');
            $table->unsignedSmallInteger('position')->nullable();
            $table->primary(['title_aka_id', 'aka_attribute_id']);
        });

        Schema::connection('imdb_mysql')->create('title_aka_types', function (Blueprint $table): void {
            $table->integer('title_aka_id');
            $table->integer('aka_type_id');
            $table->unsignedSmallInteger('position')->nullable();
            $table->primary(['title_aka_id', 'aka_type_id']);
        });

        Schema::connection('imdb_mysql')->create('title_crew_directors', function (Blueprint $table): void {
            $table->integer('title_crew_id');
            $table->integer('name_basic_id');
            $table->unsignedSmallInteger('position')->nullable();
            $table->primary(['title_crew_id', 'name_basic_id']);
        });

        Schema::connection('imdb_mysql')->create('title_crew_writers', function (Blueprint $table): void {
            $table->integer('title_crew_id');
            $table->integer('name_basic_id');
            $table->unsignedSmallInteger('position')->nullable();
            $table->primary(['title_crew_id', 'name_basic_id']);
        });
    }
};
