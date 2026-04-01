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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('biography')->nullable();
            $table->string('known_for_department')->nullable()->index();
            $table->date('birth_date')->nullable()->index();
            $table->date('death_date')->nullable()->index();
            $table->string('birth_place')->nullable();
            $table->unsignedInteger('popularity_rank')->nullable()->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();

            $table->index(['is_published', 'popularity_rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
