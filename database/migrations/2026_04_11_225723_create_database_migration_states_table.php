<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_migration_states', function (Blueprint $table) {
            $table->id();
            $table->string('source_driver', 32);
            $table->string('source_host', 191)->nullable();
            $table->string('source_database', 191);
            $table->string('table_name', 191);
            $table->json('cursor_columns');
            $table->json('last_cursor')->nullable();
            $table->unsignedBigInteger('rows_copied')->default(0);
            $table->string('status', 32)->default('pending')->index();
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_driver', 'source_host', 'source_database', 'table_name'],
                'database_migration_states_source_table_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_migration_states');
    }
};
