<?php

use App\Enums\ProfileVisibility;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_visibility')
                ->default(ProfileVisibility::Public->value)
                ->after('status');
            $table->boolean('show_ratings_on_profile')
                ->default(true)
                ->after('profile_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_visibility',
                'show_ratings_on_profile',
            ]);
        });
    }
};
