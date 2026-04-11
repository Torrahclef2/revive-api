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
        Schema::create('session_members', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('session_id');
            $table->uuid('user_id');

            // Member status
            $table->enum('status', ['requested', 'admitted', 'rejected', 'kicked'])->default('requested');

            // Timing
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('prayer_sessions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('status');

            // Unique constraint to prevent duplicate memberships
            $table->unique(['session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_members');
    }
};
