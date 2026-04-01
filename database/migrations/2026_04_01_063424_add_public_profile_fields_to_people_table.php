<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->text('alternate_names')->nullable()->after('name');
            $table->text('short_biography')->nullable()->after('biography');
            $table->string('nationality')->nullable()->after('birth_place');
            $table->string('death_place')->nullable()->after('death_date');
            $table->index('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['nationality']);
            $table->dropColumn([
                'alternate_names',
                'short_biography',
                'nationality',
                'death_place',
            ]);
        });
    }
};
