<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->string('imdb_id')->nullable()->after('id');
            $table->unique('imdb_id', 'titles_imdb_id_unique');
            $table->string('imdb_type')->nullable()->after('title_type');
            $table->index('imdb_type', 'titles_imdb_type_index');
            $table->unsignedInteger('runtime_seconds')->nullable()->after('runtime_minutes');
            $table->json('imdb_genres')->nullable()->after('search_keywords');
            $table->json('imdb_interests')->nullable()->after('imdb_genres');
            $table->json('imdb_origin_countries')->nullable()->after('imdb_interests');
            $table->json('imdb_spoken_languages')->nullable()->after('imdb_origin_countries');
            $table->json('imdb_payload')
                ->nullable()
                ->after('imdb_spoken_languages')
                ->comment('Compact supplemental IMDb data only; full raw bundle is stored in imdb_title_imports.payload.');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('imdb_id')->nullable()->after('id');
            $table->unique('imdb_id', 'people_imdb_id_unique');
            $table->json('imdb_alternative_names')->nullable()->after('alternate_names');
            $table->json('imdb_primary_professions')->nullable()->after('imdb_alternative_names');
            $table->json('imdb_payload')
                ->nullable()
                ->after('imdb_primary_professions')
                ->comment('Compact supplemental IMDb person data only; canonical raw title bundles remain in imdb_title_imports.payload.');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->string('imdb_source_group')->nullable()->after('credited_as');
            $table->index('imdb_source_group', 'credits_imdb_source_group_index');
        });

        Schema::table('title_statistics', function (Blueprint $table) {
            $table->unsignedSmallInteger('metacritic_score')->nullable()->after('average_rating');
            $table->unsignedInteger('metacritic_review_count')->nullable()->after('metacritic_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('title_statistics', function (Blueprint $table) {
            $table->dropColumn([
                'metacritic_score',
                'metacritic_review_count',
            ]);
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex('credits_imdb_source_group_index');
            $table->dropColumn('imdb_source_group');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique('people_imdb_id_unique');
            $table->dropColumn([
                'imdb_id',
                'imdb_alternative_names',
                'imdb_primary_professions',
                'imdb_payload',
            ]);
        });

        Schema::table('titles', function (Blueprint $table) {
            $table->dropUnique('titles_imdb_id_unique');
            $table->dropIndex('titles_imdb_type_index');
            $table->dropColumn([
                'imdb_id',
                'imdb_type',
                'runtime_seconds',
                'imdb_genres',
                'imdb_interests',
                'imdb_origin_countries',
                'imdb_spoken_languages',
                'imdb_payload',
            ]);
        });
    }
};
