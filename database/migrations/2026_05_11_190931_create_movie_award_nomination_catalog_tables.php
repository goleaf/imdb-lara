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
        if (! Schema::connection('imdb_mysql')->hasTable('movie_award_nominations')) {
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

                $table->index(['movie_id', 'is_winner', 'award_year', 'position'], 'idx_movie_award_nominations_movie_id_ranked');
                $table->index(['is_winner', 'award_year', 'position'], 'idx_movie_award_nominations_ranked');
                $table->index('award_category_id', 'idx_movie_award_nominations_award_category_id');
            });
        }

        if (! Schema::connection('imdb_mysql')->hasTable('movie_award_nomination_nominees')) {
            Schema::connection('imdb_mysql')->create('movie_award_nomination_nominees', function (Blueprint $table): void {
                $table->unsignedInteger('movie_award_nomination_id');
                $table->unsignedInteger('name_basic_id');
                $table->unsignedSmallInteger('position')->nullable();

                $table->primary(
                    ['movie_award_nomination_id', 'name_basic_id'],
                    'pk_movie_award_nomination_nominees'
                );
                $table->index('name_basic_id', 'idx_movie_award_nomination_nominees_name_basic_id');
                $table->index('position', 'idx_movie_award_nomination_nominees_position');
            });
        }

        if (! Schema::connection('imdb_mysql')->hasTable('movie_award_nomination_titles')) {
            Schema::connection('imdb_mysql')->create('movie_award_nomination_titles', function (Blueprint $table): void {
                $table->unsignedInteger('movie_award_nomination_id');
                $table->unsignedInteger('nominated_movie_id');
                $table->unsignedSmallInteger('position')->nullable();

                $table->primary(
                    ['movie_award_nomination_id', 'nominated_movie_id'],
                    'pk_movie_award_nomination_titles'
                );
                $table->index('nominated_movie_id', 'idx_movie_award_nomination_titles_nominated_movie_id');
                $table->index('position', 'idx_movie_award_nomination_titles_position');
            });
        }

        if (! Schema::connection('imdb_mysql')->hasTable('movie_award_nomination_summaries')) {
            Schema::connection('imdb_mysql')->create('movie_award_nomination_summaries', function (Blueprint $table): void {
                $table->unsignedInteger('movie_id')->primary();
                $table->unsignedInteger('nomination_count')->nullable();
                $table->unsignedInteger('win_count')->nullable();
                $table->string('next_page_token')->nullable();
                $table->index('nomination_count', 'idx_movie_award_nomination_summaries_nomination_count');
                $table->index('win_count', 'idx_movie_award_nomination_summaries_win_count');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('imdb_mysql')->hasTable('movie_award_nomination_summaries')) {
            Schema::connection('imdb_mysql')->drop('movie_award_nomination_summaries');
        }

        if (Schema::connection('imdb_mysql')->hasTable('movie_award_nomination_titles')) {
            Schema::connection('imdb_mysql')->drop('movie_award_nomination_titles');
        }

        if (Schema::connection('imdb_mysql')->hasTable('movie_award_nomination_nominees')) {
            Schema::connection('imdb_mysql')->drop('movie_award_nomination_nominees');
        }

        Schema::connection('imdb_mysql')->dropIfExists('movie_award_nominations');
    }
};
