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
        Schema::create('streak_logs', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('user_id');
            $table->uuid('session_id')->nullable();

            // Activity tracking
            $table->date('activity_date');
            $table->enum('activity_type', ['hosted_session', 'joined_session']);

            // Rewards
            $table->unsignedSmallInteger('xp_earned')->default(0);

            // Timestamp (created_at only)
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('prayer_sessions')->cascadeOnDelete();

            // Indexes
            $table->index('user_id');
            $table->index('activity_date');

            // Unique constraint to prevent duplicate daily logs
            $table->unique(['user_id', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streak_logs');
    }
};
