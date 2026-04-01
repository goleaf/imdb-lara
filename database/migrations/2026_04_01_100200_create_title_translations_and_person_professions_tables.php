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
        Schema::create('title_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12)->index();
            $table->string('localized_title');
            $table->string('localized_slug')->nullable();
            $table->text('localized_plot_outline')->nullable();
            $table->text('localized_synopsis')->nullable();
            $table->string('localized_tagline')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['title_id', 'locale']);
            $table->unique(['locale', 'localized_slug']);
            $table->index(['locale', 'localized_title'], 'title_translations_locale_title_index');
        });

        Schema::create('person_professions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('department')->index();
            $table->string('profession')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['person_id', 'profession']);
            $table->index(['person_id', 'is_primary'], 'person_professions_primary_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_professions');
        Schema::dropIfExists('title_translations');
    }
};
