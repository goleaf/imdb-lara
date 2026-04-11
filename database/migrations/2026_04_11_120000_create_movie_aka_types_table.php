<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if (Schema::connection('imdb_mysql')->hasTable('movie_aka_types')) {
            return;
        }

        Schema::connection('imdb_mysql')->create('movie_aka_types', function (Blueprint $table): void {
            $table->unsignedInteger('movie_aka_id');
            $table->unsignedInteger('aka_type_id');
            $table->unsignedSmallInteger('position');

            $table->primary(['movie_aka_id', 'aka_type_id']);
            $table->index(['aka_type_id', 'movie_aka_id'], 'idx_movie_aka_types_type_id_movie_aka_id');
            $table->index(['movie_aka_id', 'position'], 'idx_movie_aka_types_movie_aka_id_position');

            $table->foreign('movie_aka_id', 'fk_movie_aka_types_movie_aka_id')
                ->references('id')
                ->on('movie_akas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('aka_type_id', 'fk_movie_aka_types_aka_type_id')
                ->references('id')
                ->on('aka_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Schema::connection('imdb_mysql')->dropIfExists('movie_aka_types');
    }
};
