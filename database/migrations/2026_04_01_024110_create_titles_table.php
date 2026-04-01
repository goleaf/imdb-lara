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
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_name')->nullable();
            $table->string('slug')->unique();
            $table->string('title_type')->index();
            $table->unsignedSmallInteger('release_year')->nullable()->index();
            $table->unsignedSmallInteger('end_year')->nullable()->index();
            $table->date('release_date')->nullable()->index();
            $table->unsignedSmallInteger('runtime_minutes')->nullable()->index();
            $table->string('age_rating', 12)->nullable()->index();
            $table->text('plot_outline')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('tagline')->nullable();
            $table->string('origin_country', 2)->nullable()->index();
            $table->string('original_language', 12)->nullable()->index();
            $table->unsignedInteger('popularity_rank')->nullable()->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();

            $table->index(['title_type', 'release_year']);
            $table->index(['is_published', 'popularity_rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titles');
    }
};
