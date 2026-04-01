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
            $table->string('sort_title')->nullable()->index();
            $table->foreignId('canonical_title_id')->nullable()->constrained('titles')->nullOnDelete();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('search_keywords')->nullable();
            $table->softDeletes();

            $table->index(['name', 'title_type'], 'titles_name_type_index');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('search_keywords')->nullable();
            $table->softDeletes();

            $table->index(['name', 'known_for_department'], 'people_name_department_index');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->softDeletes();

            $table->index(['name', 'kind'], 'companies_name_kind_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
        });

        Schema::table('user_lists', function (Blueprint $table) {
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->softDeletes();
        });

        Schema::table('list_items', function (Blueprint $table) {
            $table->string('watch_state')->default('planned')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('watched_at')->nullable()->index();
            $table->unsignedSmallInteger('rewatch_count')->default(0);

            $table->index(['watch_state', 'watched_at'], 'list_items_watch_state_watched_at_index');
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->string('provider')->nullable()->index();
            $table->string('provider_key')->nullable()->index();
            $table->string('language', 12)->nullable()->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->softDeletes();

            $table->index(['kind', 'provider'], 'media_assets_kind_provider_index');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->text('resolution_notes')->nullable();
            $table->softDeletes();
        });

        Schema::table('company_title', function (Blueprint $table) {
            $table->string('credited_as')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->nullable();

            $table->index(['title_id', 'sort_order'], 'company_title_sort_order_index');
        });

        Schema::table('title_statistics', function (Blueprint $table) {
            $table->unsignedInteger('episodes_count')->default(0);
            $table->unsignedInteger('awards_nominated_count')->default(0);
            $table->unsignedInteger('awards_won_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('title_statistics', function (Blueprint $table) {
            $table->dropColumn([
                'episodes_count',
                'awards_nominated_count',
                'awards_won_count',
            ]);
        });

        Schema::table('company_title', function (Blueprint $table) {
            $table->dropIndex('company_title_sort_order_index');
            $table->dropColumn([
                'credited_as',
                'is_primary',
                'sort_order',
            ]);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'resolution_notes',
                'deleted_at',
            ]);
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex('media_assets_kind_provider_index');
            $table->dropColumn([
                'provider',
                'provider_key',
                'language',
                'duration_seconds',
                'metadata',
                'published_at',
                'deleted_at',
            ]);
        });

        Schema::table('list_items', function (Blueprint $table) {
            $table->dropIndex('list_items_watch_state_watched_at_index');
            $table->dropColumn([
                'watch_state',
                'started_at',
                'watched_at',
                'rewatch_count',
            ]);
        });

        Schema::table('user_lists', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'deleted_at',
            ]);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'edited_at',
                'deleted_at',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_name_kind_index');
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'deleted_at',
            ]);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('people_name_department_index');
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'search_keywords',
                'deleted_at',
            ]);
        });

        Schema::table('titles', function (Blueprint $table) {
            $table->dropIndex('titles_name_type_index');
            $table->dropConstrainedForeignId('canonical_title_id');
            $table->dropColumn([
                'sort_title',
                'meta_title',
                'meta_description',
                'search_keywords',
                'deleted_at',
            ]);
        });
    }
};
