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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable');
            $table->string('kind')->index();
            $table->text('url');
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id', 'kind'], 'media_assets_mediable_kind_index');
            $table->index(['mediable_type', 'mediable_id', 'position'], 'media_assets_mediable_position_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
