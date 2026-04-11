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
        Schema::create('session_reports', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('session_id');
            $table->uuid('reporter_id');
            $table->uuid('reported_user_id');

            // Report details
            $table->text('reason');
            $table->enum('stage', ['during', 'after']);

            // Moderation
            $table->boolean('reviewed')->default(false);

            // Timestamp (created_at only)
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('prayer_sessions')->cascadeOnDelete();
            $table->foreign('reporter_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reported_user_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('session_id');
            $table->index('reporter_id');
            $table->index('reported_user_id');
            $table->index('reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_reports');
    }
};
