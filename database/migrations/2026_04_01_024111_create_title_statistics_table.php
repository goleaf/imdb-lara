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
        Schema::create('title_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete()->unique();
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('average_rating', 4, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('watchlist_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_statistics');
    }
};
