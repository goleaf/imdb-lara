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
        Schema::table('title_statistics', function (Blueprint $table) {
            $table->json('rating_distribution')->nullable()->after('average_rating');
            $table->index(['average_rating', 'rating_count'], 'title_statistics_top_rated_index');
            $table->index(['watchlist_count', 'review_count', 'rating_count'], 'title_statistics_trending_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('title_statistics', function (Blueprint $table) {
            $table->dropIndex('title_statistics_top_rated_index');
            $table->dropIndex('title_statistics_trending_index');
            $table->dropColumn('rating_distribution');
        });
    }
};
