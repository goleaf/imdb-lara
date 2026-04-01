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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('department')->index();
            $table->string('job')->index();
            $table->string('character_name')->nullable();
            $table->unsignedSmallInteger('billing_order')->nullable();
            $table->boolean('is_principal')->default(false)->index();
            $table->timestamps();

            $table->index(['title_id', 'billing_order']);
            $table->index(['person_id', 'department']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
