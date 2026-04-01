<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('list_items', function (Blueprint $table) {
            $table->index(
                ['user_list_id', 'created_at', 'id'],
                'list_items_user_list_id_created_at_id_index',
            );
            $table->index(
                ['user_list_id', 'watch_state', 'created_at'],
                'list_items_user_list_id_watch_state_created_at_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('list_items', function (Blueprint $table) {
            $table->dropIndex('list_items_user_list_id_created_at_id_index');
            $table->dropIndex('list_items_user_list_id_watch_state_created_at_index');
        });
    }
};
