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
        Schema::create('circle_suggestions', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('session_id');
            $table->uuid('from_user_id');
            $table->uuid('to_user_id');

            // Status
            $table->enum('status', ['pending', 'accepted', 'dismissed'])->default('pending');

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('prayer_sessions')->cascadeOnDelete();
            $table->foreign('from_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('to_user_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('session_id');
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('status');

            // Unique constraint to prevent duplicate suggestions
            $table->unique(['session_id', 'from_user_id', 'to_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_suggestions');
    }
};
