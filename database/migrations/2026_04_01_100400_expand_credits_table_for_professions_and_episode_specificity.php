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
        Schema::table('credits', function (Blueprint $table) {
            $table->foreignId('person_profession_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->nullOnDelete();
            $table->string('credited_as')->nullable();
            $table->softDeletes();

            $table->index(['title_id', 'person_profession_id'], 'credits_title_profession_index');
            $table->index(['episode_id', 'billing_order'], 'credits_episode_billing_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex('credits_title_profession_index');
            $table->dropIndex('credits_episode_billing_index');
            $table->dropConstrainedForeignId('person_profession_id');
            $table->dropConstrainedForeignId('episode_id');
            $table->dropColumn([
                'credited_as',
                'deleted_at',
            ]);
        });
    }
};
