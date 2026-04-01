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
        Schema::create('imdb_title_imports', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id')->unique();
            $table->text('source_url')->nullable();
            $table->text('storage_path')->nullable();
            $table->string('payload_hash', 64)->nullable()->index();
            $table->json('payload')->comment('Full raw IMDb bundle payload for archival and re-compaction purposes.');
            $table->timestamp('downloaded_at')->nullable()->index();
            $table->timestamp('imported_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imdb_title_imports');
    }
};
