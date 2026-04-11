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
        Schema::create('groups', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign key
            $table->uuid('creator_id');

            // Group information
            $table->string('name');
            $table->enum('purpose', ['prayer', 'study']);
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();

            // Index
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
