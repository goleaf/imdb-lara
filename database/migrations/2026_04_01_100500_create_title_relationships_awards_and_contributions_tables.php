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
        Schema::create('title_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_title_id')->constrained('titles')->cascadeOnDelete();
            $table->foreignId('to_title_id')->constrained('titles')->cascadeOnDelete();
            $table->string('relationship_type')->index();
            $table->unsignedSmallInteger('weight')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['from_title_id', 'to_title_id', 'relationship_type'], 'title_relationships_unique_link');
            $table->index(['to_title_id', 'relationship_type'], 'title_relationships_reverse_lookup_index');
        });

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('award_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('year')->index();
            $table->string('edition')->nullable();
            $table->date('event_date')->nullable()->index();
            $table->string('location')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->unique(['award_id', 'year', 'name']);
        });

        Schema::create('award_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('recipient_scope')->default('title')->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['award_id', 'slug']);
        });

        Schema::create('award_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->nullOnDelete();
            $table->string('credited_name')->nullable();
            $table->text('details')->nullable();
            $table->boolean('is_winner')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['title_id', 'is_winner'], 'award_nominations_title_lookup_index');
            $table->index(['person_id', 'is_winner'], 'award_nominations_person_lookup_index');
            $table->index(['company_id', 'is_winner'], 'award_nominations_company_lookup_index');
        });

        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('contributable');
            $table->string('action')->index();
            $table->string('status')->default('submitted')->index();
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contributable_type', 'contributable_id', 'status'], 'contributions_contributable_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
        Schema::dropIfExists('award_nominations');
        Schema::dropIfExists('award_categories');
        Schema::dropIfExists('award_events');
        Schema::dropIfExists('awards');
        Schema::dropIfExists('title_relationships');
    }
};
