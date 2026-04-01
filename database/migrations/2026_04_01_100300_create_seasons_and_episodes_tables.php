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
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('titles')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('season_number')->index();
            $table->text('summary')->nullable();
            $table->unsignedSmallInteger('release_year')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['series_id', 'season_number']);
            $table->index(['series_id', 'deleted_at'], 'seasons_series_deleted_at_index');
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('series_id')->constrained('titles')->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('season_number')->nullable()->index();
            $table->unsignedSmallInteger('episode_number')->nullable()->index();
            $table->unsignedInteger('absolute_number')->nullable()->index();
            $table->string('production_code')->nullable()->index();
            $table->date('aired_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['season_id', 'episode_number']);
            $table->index(['series_id', 'season_number', 'episode_number'], 'episodes_series_season_episode_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('seasons');
    }
};
